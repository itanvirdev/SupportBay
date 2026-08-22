<?php

declare(strict_types=1);

namespace SupportBay\Core\Integrations\Data;

use InvalidArgumentException;

final class ProviderConfigurationField {
  public function __construct(
    private readonly string $key,
    private readonly string $label,
    private readonly string $type = 'text',
    private readonly bool $required = false,
    private readonly ?string $description = null,
    private readonly mixed $defaultValue = null,
    private readonly string $group = 'main',
    private readonly ?string $requiredWhen = null,
  ) {
    if (! in_array($type, ['text', 'secret', 'url', 'toggle', 'readonly'], true)) {
      throw new InvalidArgumentException('Unsupported provider configuration field type.');
    }
  }

  /** @return array<string, mixed> */
  public function toArray(mixed $value = null, bool $configured = false): array {
    return [
      'key' => $this->key,
      'label' => $this->label,
      'type' => $this->type,
      'required' => $this->required,
      'description' => $this->description,
      'value' => $this->isSecret() ? '' : ($value !== null && $value !== '' ? $value : $this->defaultValue),
      'configured' => $this->isSecret() ? $configured : null,
      'group' => $this->group,
    ];
  }

  public function key(): string { return $this->key; }
  public function label(): string { return $this->label; }
  public function type(): string { return $this->type; }
  public function isRequired(): bool { return $this->required; }
  public function isSecret(): bool { return $this->type === 'secret'; }
  public function defaultValue(): mixed { return $this->defaultValue; }
  public function group(): string { return $this->group; }
  public function requiredWhen(): ?string { return $this->requiredWhen; }
}
