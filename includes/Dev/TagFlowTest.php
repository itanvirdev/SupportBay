<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use InvalidArgumentException;
use SupportBay\Core\Database\DatabaseInstaller;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Tags\Enums\TagStatus;
use SupportBay\Modules\Tags\Services\TagService;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Tags\Http\Controllers\TagController;
use WP_Error;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Tickets\Data\TicketQuery;
use SupportBay\Modules\Tickets\Enums\TicketBulkAction;

final class TagFlowTest extends FlowTest {
  protected static function title(): string { return 'Tag Flow Test'; }

  protected static function execute(...$services): void {
    /** @var TagService $tags */
    /** @var TicketService $tickets */
    /** @var TagController $controller */
    /** @var ActivityService $activities */
    [$tags, $tickets, $controller, $activities] = $services;
    DatabaseInstaller::install();
    if (did_action('rest_api_init') === 0) {
      do_action('rest_api_init', rest_get_server());
    }
    Assert::true(
      isset(rest_get_server()->get_routes()['/sbay/v1/tags']),
      'Versioned tag routes are registered.'
    );
    wp_set_current_user(0);
    Assert::true(
      $controller->canManage() instanceof WP_Error,
      'Anonymous tag management is rejected.'
    );
    wp_set_current_user(1);
    Assert::true(
      $controller->canManage() === true,
      'Administrators can manage tags.'
    );
    $suffix = strtolower(wp_generate_password(8, false, false));
    $tag = $tags->create(['name' => 'Urgent Customer ' . $suffix, 'color' => '#b32d2e']);
    $ticketId = $tickets->create([
      'customer_id' => 1,
      'subject' => 'Tag foundation flow ' . $suffix,
    ]);

    try {
      Assert::true(
        $tag->slug() === 'urgent-customer-' . $suffix
        && $tag->isActive()
        && $tag->color() === '#b32d2e',
        'A sanitized active tag is created.'
      );
      $tags->attach($ticketId, $tag->id(), 1);
      $tags->attach($ticketId, $tag->id(), 1);
      Assert::count(1, $tags->forTicket($ticketId), 'Ticket-tag relationships are unique and reusable.');
      $filtered = $tickets->searchQueue(new TicketQuery(tagId: $tag->id()));
      Assert::true(
        $filtered['total'] === 1
        && $filtered['items'][0]->toArray()['tags'][0]['id'] === $tag->id(),
        'The queue filters by tag and returns assigned tag metadata.'
      );
      Assert::count(1, array_filter(
        $activities->getByTicket($ticketId),
        static fn($activity): bool => $activity->eventType() === ActivityType::TAG_ADDED,
      ), 'Duplicate assignment emits one tag-added activity.');

      $blocked = false;
      try { $tags->delete($tag->id()); } catch (InvalidArgumentException) { $blocked = true; }
      Assert::true($blocked, 'Tags attached to tickets cannot be deleted.');

      $updated = $tags->update($tag->id(), ['status' => TagStatus::INACTIVE->value]);
      Assert::true($updated !== null && ! $updated->isActive(), 'Tag lifecycle updates preserve identity.');

      $tickets->bulkChange([$ticketId], TicketBulkAction::TAG_REMOVE, $tag->id(), 1);
      Assert::count(0, $tags->forTicket($ticketId), 'Tags can be detached from tickets.');
      Assert::count(1, array_filter(
        $activities->getByTicket($ticketId),
        static fn($activity): bool => $activity->eventType() === ActivityType::TAG_REMOVED,
      ), 'Bulk removal emits one tag-removed activity.');
    } finally {
      $tags->detach($ticketId, $tag->id());
      $tickets->delete($ticketId);
      $tags->delete($tag->id());
      wp_set_current_user(0);
    }
  }
}
