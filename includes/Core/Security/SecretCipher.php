<?php

declare(strict_types=1);

namespace SupportBay\Core\Security;

use RuntimeException;

final class SecretCipher {
  private const PREFIX = 'sbay:v1:';

  public function encrypt(string $value): string {
    $key = hash('sha256', wp_salt('auth'), true);
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt(
      $value,
      'aes-256-gcm',
      $key,
      OPENSSL_RAW_DATA,
      $iv,
      $tag,
    );

    if ($ciphertext === false) {
      throw new RuntimeException('Unable to encrypt secret data.');
    }

    return self::PREFIX . base64_encode($iv . $tag . $ciphertext);
  }

  public function decrypt(string $value): string {
    if (! $this->isEncrypted($value)) {
      return $value;
    }

    $payload = base64_decode(substr($value, strlen(self::PREFIX)), true);

    if ($payload === false || strlen($payload) < 29) {
      throw new RuntimeException('Encrypted secret data is invalid.');
    }

    $plaintext = openssl_decrypt(
      substr($payload, 28),
      'aes-256-gcm',
      hash('sha256', wp_salt('auth'), true),
      OPENSSL_RAW_DATA,
      substr($payload, 0, 12),
      substr($payload, 12, 16),
    );

    if ($plaintext === false) {
      throw new RuntimeException('Unable to decrypt secret data.');
    }

    return $plaintext;
  }

  public function isEncrypted(string $value): bool {
    return str_starts_with($value, self::PREFIX);
  }
}
