<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers\Listeners;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Events\Contracts\Event;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Customers\Events\CustomerCreated;

final class LogCustomerCreatedActivity implements Listener {
  public function __construct(
    private ActivityService $activities,
  ) {
  }

  public function handle(Event $event): void {
    if (! $event instanceof CustomerCreated) {
      return;
    }

    $customer = $event->customer();

    $this->activities->create([
      'ticket_id'   => 0,
      'actor_id'    => $customer->userId(),
      'actor_type'  => AuthorType::SYSTEM->value,
      'event_type'  => ActivityType::CUSTOMER_CREATED->value,
      'description' => sprintf(
        'Customer "%s" created.',
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
