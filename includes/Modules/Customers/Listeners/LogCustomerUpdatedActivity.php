<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers\Listeners;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Events\Contracts\Event;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Customers\Events\CustomerUpdated;

final class LogCustomerUpdatedActivity implements Listener {
  public function __construct(
    private ActivityService $activities,
  ) {
  }

  public function handle(Event $event): void {
    if (! $event instanceof CustomerUpdated) {
      return;
    }

    $customer = $event->customer();

    $this->activities->create([
      'ticket_id'   => 0,
      'actor_id'    => $customer->userId(),
      'actor_type'  => AuthorType::SYSTEM->value,
      'event_type'  => ActivityType::CUSTOMER_UPDATED->value,
      'description' => sprintf(
        'Customer "%s" updated.',
        $customer->displayName()
      ),
      'payload' => [
        'customer_id' => $customer->id(),
        'state'       => $customer->state()->value,
        'source'      => $customer->source()->value,
      ],
    ]);
  }
}
