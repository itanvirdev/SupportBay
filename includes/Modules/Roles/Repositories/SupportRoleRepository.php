<?php

declare(strict_types=1);

namespace SupportBay\Modules\Roles\Repositories;

final class SupportRoleRepository {
  private const OPTION = 'sbay_role_metadata';

  /** @return array<string, array{name:string, capabilities:array<string, bool>}> */
  public function roles(): array {
    return wp_roles()->roles;
  }

  /** @return array<string, array<string, mixed>> */
  public function metadata(): array {
    $value = get_option(self::OPTION, []);
    return is_array($value) ? $value : [];
  }

  /** @param array<string, mixed> $metadata */
  public function saveMetadata(string $slug, array $metadata): void {
    $all = $this->metadata();
    $all[$slug] = $metadata;
    update_option(self::OPTION, $all, false);
  }

  public function deleteMetadata(string $slug): void {
    $all = $this->metadata();
    unset($all[$slug]);
    update_option(self::OPTION, $all, false);
  }

  /** @param string[] $capabilities */
  public function saveRole(string $slug, string $name, array $capabilities): bool {
    $role = get_role($slug);
    if (! $role) {
      $role = add_role($slug, $name, []);
    } else {
      wp_roles()->roles[$slug]['name'] = $name;
      wp_roles()->role_names[$slug] = $name;
      update_option(wp_roles()->role_key, wp_roles()->roles);
    }
    if (! $role) { return false; }
    foreach (array_keys($role->capabilities) as $capability) { $role->remove_cap($capability); }
    foreach ($capabilities as $capability) { $role->add_cap($capability); }
    return true;
  }

  public function deleteRole(string $slug): void { remove_role($slug); }

  public function userCount(string $slug): int {
    return count(get_users(['role' => $slug, 'fields' => 'ids']));
  }
}
