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

    $plainToken = $authService->generate(
      userId: 1,
      type: AuthTokenType::MAGIC_LOGIN,
      redirectTo: '/support/ticket/9D980553',
      expiresAt: date('Y-m-d H:i:s', strtotime('+30 days')),
    );

    Assert::true(
      ! empty($plainToken),
      'Auth token generated.'
    );

    // -------------------------------------------------
    // Retrieve Token
    // -------------------------------------------------

    $token = $authService->findByToken($plainToken);

    Assert::notNull(
      $token,
      'Auth token retrieved.'
    );

    Assert::equals(
      1,
      $token->userId(),
      'WordPress user linked.'
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
      '/support/ticket/9D980553',
      $token->redirectTo(),
      'Redirect stored.'
    );

    Assert::equals(
      0,
      $token->useCount(),
      'Initial use count.'
    );

    Assert::false(
      $token->isExpired(),
      'Token is not expired.'
    );

    Assert::true(
      $token->canBeUsed(),
      'Token is usable.'
    );

    // -------------------------------------------------
    // Authenticate Token
    // -------------------------------------------------

    $authenticated = $authService->authenticate($plainToken);

    Assert::notNull(
      $authenticated,
      'Token authenticated.'
    );

    Assert::equals(
      1,
      $authenticated->useCount(),
      'Use count incremented.'
    );

    Assert::notNull(
      $authenticated->lastUsedAt(),
      'Last used timestamp updated.'
    );

    // -------------------------------------------------
    // Revoke Token
    // -------------------------------------------------

    $authService->revoke($authenticated->id());

    $revoked = $authService->find(
      $authenticated->id()
    );

    Assert::equals(
      AuthTokenState::REVOKED,
      $revoked->state(),
      'Token revoked.'
    );

    Assert::true(
      $revoked->isRevoked(),
      'Revoked state detected.'
    );

    Assert::false(
      $revoked->canBeUsed(),
      'Revoked token cannot be used.'
    );

    Assert::notNull(
      $revoked->revokedAt(),
      'Revoked timestamp stored.'
    );
  }
}
