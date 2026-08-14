<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Auth\Services\OAuthLoginService;
use SupportBay\Modules\Auth\Http\OAuthRoutes;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Services\CustomerService;

final class OAuthFlowTest extends FlowTest {
  protected static function title(): string {
    return 'OAuth Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var OAuthLoginService $oauth */
    /** @var IntegrationManager $integrations */
    /** @var CustomerService $customers */
    /** @var OAuthRoutes $routes */
    [$oauth, $integrations, $customers, $routes] = $services;

    $provider = new FakeOAuthProvider();

    if ($integrations->has($provider->slug())) {
      $integrations->unregister($provider->slug());
    }

    $integrations->register($provider);

    $reference = strtoupper(
      wp_generate_password(16, false, false)
    );

    $authorizationUrl = $oauth->authorizationUrl(
      $provider->slug(),
      ['state' => 'test-state'],
    );

    Assert::true(
      str_contains($authorizationUrl, 'test-state'),
      'Provider authorization URL is resolved generically.'
    );

    Assert::true(
      str_contains($routes->connectUrl($provider->slug()), 'sbay_oauth=login')
      && str_contains($routes->connectUrl($provider->slug()), 'provider=fake-oauth'),
      'Customer provider connection uses the generic OAuth entry route.'
    );

    $customer = $oauth->login(
      $provider->slug(),
      'test-code',
      ['reference' => $reference],
    );

    Assert::true(
      $customer->id() > 0,
      'OAuth login creates a SupportBay customer.'
    );

    Assert::equals(
      CustomerSource::PROVIDER,
      $customer->source(),
      'OAuth customer uses the provider source.'
    );

    Assert::true(
      $customer->cameFromProvider(),
      'Customer detects its provider origin.'
    );

    Assert::true(
      $customer->hasLoggedIn(),
      'OAuth login timestamp is recorded.'
    );

    $storedConnection = (string) get_user_meta(
      $customer->userId(),
      'sbay_oauth_fake-oauth_connection',
      true,
    );

    Assert::true(
      $storedConnection !== '' &&
      ! str_contains($storedConnection, 'fake-access-token'),
      'OAuth tokens are encrypted before persistence.'
    );

    $providerContext = $oauth->providerContext(
      $customer->id(),
      $provider->slug(),
    );

    Assert::equals(
      'fake-refreshed-access-token',
      $providerContext['access_token'] ?? null,
      'An expiring OAuth access token is refreshed before provider use.'
    );

    Assert::equals(
      1,
      $provider->refreshCalls(),
      'OAuth refresh-capable provider is called exactly once.'
    );

    $persistedContext = $customers->providerContext(
      $customer->id(),
      $provider->slug(),
    );

    Assert::equals(
      'fake-refreshed-access-token',
      $persistedContext['access_token'] ?? null,
      'Refreshed OAuth token is encrypted and persisted for reuse.'
    );

    $oauth->providerContext($customer->id(), $provider->slug());

    Assert::equals(
      1,
      $provider->refreshCalls(),
      'A valid refreshed OAuth token is reused without another refresh.'
    );

    $connected = $oauth->connect(
      $customer->id(),
      $provider->slug(),
      'connect-code',
      ['reference' => $reference],
    );

    Assert::equals(
      $customer->id(),
      $connected->id(),
      'OAuth provider can be reconnected to the authenticated customer.'
    );

    $otherReference = 'OTHER-' . strtoupper(
      wp_generate_password(12, false, false)
    );
    $otherCustomer = $oauth->login(
      $provider->slug(),
      'other-code',
      ['reference' => $otherReference],
    );
    $collisionRejected = false;

    try {
      $oauth->connect(
        $customer->id(),
        $provider->slug(),
        'collision-code',
        ['reference' => $otherReference],
      );
    } catch (\RuntimeException $exception) {
      $collisionRejected = str_contains(
        $exception->getMessage(),
        'another customer'
      );
    }

    Assert::true(
      $collisionRejected,
      'OAuth identities already owned by another customer cannot be attached.'
    );

    Assert::true(
      $customers->deleteWithUser($otherCustomer->id()),
      'Collision test customer and WordPress user deleted.'
    );

    $existing = $oauth->login(
      $provider->slug(),
      'second-code',
      ['reference' => $reference],
    );

    Assert::equals(
      $customer->id(),
      $existing->id(),
      'Repeated OAuth login reuses the linked customer.'
    );

    Assert::true(
      $customers->deleteWithUser($customer->id()),
      'Test customer and WordPress user deleted.'
    );

    $integrations->unregister($provider->slug());
  }
}
