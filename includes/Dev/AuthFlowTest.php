<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Auth\Enums\AuthTokenState;
use SupportBay\Modules\Auth\Enums\AuthTokenType;
use SupportBay\Modules\Auth\Services\AuthService;

final class AuthFlowTest extends FlowTest {
  /**
   * Test title.
   */
  protected static function title(): string {
    return 'Auth Flow Test';
  }

  /**
   * Execute flow.
   */
  protected static function execute(...$services): void {
    /** @var AuthService $authService */
    [$authService] = $services;

    echo "🚀 Starting SupportBay Auth Flow Test...\n\n";

    // -------------------------------------------------
    // Generate Token
    // -------------------------------------------------

    $rawToken = $authService->create([
      'user_id'     => 1,
      'redirect_to' => '/support/ticket/F6C521D5',
    ]);

    Assert::true(
      ! empty($rawToken),
      'Magic token generated.'
    );

    // -------------------------------------------------
    // Retrieve Token
    // -------------------------------------------------

    $token = $authService->findByToken($rawToken);

    Assert::notNull(
      $token,
      'Token retrieved.'
    );

    Assert::equals(
      1,
      $token->userId(),
      'User linked.'
    );

    Assert::equals(
      AuthTokenType::MAGIC_LOGIN,
      $token->type(),
      'Token type stored.'
    );

    Assert::equals(
      AuthTokenState::ACTIVE,
      $token->state(),
      'Token state stored.'
    );

    Assert::equals(
      '/support/ticket/F6C521D5',
      $token->redirectTo(),
      'Redirect stored.'
    );

    Assert::null(
      $token->lastUsedAt(),
      'Token not yet used.'
    );

    // -------------------------------------------------
    // Authenticate
    // -------------------------------------------------

    $authenticated = $authService->authenticate($rawToken);

    Assert::true(
      $authenticated,
      'Authentication successful.'
    );

    $token = $authService->findByToken($rawToken);

    Assert::notNull(
      $token->lastUsedAt(),
      'Last used timestamp updated.'
    );

    // -------------------------------------------------
    // Revoke
    // -------------------------------------------------

    $authService->revoke($token->id());

    $token = $authService->find($token->id());

    Assert::equals(
      AuthTokenState::REVOKED,
      $token->state(),
      'Token revoked.'
    );

    Assert::notNull(
      $token->revokedAt(),
      'Revoked timestamp stored.'
    );
  }
}
