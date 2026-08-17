<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tags\Services;

use InvalidArgumentException;
use SupportBay\Modules\Tags\Entities\Tag;
use SupportBay\Modules\Tags\Enums\TagStatus;
use SupportBay\Modules\Tags\Repositories\TagRepository;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;
use SupportBay\Core\Events\EventDispatcher;
use SupportBay\Modules\Tags\Events\TicketTagChanged;

final class TagService {
  public function __construct(
    private readonly TagRepository $repository,
    private readonly TicketRepository $tickets,
    private readonly EventDispatcher $events,
  ) {
  }

  /** @param array<string, mixed> $data */
  public function create(array $data): Tag {
    $normalized = $this->normalize($data, true);

    if ($this->repository->findBySlug($normalized['slug'])) {
      throw new InvalidArgumentException('Tag slug already exists.');
    }

    return $this->repository->find($this->repository->create($normalized))
      ?? throw new InvalidArgumentException('Tag could not be created.');
  }

  /** @param array<string, mixed> $data */
  public function update(int $id, array $data): ?Tag {
    if (! $this->repository->find($id)) { return null; }
    $normalized = $this->normalize($data, false);

    if (isset($normalized['slug'])) {
      $match = $this->repository->findBySlug($normalized['slug']);
      if ($match && $match->id() !== $id) {
        throw new InvalidArgumentException('Tag slug already exists.');
      }
    }

    if ($normalized !== []) { $this->repository->update($id, $normalized); }
    return $this->repository->find($id);
  }

  public function find(int $id): ?Tag { return $this->repository->find($id); }

  /** @return Tag[] */
  public function all(): array { return $this->repository->all(); }

  /** @return Tag[] */
  public function active(): array { return $this->repository->active(); }

  public function delete(int $id): bool {
    if (! $this->repository->find($id)) { return false; }
    if ($this->repository->assignmentCount($id) > 0) {
      throw new InvalidArgumentException(
        'Tags used by tickets must be deactivated instead of deleted.'
      );
    }
    return $this->repository->delete($id);
  }

  public function attach(int $ticketId, int $tagId, ?int $actorId = null): void {
    $ticket = $this->tickets->find($ticketId);
    if (! $ticket) {
      throw new InvalidArgumentException('Ticket was not found.');
    }
    $tag = $this->find($tagId);
    if (! $tag || ! $tag->isActive()) {
      throw new InvalidArgumentException('Please select an active tag.');
    }
    if ($this->repository->isAttached($ticketId, $tagId)) { return; }
    if (! $this->repository->attach($ticketId, $tagId, $actorId)) {
      throw new InvalidArgumentException('Tag could not be attached.');
    }
    if ($actorId !== null) {
      $this->events->dispatch(new TicketTagChanged($ticket, $tag, 'added', $actorId));
    }
  }

  public function detach(int $ticketId, int $tagId, ?int $actorId = null): void {
    $ticket = $this->tickets->find($ticketId);
    $tag = $this->find($tagId);
    if (! $ticket || ! $tag || ! $this->repository->isAttached($ticketId, $tagId)) { return; }
    if (! $this->repository->detach($ticketId, $tagId)) {
      throw new InvalidArgumentException('Tag could not be detached.');
    }
    if ($actorId !== null) {
      $this->events->dispatch(new TicketTagChanged($ticket, $tag, 'removed', $actorId));
    }
  }

  /** @return Tag[] */
  public function forTicket(int $ticketId): array {
    return $this->repository->findByTicket($ticketId);
  }

  /** @param int[] $ticketIds @return array<int, Tag[]> */
  public function forTickets(array $ticketIds): array {
    return $this->repository->findByTickets($ticketIds);
  }

  /** @param array<string, mixed> $data @return array<string, mixed> */
  private function normalize(array $data, bool $creating): array {
    $result = [];
    if ($creating || array_key_exists('name', $data)) {
      $name = sanitize_text_field((string) ($data['name'] ?? ''));
      if ($name === '') { throw new InvalidArgumentException('Tag name is required.'); }
      $result['name'] = $name;
      if ($creating && ! isset($data['slug'])) { $data['slug'] = $name; }
    }
    if ($creating || array_key_exists('slug', $data)) {
      $slug = sanitize_title((string) ($data['slug'] ?? ''));
      if ($slug === '') { throw new InvalidArgumentException('Tag slug is required.'); }
      $result['slug'] = $slug;
    }
    if ($creating || array_key_exists('color', $data)) {
      $result['color'] = sanitize_hex_color((string) ($data['color'] ?? '')) ?: null;
    }
    if ($creating || array_key_exists('status', $data)) {
      $status = TagStatus::tryFrom(sanitize_key(
        (string) ($data['status'] ?? TagStatus::ACTIVE->value)
      ));
      if (! $status) { throw new InvalidArgumentException('Tag status is invalid.'); }
      $result['status'] = $status->value;
    }
    return $result;
  }
}
