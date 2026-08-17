<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use InvalidArgumentException;
use SupportBay\Core\Database\DatabaseInstaller;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\CustomFields\Enums\CustomFieldStatus;
use SupportBay\Modules\CustomFields\Http\Controllers\CustomFieldController;
use SupportBay\Modules\CustomFields\Services\CustomFieldService;
use SupportBay\Modules\Tickets\Services\TicketService;
use WP_Error;

final class CustomFieldFlowTest extends FlowTest {
  protected static function title(): string { return 'Custom Field Flow Test'; }

  protected static function execute(...$services): void {
    /** @var CustomFieldService $fields */
    /** @var TicketService $tickets */
    /** @var CustomFieldController $controller */
    [$fields, $tickets, $controller] = $services;
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
      'customer_visible' => true,
      'department_id' => 1,
      'sort_order' => 10,
    ]);
    $ticketId = $tickets->create([
      'customer_id' => null,
      'department_id' => 1,
      'subject' => 'Custom field foundation ' . $suffix,
    ]);

    try {
      Assert::true(
        $field->slug() === 'product-version-' . $suffix
        && $field->options() === ['Current', 'Legacy']
        && $field->isRequired()
        && $field->isCustomerVisible()
        && count($fields->applicable(1, true)) >= 1
        && ! in_array($field->id(), array_map(static fn($item): int => $item->id(), $fields->applicable(2, true)), true),
        'Definitions sanitize choices and respect status, audience, order, and department scope.',
      );

      $fields->setValue($ticketId, $field->id(), 'Current', 1);
      $fields->setValue($ticketId, $field->id(), 'Legacy', 1);
      $values = $fields->valuesForTicket($ticketId);
      Assert::true(
        count($values) === 1
        && $values[0]->value() === 'Legacy'
        && $values[0]->updatedBy() === 1,
        'Ticket custom-field values are normalized and upserted by the unique ticket-field relationship.',
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

      $updated = $fields->update($field->id(), ['status' => CustomFieldStatus::INACTIVE->value]);
      Assert::true($updated !== null && ! $updated->isActive(), 'Custom fields support an inactive historical lifecycle.');
    } finally {
      $fields->removeValue($ticketId, $field->id());
      $tickets->delete($ticketId);
      $fields->delete($field->id());
      wp_set_current_user(0);
    }
  }
}
