<?php

declare(strict_types=1);

namespace SupportBay\Modules\SavedReplies\Services;

use InvalidArgumentException;
use SupportBay\Common\Utilities\RichTextSanitizer;
use SupportBay\Modules\SavedReplies\Entities\SavedReply;
use SupportBay\Modules\SavedReplies\Enums\SavedReplyStatus;
use SupportBay\Modules\SavedReplies\Repositories\SavedReplyRepository;

final class SavedReplyService {
  /** @return array<int, array{key: string, label: string}> */
  public function placeholders(): array {
    return [
      ['key' => 'customer_name', 'label' => 'Customer name'], ['key' => 'customer_email', 'label' => 'Customer email'],
      ['key' => 'ticket_id', 'label' => 'Ticket ID'], ['key' => 'track_id', 'label' => 'Ticket track ID'],
      ['key' => 'ticket_subject', 'label' => 'Ticket subject'], ['key' => 'ticket_priority', 'label' => 'Ticket priority'],
      ['key' => 'ticket_status', 'label' => 'Ticket status'], ['key' => 'agent_name', 'label' => 'Assigned agent'],
      ['key' => 'category_name', 'label' => 'Category'], ['key' => 'product_name', 'label' => 'Product name'],
      ['key' => 'license_type', 'label' => 'License type'], ['key' => 'support_expires_at', 'label' => 'Support expiry'],
    ];
  }

  public function __construct(private readonly SavedReplyRepository $repository) {
  }

  public function create(array $data, int $createdBy): SavedReply {
    $normalized = $this->normalize($data, true);
    $normalized['created_by'] = $createdBy;
    $id = $this->repository->create($normalized);
    return $this->repository->find($id) ?? throw new InvalidArgumentException('Saved reply could not be created.');
  }

  public function update(int $id, array $data): ?SavedReply {
    if (! $this->repository->find($id)) { return null; }
    $normalized = $this->normalize($data, false);
    if ($normalized !== []) { $this->repository->update($id, $normalized); }
    return $this->repository->find($id);
  }

  public function find(int $id): ?SavedReply { return $this->repository->find($id); }

  /** @return SavedReply[] */
  public function search(string $term = '', ?SavedReplyStatus $status = null, string $orderBy = 'title', ?string $category = null): array {
    $orderBy = sanitize_key($orderBy);
    if (! in_array($orderBy, ['title', 'usage', 'recent'], true)) {
      throw new InvalidArgumentException('Saved reply sort is invalid.');
    }
    $category = $category !== null ? sanitize_text_field($category) : null;
    return $this->repository->search(sanitize_text_field($term), $status, $orderBy, $category !== '' ? $category : null);
  }

  public function delete(int $id): bool {
    return $this->repository->find($id) ? $this->repository->delete($id) : false;
  }

  public function recordUsage(int $id, int $userId): ?SavedReply {
    if ($userId <= 0 || ! $this->repository->recordUsage($id, $userId)) { return null; }
    return $this->repository->find($id);
  }

  private function normalize(array $data, bool $creating): array {
    $normalized = [];
    if ($creating || array_key_exists('title', $data)) {
      $title = sanitize_text_field((string) ($data['title'] ?? ''));
      if ($title === '') { throw new InvalidArgumentException('Saved reply title is required.'); }
      $normalized['title'] = $title;
    }
    if ($creating || array_key_exists('content', $data)) {
      $content = RichTextSanitizer::sanitize((string) ($data['content'] ?? ''));
      if (trim(wp_strip_all_tags($content)) === '') { throw new InvalidArgumentException('Saved reply content is required.'); }
      $normalized['content'] = $content;
    }
    if ($creating || array_key_exists('status', $data)) {
      $status = $data['status'] ?? SavedReplyStatus::ACTIVE->value;
      $status = $status instanceof SavedReplyStatus ? $status : SavedReplyStatus::tryFrom(sanitize_key((string) $status));
      if (! $status) { throw new InvalidArgumentException('Saved reply status is invalid.'); }
      $normalized['status'] = $status->value;
    }
    if ($creating || array_key_exists('category', $data)) {
      $category = sanitize_text_field((string) ($data['category'] ?? ''));
      if (strlen($category) > 100) { throw new InvalidArgumentException('Saved reply category is too long.'); }
      $normalized['category'] = $category !== '' ? $category : null;
    }
    return $normalized;
  }
}
