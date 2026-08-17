<?php

declare(strict_types=1);

namespace SupportBay\Modules\Roles\Services;

use InvalidArgumentException;
use SupportBay\Modules\Roles\Entities\SupportRole;
use SupportBay\Modules\Roles\Repositories\SupportRoleRepository;

final class SupportRoleService {
  private const PROTECTED = ['administrator'];
  private const INTERNAL = ['read', 'sbay_access_dashboard', 'sbay_access_agent_dashboard', 'sbay_view_tickets'];

  public function __construct(private readonly SupportRoleRepository $repository) {}

  /** @return array<string, array<string, string>> */
  public function catalog(): array {
    return [
      'Tickets' => [
        'sbay_view_tickets' => 'View staff ticket workspace',
        'sbay_reply_ticket' => 'Reply to tickets',
        'sbay_create_internal_note' => 'Create internal notes',
        'sbay_upload_attachment' => 'Upload attachments',
        'sbay_change_ticket_status' => 'Change ticket status',
        'sbay_change_ticket_priority' => 'Change ticket priority',
        'sbay_move_ticket_department' => 'Move ticket department',
        'sbay_take_ticket_ownership' => 'Take ticket ownership',
        'sbay_assign_ticket' => 'Assign or transfer individual tickets',
        'sbay_reassign_ticket' => 'Bulk reassign tickets',
        'sbay_merge_ticket' => 'Merge tickets',
        'sbay_split_ticket' => 'Split tickets',
      ],
      'Classification' => [
        'sbay_change_ticket_category' => 'Change ticket categories',
        'sbay_change_ticket_tags' => 'Change ticket tags',
        'sbay_change_ticket_custom_fields' => 'Edit ticket custom fields',
      ],
      'Customers & Verification' => [
        'sbay_manage_customers' => 'Manage customers',
        'sbay_view_purchase_verification' => 'View purchase verification',
        'sbay_refresh_verification' => 'Refresh purchase verification',
      ],
      'Content & Organization' => [
        'sbay_use_saved_replies' => 'Use saved replies',
        'sbay_manage_saved_replies' => 'Manage saved replies',
        'sbay_manage_departments' => 'Manage departments',
        'sbay_manage_categories' => 'Manage categories',
        'sbay_manage_tags' => 'Manage tags',
        'sbay_manage_custom_fields' => 'Manage custom fields',
      ],
      'Reports' => [
        'sbay_view_reports' => 'View reports',
        'sbay_export_reports' => 'Export reports',
      ],
      'Settings & Integrations' => [
        'sbay_manage_settings' => 'Manage SupportBay settings',
        'sbay_manage_providers' => 'Manage integrations and providers',
      ],
    ];
  }

  /** @return string[] */
  public function requiredCapabilities(): array { return ['sbay_view_tickets']; }

  /** @return SupportRole[] */
  public function all(): array {
    $roles = $this->repository->roles();
    $metadata = $this->repository->metadata();
    $allowed = $this->allowedCapabilities();
    $items = [$this->administrator($roles)];
    foreach ($roles as $slug => $role) {
      if (! str_starts_with($slug, 'sbay_') || $slug === 'sbay_customer') { continue; }
      $meta = $metadata[$slug] ?? [];
      $selected = isset($meta['capabilities']) && is_array($meta['capabilities'])
        ? array_values(array_intersect($meta['capabilities'], $allowed))
        : array_values(array_intersect(array_keys(array_filter($role['capabilities'])), $allowed));
      $items[] = new SupportRole(
        slug: $slug,
        name: sanitize_text_field((string) $role['name']),
        description: isset($meta['description']) ? sanitize_textarea_field((string) $meta['description']) : null,
        active: ($meta['status'] ?? 'active') === 'active',
        builtIn: in_array($slug, ['sbay_agent', 'sbay_manager'], true),
        editable: true,
        supportRole: (bool) ($meta['support_role'] ?? in_array('sbay_view_tickets', $selected, true)),
        capabilities: $selected,
        userCount: $this->repository->userCount($slug),
      );
    }
    return $items;
  }

  public function find(string $slug): ?SupportRole {
    foreach ($this->all() as $role) { if ($role->slug() === $slug) { return $role; } }
    return null;
  }

  /** @param array<string, mixed> $data */
  public function create(array $data): SupportRole {
    $name = sanitize_text_field((string) ($data['name'] ?? ''));
    if ($name === '') { throw new InvalidArgumentException('Role name is required.'); }
    $base = sanitize_key(sanitize_title($name));
    $slug = str_starts_with($base, 'sbay_') ? $base : 'sbay_' . $base;
    if ($slug === 'sbay_') { throw new InvalidArgumentException('Role slug is required.'); }
    if ($this->find($slug) || isset($this->repository->roles()[$slug])) {
      throw new InvalidArgumentException('Role slug already exists.');
    }
    return $this->persist($slug, $data + ['name' => $name]);
  }

  /** @param array<string, mixed> $data */
  public function update(string $slug, array $data): SupportRole {
    $existing = $this->find($slug);
    if (! $existing) { throw new InvalidArgumentException('Role was not found.'); }
    if (! $existing->isEditable()) { throw new InvalidArgumentException('Built-in WordPress roles are view-only.'); }
    return $this->persist($slug, $data, $existing);
  }

  public function delete(string $slug): void {
    $role = $this->find($slug);
    if (! $role) { throw new InvalidArgumentException('Role was not found.'); }
    if (! $role->isEditable()) { throw new InvalidArgumentException('The Administrator role cannot be deleted.'); }
    if ($role->isInUse()) { throw new InvalidArgumentException('Roles assigned to users cannot be deleted.'); }
    $this->repository->deleteRole($slug);
    $this->repository->deleteMetadata($slug);
  }

  /** @return array<string, array{name:string, capabilities:array<string, bool>}> */
  public function filterEditableRoles(array $roles): array {
    foreach ($this->all() as $role) {
      if (! $role->isActive()) { unset($roles[$role->slug()]); }
    }
    return $roles;
  }

  /** @param array<string, mixed> $data */
  private function persist(string $slug, array $data, ?SupportRole $existing = null): SupportRole {
    $name = sanitize_text_field((string) ($data['name'] ?? $existing?->name() ?? ''));
    if ($name === '') { throw new InvalidArgumentException('Role name is required.'); }
    $status = sanitize_key((string) ($data['status'] ?? ($existing?->isActive() ? 'active' : 'inactive')));
    if (! in_array($status, ['active', 'inactive'], true)) { throw new InvalidArgumentException('Role status is invalid.'); }
    $supportRole = filter_var($data['support_role'] ?? $existing?->isSupportRole() ?? true, FILTER_VALIDATE_BOOL);
    $requested = (array) ($data['capabilities'] ?? $existing?->capabilities() ?? []);
    $capabilities = $supportRole ? array_values(array_unique([...$this->requiredCapabilities(), ...array_intersect(array_map(
      static fn(mixed $capability): string => sanitize_key((string) $capability),
      array_slice($requested, 0, 100),
    ), $this->allowedCapabilities())])) : [];
    $runtime = $status === 'active' && $supportRole ? array_values(array_unique([...self::INTERNAL, ...$capabilities])) : ['read'];
    if (! $this->repository->saveRole($slug, $name, $runtime)) { throw new InvalidArgumentException('Role could not be saved.'); }
    $this->repository->saveMetadata($slug, [
      'description' => sanitize_textarea_field((string) ($data['description'] ?? $existing?->description() ?? '')),
      'status' => $status,
      'support_role' => $supportRole,
      'capabilities' => $capabilities,
    ]);
    return $this->find($slug) ?? throw new InvalidArgumentException('Role could not be loaded.');
  }

  /** @return string[] */
  private function allowedCapabilities(): array {
    return array_keys(array_merge(...array_values($this->catalog())));
  }

  /** @param array<string, array{name:string, capabilities:array<string, bool>}> $roles */
  private function administrator(array $roles): SupportRole {
    $role = $roles['administrator'] ?? ['name' => 'Administrator', 'capabilities' => []];
    return new SupportRole('administrator', sanitize_text_field((string) $role['name']), 'Native WordPress administrator role.', true, true, false, true, $this->allowedCapabilities(), $this->repository->userCount('administrator'));
  }
}
