<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers\Repositories;

use RuntimeException;
use SupportBay\Core\Integrations\Data\OAuthIdentityData;
use SupportBay\Core\Integrations\Data\OAuthTokenData;
use WP_Error;
use WP_User;

final class WordPressUserRepository {
  /**
   * Find a WordPress user by ID.
   */
  public function find(int $userId): ?WP_User {
    $user = get_userdata($userId);

    return $user instanceof WP_User ? $user : null;
  }

  /**
   * Find a WordPress user by provider identity.
   */
  public function findByProvider(
    string $provider,
    string $reference,
  ): ?int {
    $users = get_users([
      'fields'     => 'ids',
      'number'     => 1,
      'meta_key'   => $this->identityKey($provider),
      'meta_value' => $reference,
    ]);

    return isset($users[0]) ? (int) $users[0] : null;
  }

  /**
   * Find a WordPress user by email.
   */
  public function findByEmail(string $email): ?int {
    $user = get_user_by('email', $email);

    return $user ? (int) $user->ID : null;
  }

  /**
   * Create a WordPress customer account.
   */
  public function create(OAuthIdentityData $identity): int {
    $login = $this->uniqueLogin($identity->username());

    $userId = wp_insert_user([
      'user_login'   => $login,
      'user_pass'    => wp_generate_password(32, true, true),
      'user_email'   => $identity->email() ?? '',
      'display_name' => $identity->displayName() ?? $identity->username(),
      'role'         => 'subscriber',
    ]);

    if ($userId instanceof WP_Error) {
      throw new RuntimeException($userId->get_error_message());
    }

    return (int) $userId;
  }

  /**
   * Persist a provider link and encrypted token payload.
   */
  public function linkProvider(
    int $userId,
    OAuthIdentityData $identity,
    OAuthTokenData $token,
  ): void {
    update_user_meta(
      $userId,
      $this->identityKey($identity->provider()),
      $identity->providerReference(),
    );

    update_user_meta(
      $userId,
      $this->connectionKey($identity->provider()),
      $this->encrypt([
        'token'        => $token->toArray(),
        'connected_at' => current_time('mysql'),
      ]),
    );
  }

  /**
   * Delete a test-created WordPress user.
   */
  public function delete(int $userId): bool {
    if (! function_exists('wp_delete_user')) {
      require_once ABSPATH . 'wp-admin/includes/user.php';
    }

    return wp_delete_user($userId);
  }

  private function identityKey(string $provider): string {
    return 'sbay_oauth_' . sanitize_key($provider) . '_identity';
  }

  private function connectionKey(string $provider): string {
    return 'sbay_oauth_' . sanitize_key($provider) . '_connection';
  }

  private function uniqueLogin(string $username): string {
    $base = sanitize_user($username, true);

    if ($base === '') {
      $base = 'supportbay-customer';
    }

    $login = $base;
    $suffix = 1;

    while (username_exists($login)) {
      $login = $base . '-' . $suffix;
      $suffix++;
    }

    return $login;
  }

  /**
   * Encrypt provider secrets using the WordPress installation salt.
   *
   * @param array<string, mixed> $payload
   */
  private function encrypt(array $payload): string {
    $json = wp_json_encode($payload);

    if ($json === false) {
      throw new RuntimeException(
        'Unable to encode provider connection data.'
      );
    }

    $key = hash('sha256', wp_salt('auth'), true);
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt(
      $json,
      'aes-256-gcm',
      $key,
      OPENSSL_RAW_DATA,
      $iv,
      $tag,
    );

    if ($ciphertext === false) {
      throw new RuntimeException(
        'Unable to encrypt provider connection data.'
      );
    }

    return base64_encode($iv . $tag . $ciphertext);
  }
}
