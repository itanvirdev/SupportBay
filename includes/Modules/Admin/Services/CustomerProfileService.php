<?php

declare(strict_types=1);

namespace SupportBay\Modules\Admin\Services;

use RuntimeException;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Tickets\Entities\Ticket;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Verifications\Services\VerificationService;

final class CustomerProfileService {
  public function __construct(
    private readonly CustomerService $customers,
    private readonly TicketService $tickets,
    private readonly VerificationService $verifications,
    private readonly ActivityService $activities,
  ) {
  }

  /** @return array<string, mixed> */
  public function profile(int $customerId): array {
    $profile = $this->customers->profile($customerId);
    $customer = $profile->customer();
    $tickets = $this->tickets->findByCustomer($customerId);
    $verifications = $this->verifications->findByCustomer($customerId);
    $ticketById = [];

    foreach ($tickets as $ticket) {
      $ticketById[$ticket->id()] = $ticket;
    }

    $ticketIds = array_keys($ticketById);
    $openTickets = array_filter($tickets, static fn(Ticket $ticket): bool => ! $ticket->isClosed());

    return [
      'customer' => [
        'id' => $customer->id(),
        'display_name' => $profile->displayName(),
        'email' => $profile->email(),
        'avatar_url' => $customer->avatarUrl() ?: get_avatar_url($customer->userId()),
        'state' => $customer->state()->value,
        'source' => $customer->source()->value,
        'company' => $customer->company(),
        'phone' => $customer->phone(),
        'country' => $customer->country(),
        'timezone' => $customer->timezone(),
        'language' => $customer->language(),
        'last_login_at' => $customer->lastLoginAt(),
        'created_at' => $customer->createdAt(),
      ],
      'summary' => [
        'tickets' => count($tickets),
        'open_tickets' => count($openTickets),
        'purchases' => count($verifications),
        'verified_purchases' => count(array_filter($verifications, static fn($verification): bool => $verification->isValid())),
      ],
      'providers' => array_map(fn(array $connection): array => [
        'provider' => $connection['provider'],
        'reference' => $this->mask($connection['reference']),
      ], $this->customers->providerConnections($customerId)),
      'purchases' => array_map(fn($verification): array => [
        'id' => $verification->id(),
        'provider' => $verification->provider(),
        'reference' => $this->mask($verification->providerReference()),
        'product_name' => $verification->productName(),
        'product_id' => $verification->productId(),
        'license_type' => $verification->licenseType(),
        'purchased_at' => $verification->purchasedAt(),
        'support_expires_at' => $verification->supportExpiresAt(),
        'status' => $verification->status()->value,
      ], $verifications),
      'tickets' => array_map(static fn(Ticket $ticket): array => [
        'id' => $ticket->id(),
        'track_id' => $ticket->trackId(),
        'subject' => $ticket->subject(),
        'status' => $ticket->status()->value,
        'state' => $ticket->state()->value,
        'priority' => $ticket->priority()->value,
        'created_at' => $ticket->createdAt(),
        'updated_at' => $ticket->updatedAt(),
      ], $tickets),
      'activity' => array_map(static function ($activity) use ($ticketById): array {
        $ticket = $ticketById[$activity->ticketId()] ?? null;

        return [
          'id' => $activity->id(),
          'ticket_id' => $activity->ticketId(),
          'ticket_track_id' => $ticket?->trackId(),
          'label' => $activity->eventType()->label(),
          'description' => $activity->description(),
          'created_at' => $activity->createdAt(),
        ];
      }, $this->activities->getByTickets($ticketIds)),
    ];
  }

  private function mask(string $reference): string {
    $length = strlen($reference);

    if ($length <= 6) {
      return str_repeat('•', $length);
    }

    return substr($reference, 0, 3) . str_repeat('•', min(8, $length - 6)) . substr($reference, -3);
  }
}
