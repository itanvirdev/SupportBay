<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Services;

use SupportBay\Modules\Settings\Services\GeneralSettingsService;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;

final class TicketTrackIdService {
  public function __construct(
    private readonly TicketRepository $tickets,
    private readonly GeneralSettingsService $settings,
  ) {
  }

  public function next(): string {
    if (! $this->settings->sequentialTrackIdEnabled()) {
      return $this->random();
    }

    return $this->random(
      $this->settings->sequentialTrackIdPrefix(),
      $this->settings->sequentialTrackIdLength(),
    );
  }

  private function random(string $prefix = '', int $length = 8): string {
    do {
      $random=strtoupper(substr(bin2hex(random_bytes((int)ceil($length/2))),0,$length));
      $trackId=$prefix.$random;
    } while ($this->tickets->findByTrackId($trackId));

    return $trackId;
  }
}
