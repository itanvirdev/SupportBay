<?php

declare(strict_types=1);

namespace SupportBay\Modules\CustomFields\Services;

use DateTimeImmutable;
use InvalidArgumentException;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Events\EventDispatcher;
use SupportBay\Modules\CustomFields\Entities\CustomField;
use SupportBay\Modules\CustomFields\Entities\TicketCustomFieldValue;
use SupportBay\Modules\CustomFields\Enums\CustomFieldStatus;
use SupportBay\Modules\CustomFields\Enums\CustomFieldType;
use SupportBay\Modules\CustomFields\Events\TicketCustomFieldValueChanged;
use SupportBay\Modules\CustomFields\Repositories\CustomFieldRepository;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;
use SupportBay\Modules\Tickets\Entities\Ticket;
use SupportBay\Modules\Categories\Services\CategoryService;

final class CustomFieldService {
  public function __construct(
    private readonly CustomFieldRepository $repository,
    private readonly TicketRepository $tickets,
    private readonly EventDispatcher $events,
    private readonly CategoryService $categories,
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
  public function ticketFields(): array {
    return array_values(array_filter($this->active(), static fn(CustomField $field): bool => $field->formLocation() === 'ticket'));
  }

  /** @return CustomField[] */
  public function applicable(?int $categoryId, bool $customerOnly = false): array {
    return array_values(array_filter(
      $this->active(),
      static fn(CustomField $field): bool => $field->formLocation() === 'ticket'
        && $field->appliesToCategory($categoryId)
        && (! $customerOnly || $field->isCustomerVisible()),
    ));
  }

  /** @return CustomField[] */
  public function registrationFields(bool $customerOnly = true): array {
    return array_values(array_filter(
      $this->active(),
      static fn(CustomField $field): bool => $field->formLocation() === 'registration'
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

  public function move(int $id, string $direction): ?CustomField {
    $items = $this->all();
    usort(
      $items,
      static fn(CustomField $left, CustomField $right): int =>
        [$left->sortOrder(), $left->id()] <=> [$right->sortOrder(), $right->id()],
    );
    $index = array_search($id, array_map(static fn(CustomField $field): int => $field->id(), $items), true);
    if ($index === false) { return null; }
    $target = $direction === 'up' ? $index - 1 : ($direction === 'down' ? $index + 1 : -1);
    if (! isset($items[$target])) { return $items[$index]; }

    [$items[$index], $items[$target]] = [$items[$target], $items[$index]];

    foreach ($items as $position => $item) {
      $sortOrder = $position + 1;
      if ($item->sortOrder() !== $sortOrder) {
        $this->repository->update($item->id(), ['sort_order' => $sortOrder]);
      }
    }

    return $this->find($id);
  }

  /**
   * Validate and normalize customer-submitted values before ticket creation.
   *
   * @param array<int|string, mixed> $values
   * @return array<int, string>
   */
  public function validateCustomerValues(?int $categoryId, array $values): array {
    return $this->validateTicketValues($categoryId, $values, true);
  }

  /** @param array<int|string, mixed> $values @return array<int, string> */
  public function validateStaffValues(?int $categoryId, array $values): array {
    return $this->validateTicketValues($categoryId, $values, false);
  }

  /** @param array<int|string, mixed> $values @return array<int, string> */
  private function validateTicketValues(?int $categoryId, array $values, bool $customerOnly): array {
    $fields = [];
    foreach ($this->applicable($categoryId, $customerOnly) as $field) {
      $fields[$field->id()] = $field;
    }

    foreach (array_keys($values) as $fieldId) {
      $validId = is_int($fieldId)
        || (is_string($fieldId) && ctype_digit($fieldId));
      if (! $validId || ! isset($fields[(int) $fieldId])) {
        throw new InvalidArgumentException('Please select an available custom field.');
      }
    }

    $normalized = [];
    foreach ($fields as $fieldId => $field) {
      $value = $values[$fieldId] ?? $values[(string) $fieldId] ?? null;
      $result = $this->normalizeValue($field, $value);
      if ($result !== null) {
        $normalized[$fieldId] = $result;
      }
    }

    return $normalized;
  }

  /** @param array<int|string, mixed> $values @return array<int, string> */
  public function validateRegistrationValues(array $values): array {
    $fields = [];
    foreach ($this->registrationFields(true) as $field) { $fields[$field->id()] = $field; }
    foreach (array_keys($values) as $fieldId) {
      if ((! is_int($fieldId) && ! (is_string($fieldId) && ctype_digit($fieldId))) || ! isset($fields[(int) $fieldId])) {
        throw new InvalidArgumentException('Please select an available registration field.');
      }
    }
    $normalized = [];
    foreach ($fields as $fieldId => $field) {
      $value = $values[$fieldId] ?? $values[(string) $fieldId] ?? null;
      $result = $this->normalizeValue($field, $value);
      if ($result !== null) { $normalized[$fieldId] = $result; }
    }
    return $normalized;
  }

  public function setValue(
    int $ticketId,
    int $fieldId,
    mixed $value,
    ?int $actorId = null,
    ?AuthorType $actorType = null,
  ): void {
    $ticket = $this->tickets->find($ticketId);
    if (! $ticket) { throw new InvalidArgumentException('Ticket was not found.'); }
    $field = $this->find($fieldId);
    if (! $field || ! $field->isActive() || $field->formLocation() !== 'ticket' || ! $field->appliesToCategory($ticket->categoryId())) {
      throw new InvalidArgumentException('Please select an available custom field.');
    }
    $normalized = $this->normalizeValue($field, $value);
    $previous = $this->repository->findValue($ticketId, $fieldId);
    if ($previous?->value() === $normalized) { return; }
    $resolvedActorType = $actorType ?? ($actorId !== null
      ? AuthorType::AGENT
      : AuthorType::SYSTEM);
    if ($normalized === null) {
      if ($previous === null) { return; }
      if (! $this->repository->deleteValue($ticketId, $fieldId)) {
        throw new InvalidArgumentException('Custom field value could not be cleared.');
      }
      $this->events->dispatch(new TicketCustomFieldValueChanged(
        $ticket,
        $field,
        $previous,
        null,
        'cleared',
        $actorId,
        $resolvedActorType,
      ));
      return;
    }
    if (! $this->repository->saveValue($ticketId, $fieldId, $normalized, $actorId)) {
      throw new InvalidArgumentException('Custom field value could not be saved.');
    }
    $current = $this->repository->findValue($ticketId, $fieldId)
      ?? throw new InvalidArgumentException('Custom field value could not be loaded.');
    $this->events->dispatch(new TicketCustomFieldValueChanged(
      $ticket,
      $field,
      $previous,
      $current,
      $previous === null ? 'set' : 'updated',
      $actorId,
      $resolvedActorType,
    ));
  }

  /** @return TicketCustomFieldValue[] */
  public function valuesForTicket(int $ticketId): array {
    return $this->repository->valuesForTicket($ticketId);
  }

  /**
   * Apply one field value to a bounded collection while preserving per-ticket validation.
   *
   * @param int[] $ticketIds
   * @return array{updated: Ticket[], failed: array<int, string>}
   */
  public function bulkSetValues(
    array $ticketIds,
    int $fieldId,
    mixed $value,
    int $actorId,
  ): array {
    $updated = [];
    $failed = [];

    foreach (array_values(array_unique(array_map('absint', $ticketIds))) as $ticketId) {
      if ($ticketId === 0) { continue; }

      try {
        $this->setValue($ticketId, $fieldId, $value, $actorId);
        $updated[] = $this->tickets->find($ticketId)
          ?? throw new InvalidArgumentException('Ticket was not found.');
      } catch (InvalidArgumentException|\RuntimeException $exception) {
        $failed[$ticketId] = $exception->getMessage();
      }
    }

    return ['updated' => $updated, 'failed' => $failed];
  }

  /**
   * Return editable definitions plus definitions needed to explain historical values.
   *
   * @return CustomField[]
   */
  public function staffFieldsForTicket(int $ticketId): array {
    $ticket = $this->tickets->find($ticketId);
    if (! $ticket) { throw new InvalidArgumentException('Ticket was not found.'); }

    $fields = [];
    foreach ($this->applicable($ticket->categoryId()) as $field) {
      $fields[$field->id()] = $field;
    }
    foreach ($this->valuesForTicket($ticketId) as $value) {
      $field = $this->find($value->fieldId());
      if ($field) { $fields[$field->id()] = $field; }
    }

    uasort($fields, static fn(CustomField $left, CustomField $right): int =>
      [$left->sortOrder(), $left->id()] <=> [$right->sortOrder(), $right->id()]
    );
    return array_values($fields);
  }

  /**
   * Return stored values whose definitions are currently customer-visible.
   *
   * @return array<int, array{field: CustomField, value: TicketCustomFieldValue}>
   */
  public function customerValuesForTicket(int $ticketId): array {
    $visible = [];
    foreach ($this->valuesForTicket($ticketId) as $value) {
      $field = $this->find($value->fieldId());
      if ($field && $field->isCustomerVisible()) {
        $visible[] = ['field' => $field, 'value' => $value];
      }
    }

    usort($visible, static fn(array $left, array $right): int =>
      [$left['field']->sortOrder(), $left['field']->id()]
        <=> [$right['field']->sortOrder(), $right['field']->id()]
    );
    return $visible;
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
    if ($creating || array_key_exists('name', $data)) {
      $slug = str_replace('-', '_', sanitize_title((string) ($result['name'] ?? $data['name'] ?? '')));
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
    if ($creating || array_key_exists('placeholder', $data)) {
      $result['placeholder'] = sanitize_text_field((string) ($data['placeholder'] ?? '')) ?: null;
    }
    if ($creating || array_key_exists('form_location', $data)) {
      $location = sanitize_key((string) ($data['form_location'] ?? 'ticket'));
      if (! in_array($location, ['ticket', 'registration'], true)) { throw new InvalidArgumentException('Create Where is invalid.'); }
      $result['form_location'] = $location;
    }
    if ($creating || array_key_exists('audience', $data)) {
      $audience = sanitize_key((string) ($data['audience'] ?? 'both'));
      if (! in_array($audience, ['both', 'admin_only'], true)) { throw new InvalidArgumentException('Create For is invalid.'); }
      $result['audience'] = $audience;
    }
    if ($creating || array_key_exists('category_ids', $data) || array_key_exists('form_location', $data)) {
      $location = (string) ($result['form_location'] ?? $data['form_location'] ?? 'ticket');
      $ids = $location === 'ticket'
        ? array_values(array_unique(array_filter(array_map('absint', (array) ($data['category_ids'] ?? [])))))
        : [];
      foreach ($ids as $categoryId) {
        if (! $this->categories->find($categoryId)) { throw new InvalidArgumentException('Please select an available category.'); }
      }
      $result['category_ids'] = wp_json_encode($ids);
    }
    if ($creating || array_key_exists('status', $data)) {
      $status = CustomFieldStatus::tryFrom(sanitize_key((string) ($data['status'] ?? CustomFieldStatus::ACTIVE->value)));
      if (! $status) { throw new InvalidArgumentException('Custom field status is invalid.'); }
      $result['status'] = $status->value;
    }
    if ($creating) {
      $result['sort_order'] = $this->repository->nextSortOrder();
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
