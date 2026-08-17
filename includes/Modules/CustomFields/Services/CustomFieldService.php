<?php

declare(strict_types=1);

namespace SupportBay\Modules\CustomFields\Services;

use DateTimeImmutable;
use InvalidArgumentException;
use SupportBay\Modules\CustomFields\Entities\CustomField;
use SupportBay\Modules\CustomFields\Entities\TicketCustomFieldValue;
use SupportBay\Modules\CustomFields\Enums\CustomFieldStatus;
use SupportBay\Modules\CustomFields\Enums\CustomFieldType;
use SupportBay\Modules\CustomFields\Repositories\CustomFieldRepository;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;

final class CustomFieldService {
  public function __construct(
    private readonly CustomFieldRepository $repository,
    private readonly TicketRepository $tickets,
  ) {}

  /** @param array<string, mixed> $data */
  public function create(array $data): CustomField {
    $normalized = $this->normalizeDefinition($data, true);
    if ($this->repository->findBySlug($normalized['slug'])) {
      throw new InvalidArgumentException('Custom field slug already exists.');
    }
    return $this->repository->find($this->repository->create($normalized))
      ?? throw new InvalidArgumentException('Custom field could not be created.');
  }

  /** @param array<string, mixed> $data */
  public function update(int $id, array $data): ?CustomField {
    $existing = $this->find($id);
    if (! $existing) { return null; }
    $normalized = $this->normalizeDefinition($data, false);
    $resultingType = isset($normalized['type'])
      ? CustomFieldType::from($normalized['type'])
      : $existing->type();
    $resultingOptions = isset($normalized['options'])
      ? json_decode((string) $normalized['options'], true)
      : $existing->options();
    if ($resultingType === CustomFieldType::SELECT
      && (! is_array($resultingOptions) || $resultingOptions === [])) {
      throw new InvalidArgumentException('Select fields require at least one option.');
    }
    if (isset($normalized['slug'])) {
      $match = $this->repository->findBySlug($normalized['slug']);
      if ($match && $match->id() !== $id) {
        throw new InvalidArgumentException('Custom field slug already exists.');
      }
    }
    if (isset($normalized['type'])
      && $normalized['type'] !== $existing->type()->value
      && $this->repository->valueCount($id) > 0) {
      throw new InvalidArgumentException('A custom field type cannot change after values are stored.');
    }
    if ($normalized !== []) { $this->repository->update($id, $normalized); }
    return $this->find($id);
  }

  public function find(int $id): ?CustomField { return $this->repository->find($id); }
  /** @return CustomField[] */
  public function all(): array { return $this->repository->all(); }
  /** @return CustomField[] */
  public function active(): array { return $this->repository->active(); }

  /** @return CustomField[] */
  public function applicable(int $departmentId, bool $customerOnly = false): array {
    return array_values(array_filter(
      $this->active(),
      static fn(CustomField $field): bool => $field->appliesTo($departmentId)
        && (! $customerOnly || $field->isCustomerVisible()),
    ));
  }

  public function delete(int $id): bool {
    if (! $this->find($id)) { return false; }
    if ($this->repository->valueCount($id) > 0) {
      throw new InvalidArgumentException('Custom fields used by tickets must be deactivated instead of deleted.');
    }
    return $this->repository->delete($id);
  }

  public function setValue(int $ticketId, int $fieldId, mixed $value, ?int $actorId = null): void {
    $ticket = $this->tickets->find($ticketId);
    if (! $ticket) { throw new InvalidArgumentException('Ticket was not found.'); }
    $field = $this->find($fieldId);
    if (! $field || ! $field->isActive() || ! $field->appliesTo($ticket->departmentId())) {
      throw new InvalidArgumentException('Please select an available custom field.');
    }
    $normalized = $this->normalizeValue($field, $value);
    if ($normalized === null) {
      $this->repository->deleteValue($ticketId, $fieldId);
      return;
    }
    if (! $this->repository->saveValue($ticketId, $fieldId, $normalized, $actorId)) {
      throw new InvalidArgumentException('Custom field value could not be saved.');
    }
  }

  /** @return TicketCustomFieldValue[] */
  public function valuesForTicket(int $ticketId): array {
    return $this->repository->valuesForTicket($ticketId);
  }

  public function removeValue(int $ticketId, int $fieldId): void {
    if (! $this->repository->deleteValue($ticketId, $fieldId)) {
      throw new InvalidArgumentException('Custom field value could not be removed.');
    }
  }

  /** @param array<string, mixed> $data @return array<string, mixed> */
  private function normalizeDefinition(array $data, bool $creating): array {
    $result = [];
    if ($creating || array_key_exists('name', $data)) {
      $name = sanitize_text_field((string) ($data['name'] ?? ''));
      if ($name === '') { throw new InvalidArgumentException('Custom field name is required.'); }
      $result['name'] = $name;
      if ($creating && ! isset($data['slug'])) { $data['slug'] = $name; }
    }
    if ($creating || array_key_exists('slug', $data)) {
      $slug = sanitize_title((string) ($data['slug'] ?? ''));
      if ($slug === '') { throw new InvalidArgumentException('Custom field slug is required.'); }
      $result['slug'] = $slug;
    }
    if ($creating || array_key_exists('type', $data)) {
      $type = CustomFieldType::tryFrom(sanitize_key((string) ($data['type'] ?? CustomFieldType::TEXT->value)));
      if (! $type) { throw new InvalidArgumentException('Custom field type is invalid.'); }
      $result['type'] = $type->value;
    }
    if ($creating || array_key_exists('options', $data)) {
      $options = array_values(array_unique(array_filter(array_map(
        static fn(mixed $option): string => sanitize_text_field((string) $option),
        array_slice((array) ($data['options'] ?? []), 0, 100),
      ))));
      $type = CustomFieldType::tryFrom($result['type'] ?? sanitize_key((string) ($data['type'] ?? '')));
      if ($type === CustomFieldType::SELECT && $options === []) {
        throw new InvalidArgumentException('Select fields require at least one option.');
      }
      $result['options'] = wp_json_encode($options);
    }
    if ($creating || array_key_exists('is_required', $data)) {
      $result['is_required'] = rest_sanitize_boolean($data['is_required'] ?? false) ? 1 : 0;
    }
    if ($creating || array_key_exists('customer_visible', $data)) {
      $result['customer_visible'] = rest_sanitize_boolean($data['customer_visible'] ?? false) ? 1 : 0;
    }
    if ($creating || array_key_exists('department_id', $data)) {
      $result['department_id'] = absint($data['department_id'] ?? 0) ?: null;
    }
    if ($creating || array_key_exists('status', $data)) {
      $status = CustomFieldStatus::tryFrom(sanitize_key((string) ($data['status'] ?? CustomFieldStatus::ACTIVE->value)));
      if (! $status) { throw new InvalidArgumentException('Custom field status is invalid.'); }
      $result['status'] = $status->value;
    }
    if ($creating || array_key_exists('sort_order', $data)) {
      $result['sort_order'] = absint($data['sort_order'] ?? 0);
    }
    return $result;
  }

  private function normalizeValue(CustomField $field, mixed $value): ?string {
    $raw = is_scalar($value) ? trim((string) $value) : '';
    if ($raw === '') {
      if ($field->isRequired()) { throw new InvalidArgumentException($field->name() . ' is required.'); }
      return null;
    }
    return match ($field->type()) {
      CustomFieldType::TEXT => sanitize_text_field($raw),
      CustomFieldType::TEXTAREA => sanitize_textarea_field($raw),
      CustomFieldType::NUMBER => is_numeric($raw)
        ? (string) (float) $raw
        : throw new InvalidArgumentException($field->name() . ' must be a number.'),
      CustomFieldType::SELECT => in_array(sanitize_text_field($raw), $field->options(), true)
        ? sanitize_text_field($raw)
        : throw new InvalidArgumentException('Please select an available ' . $field->name() . ' option.'),
      CustomFieldType::CHECKBOX => rest_sanitize_boolean($value) ? '1' : '0',
      CustomFieldType::DATE => $this->normalizeDate($field, $raw),
      CustomFieldType::EMAIL => is_email($raw)
        ? sanitize_email($raw)
        : throw new InvalidArgumentException($field->name() . ' must be a valid email address.'),
      CustomFieldType::URL => filter_var($raw, FILTER_VALIDATE_URL)
        ? esc_url_raw($raw)
        : throw new InvalidArgumentException($field->name() . ' must be a valid URL.'),
    };
  }

  private function normalizeDate(CustomField $field, string $value): string {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (! $date || $date->format('Y-m-d') !== $value) {
      throw new InvalidArgumentException($field->name() . ' must use the Y-m-d date format.');
    }
    return $value;
  }
}
