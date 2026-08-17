<?php

declare(strict_types=1);

namespace SupportBay\Modules\Roles\Entities;

use SupportBay\Core\Entities\Entity;

final class SupportRole extends Entity {
  /** @param string[] $capabilities */
  public function __construct(
    private readonly string $slug,
    private readonly string $name,
    private readonly ?string $description,
    private readonly bool $active,
    private readonly bool $builtIn,
    private readonly bool $editable,
    private readonly bool $supportRole,
    private readonly array $capabilities,
    private readonly int $userCount,
  ) {}

  public function toArray(): array {
    return [
      'slug' => $this->slug,
      'name' => $this->name,
      'description' => $this->description,
      'status' => $this->active ? 'active' : 'inactive',
      'built_in' => $this->builtIn,
      'editable' => $this->editable,
      'support_role' => $this->supportRole,
      'capabilities' => $this->capabilities,
      'user_count' => $this->userCount,
    ];
  }

  public function slug(): string { return $this->slug; }
  public function name(): string { return $this->name; }
  public function description(): ?string { return $this->description; }
  public function isActive(): bool { return $this->active; }
  public function isBuiltIn(): bool { return $this->builtIn; }
  public function isEditable(): bool { return $this->editable; }
  public function isSupportRole(): bool { return $this->supportRole; }
  /** @return string[] */
  public function capabilities(): array { return $this->capabilities; }
  public function userCount(): int { return $this->userCount; }

  public function isInUse(): bool { return $this->userCount > 0; }
}
