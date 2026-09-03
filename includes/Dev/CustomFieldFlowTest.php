<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use InvalidArgumentException;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Database\DatabaseInstaller;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\CustomFields\Enums\CustomFieldStatus;
use SupportBay\Modules\CustomFields\Http\Controllers\CustomFieldController;
use SupportBay\Modules\CustomFields\Services\CustomFieldService;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Tickets\Data\TicketQuery;
use WP_Error;

final class CustomFieldFlowTest extends FlowTest {
  protected static function title(): string { return 'Custom Field Flow Test'; }

  protected static function execute(...$services): void {
    /** @var CustomFieldService $fields */
    /** @var TicketService $tickets */
    /** @var CustomFieldController $controller */
    /** @var ActivityService $activities */
    [$fields, $tickets, $controller, $activities] = $services;
    DatabaseInstaller::install();
    if (did_action('rest_api_init') === 0) { do_action('rest_api_init', rest_get_server()); }
    Assert::true(
      isset(rest_get_server()->get_routes()['/sbay/v1/custom-fields']),
      'Versioned custom-field definition routes are registered.',
    );
    wp_set_current_user(0);
    Assert::true($controller->canManage() instanceof WP_Error, 'Anonymous custom-field management is rejected.');
    wp_set_current_user(1);
    Assert::true($controller->canManage() === true, 'Administrators can manage custom fields.');

    $suffix = strtolower(wp_generate_password(8, false, false));
    $field = $fields->create([
      'name' => 'Product Version ' . $suffix,
      'type' => 'select',
      'options' => [' Current ', '<b>Legacy</b>', 'Current'],
      'is_required' => true,
      'placeholder' => 'Select a version',
      'form_location' => 'ticket',
      'audience' => 'both',
      'category_ids' => [],
    ]);
    $ticketId = $tickets->create([
      'customer_id' => 1,
      'subject' => 'Custom field foundation ' . $suffix,
    ]);

    try {
      Assert::true(
        $field->slug() === 'product_version_' . $suffix
        && $field->options() === ['Current', 'Legacy']
        && $field->isRequired()
        && $field->isCustomerVisible()
        && $field->placeholder() === 'Select a version'
        && in_array($field->id(), array_map(static fn($item): int => $item->id(), $fields->applicable(null, true)), true),
        'Definitions sanitize choices and respect status, audience, order, and category scope.',
      );

      $fields->setValue($ticketId, $field->id(), 'Current', 1, AuthorType::CUSTOMER);
      $fields->setValue($ticketId, $field->id(), 'Legacy', 1);
      $fields->setValue($ticketId, $field->id(), 'Legacy', 1);
      $values = $fields->valuesForTicket($ticketId);
      Assert::true(
        count($values) === 1
        && $values[0]->value() === 'Legacy'
        && $values[0]->updatedBy() === 1,
        'Ticket custom-field values are normalized and upserted by the unique ticket-field relationship.',
      );

      $anyValueQueue = $tickets->searchQueue(new TicketQuery(
        customFieldId: $field->id(),
      ));
      $exactValueQueue = $tickets->searchQueue(new TicketQuery(
        customFieldId: $field->id(),
        customFieldValue: 'Legacy',
      ));
      $missingValueQueue = $tickets->searchQueue(new TicketQuery(
        customFieldId: $field->id(),
        customFieldValue: 'Current',
      ));
      Assert::true(
        $anyValueQueue['total'] === 1
        && $exactValueQueue['total'] === 1
        && $missingValueQueue['total'] === 0,
        'Staff queues filter by custom-field presence and exact normalized values without duplicates.',
      );

      $invalid = false;
      try { $fields->setValue($ticketId, $field->id(), 'Unavailable', 1); }
      catch (InvalidArgumentException) { $invalid = true; }
      Assert::true($invalid, 'Select values must match the sanitized definition choices.');

      $typeBlocked = false;
      try { $fields->update($field->id(), ['type' => 'text']); }
      catch (InvalidArgumentException) { $typeBlocked = true; }
      Assert::true($typeBlocked, 'Field type cannot change after ticket values exist.');

      $deleteBlocked = false;
      try { $fields->delete($field->id()); }
      catch (InvalidArgumentException) { $deleteBlocked = true; }
      Assert::true($deleteBlocked, 'Definitions with historical values must be deactivated instead of deleted.');

      $fields->update($field->id(), ['is_required' => false]);
      $fields->setValue($ticketId, $field->id(), '', 1);
      $audit = array_values(array_filter(
        $activities->getByTicket($ticketId),
        static fn($activity): bool => in_array($activity->eventType(), [
          ActivityType::CUSTOM_FIELD_SET,
          ActivityType::CUSTOM_FIELD_UPDATED,
          ActivityType::CUSTOM_FIELD_CLEARED,
        ], true),
      ));
      $auditByType = [];
      foreach ($audit as $activity) { $auditByType[$activity->eventType()->value] = $activity; }
      Assert::true(
        count($audit) === 3
        && ($auditByType[ActivityType::CUSTOM_FIELD_SET->value] ?? null)?->actorType() === AuthorType::CUSTOMER
        && ($auditByType[ActivityType::CUSTOM_FIELD_UPDATED->value] ?? null)?->actorType() === AuthorType::AGENT
        && ($auditByType[ActivityType::CUSTOM_FIELD_CLEARED->value] ?? null)?->actorType() === AuthorType::AGENT,
        'Real value changes create one actor-attributed audit event while identical writes remain silent.',
      );
      $auditText = implode(' ', array_map(
        static fn($activity): string => (string) $activity->description() . ' ' . (string) $activity->payload(),
        $audit,
      ));
      Assert::true(
        ! str_contains($auditText, 'Current') && ! str_contains($auditText, 'Legacy'),
        'Custom-field audit metadata never stores sensitive field values.',
      );

      $updated = $fields->update($field->id(), ['status' => CustomFieldStatus::INACTIVE->value]);
      Assert::true($updated !== null && ! $updated->isActive(), 'Custom fields support an inactive historical lifecycle.');
    } finally {
      if ($fields->valuesForTicket($ticketId) !== []) {
        $fields->removeValue($ticketId, $field->id());
      }
      $tickets->delete($ticketId);
      $fields->delete($field->id());
      wp_set_current_user(0);
    }
  }
}
