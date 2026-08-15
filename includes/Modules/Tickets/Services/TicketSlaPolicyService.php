<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Services;

use InvalidArgumentException;
use SupportBay\Modules\Tickets\Entities\TicketSlaPolicy;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Tickets\Repositories\TicketSlaPolicyRepository;

final class TicketSlaPolicyService {
  public function __construct(private readonly TicketSlaPolicyRepository $policies) {
  }

  public function get(): TicketSlaPolicy { return $this->policies->get(); }

  /** @param array<string, mixed> $data */
  public function update(array $data): TicketSlaPolicy {
    $existing = $this->get();
    $enabled = array_key_exists('enabled', $data) ? $this->boolean($data['enabled']) : $existing->enabled();
    $targets = $existing->firstResponseMinutes();
    if (array_key_exists('first_response_minutes', $data)) {
      if (! is_array($data['first_response_minutes'])) {
        throw new InvalidArgumentException('First-response targets must be an object.');
      }
      foreach ($data['first_response_minutes'] as $priority => $minutes) {
        $priority = sanitize_key((string) $priority);
        if (TicketPriority::tryFrom($priority) === null) {
          throw new InvalidArgumentException('Unknown SLA priority.');
        }
        $minutes = (int) $minutes;
        if ($minutes < 15 || $minutes > 10080) {
          throw new InvalidArgumentException('SLA targets must be between 15 and 10080 minutes.');
        }
        $targets[$priority] = $minutes;
      }
    }
    $policy = new TicketSlaPolicy($enabled, $targets);
    $this->policies->save($policy);
    return $policy;
  }

  private function boolean(mixed $value): bool {
    if (is_bool($value)) { return $value; }
    if (in_array($value, [0, 1, '0', '1'], true)) { return (bool) $value; }
    throw new InvalidArgumentException('SLA enabled must be boolean.');
  }
}
