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

  /** @return Tag[] */
  public function activeForCustomers(): array {
    return array_values(array_filter(
      $this->active(),
      static fn(Tag $tag): bool => $tag->isCustomerVisible(),
    ));
  }

  /** @param mixed[] $values @return Tag[] */
  public function validateSelection(array $values, bool $customerOnly = false): array {
    $result = [];
    foreach (array_values(array_unique(array_filter(array_map('absint', $values)))) as $id) {
      $tag = $this->find($id);
      if (! $tag || ! $tag->isActive() || ($customerOnly && ! $tag->isCustomerVisible())) {
        throw new InvalidArgumentException('Please select available tags.');
      }
      $result[] = $tag;
    }
    return $result;
  }

  /** @param array<int, array<string, mixed>|string> $items @return Tag[] */
  public function bulkUpsert(array $items): array {
    $result = [];
    foreach ($items as $item) {
      $data = is_string($item) ? ['name' => $item] : $item;
      $name = sanitize_text_field((string) ($data['name'] ?? ''));
      if ($name === '') { continue; }
      $slug = sanitize_title($name);
      $existing = $this->repository->findBySlug($slug);
      $result[] = $existing
        ? $this->update($existing->id(), [...$data, 'name' => $name, 'slug' => $slug])
        : $this->create([...$data, 'name' => $name, 'slug' => $slug]);
    }
    return array_values(array_filter($result));
  }

  public function delete(int $id): bool {
    if (! $this->repository->find($id)) { return false; }
    $this->repository->deleteAssignmentsForTag($id);
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
      if (strlen($name) > 150) { throw new InvalidArgumentException('Tag name cannot exceed 150 characters.'); }
      $result['name'] = $name;
      $data['slug'] = $name;
    }
    if ($creating || array_key_exists('slug', $data)) {
      $slug = sanitize_title((string) ($data['slug'] ?? ''));
      if ($slug === '') { throw new InvalidArgumentException('Tag slug is required.'); }
      $result['slug'] = $slug;
    }
    if ($creating || array_key_exists('color', $data)) {
      $result['color'] = sanitize_hex_color((string) ($data['color'] ?? '')) ?: null;
    }
    if ($creating || array_key_exists('show_on', $data)) {
      $showOn = sanitize_key((string) ($data['show_on'] ?? 'both'));
      if (! in_array($showOn, ['both', 'admin_only'], true)) {
        throw new InvalidArgumentException('Tag visibility is invalid.');
      }
      $result['show_on'] = $showOn;
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
