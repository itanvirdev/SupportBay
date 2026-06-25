<?php

declare(strict_types=1);

namespace SupportBay\Modules\Activities\Services;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Modules\Activities\Entities\Activity;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Repositories\ActivityRepository;
use RuntimeException;

final class ActivityService {
  public function __construct(
    private ActivityRepository $repository,
  ) {
  }

  /**
   * Create activity.
   */
  public function create(array $data): Activity {
    $data = $this->normalize($data);

    $activityId = $this->repository->create($data);

    $activity = $this->repository->find($activityId);

    if (!$activity) {
      throw new RuntimeException(
        'Failed to create activity.'
      );
    }

    return $activity;
  }

  /**
   * Find activity.
   */
  public function find(int $id): ?Activity {
    return $this->repository->find($id);
  }

  /**
   * Get ticket timeline.
   *
   * @return Activity[]
   */
  public function getByTicket(int $ticketId): array {
    return $this->repository->getByTicket($ticketId);
  }

  /**
   * Get activities by actor.
   *
   * @return Activity[]
   */
  public function getByActor(int $actorId): array {
    return $this->repository->getByActor($actorId);
  }

  /**
   * Get activities by event type.
   *
   * @return Activity[]
   */
  public function getByEvent(ActivityType $eventType): array {
    return $this->repository->getByEvent($eventType);
  }

  /**
   * Normalize defaults.
   */
  private function normalize(array $data): array {
    $data['actor_type'] = $data['actor_type']
      ?? AuthorType::SYSTEM->value;

    $data['description'] = $data['description']
      ?? null;

    $data['payload'] = isset($data['payload'])
      ? wp_json_encode($data['payload'])
      : null;

    $data['ip_address'] = $data['ip_address']
      ?? $this->getIpAddress();

    return $data;
  }

  /**
   * Resolve client IP.
   */
  private function getIpAddress(): ?string {
    return $_SERVER['REMOTE_ADDR'] ?? null;
  }
}
