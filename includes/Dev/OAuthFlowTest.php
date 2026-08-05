<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Auth\Services\OAuthLoginService;
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
    [$oauth, $integrations, $customers] = $services;

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
