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
  ) {
    if (! in_array($type, ['text', 'secret', 'url'], true)) {
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
      'value' => $this->isSecret() ? '' : $value,
      'configured' => $this->isSecret() ? $configured : null,
    ];
  }

  public function key(): string { return $this->key; }
  public function label(): string { return $this->label; }
  public function type(): string { return $this->type; }
  public function isRequired(): bool { return $this->required; }
  public function isSecret(): bool { return $this->type === 'secret'; }
}
