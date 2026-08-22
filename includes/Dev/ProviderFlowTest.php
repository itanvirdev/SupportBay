<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Providers\Enums\ProviderCategory;
use SupportBay\Modules\Providers\Enums\ProviderStatus;
use SupportBay\Modules\Providers\Services\ProviderService;
use SupportBay\Modules\Providers\Services\ProviderConfiguration;
use SupportBay\Core\Security\SecretCipher;
use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Modules\Providers\Services\ProviderConnectionService;

final class ProviderFlowTest extends FlowTest {
  /**
   * Test title.
   */
  protected static function title(): string {
    return 'Provider Flow Test';
  }

  /**
   * Execute flow.
   */
  protected static function execute(...$services): void {
    /** @var ProviderService $providerService */
    /** @var ProviderConfiguration $configuration */
    /** @var SecretCipher $cipher */
    /** @var IntegrationManager $integrations */
    /** @var ProviderConnectionService $connections */
    [$providerService, $configuration, $cipher, $integrations, $connections] = $services;

    echo "🚀 Starting SupportBay Provider Flow Test...\n\n";

    $providerSlug = 'fake-configurable';
    $existing = $providerService->findBySlug($providerSlug);

    if ($existing) {
      $providerService->delete($existing->id());
    }

    if (! $integrations->has($providerSlug)) {
      $integrations->register(new FakeConfigurableProvider());
    }

    // -------------------------------------------------
    // Create Provider
    // -------------------------------------------------

    $providerId = $providerService->create([
      'slug'     => $providerSlug,
      'name'     => 'Flow Test Provider',
      'category' => ProviderCategory::MARKETPLACE,
      'version'  => '1.0.0',
      'status'   => ProviderStatus::DISABLED,
    ]);

    Assert::true(
      $providerId > 0,
      'Provider created.'
    );

    // -------------------------------------------------
    // Retrieve Provider
    // -------------------------------------------------

    $provider = $providerService->find($providerId);

    Assert::notNull(
      $provider,
      'Provider retrieved.'
    );

    Assert::equals(
      $providerId,
      $provider->id(),
      'Provider ID matches.'
    );

    Assert::equals(
      $providerSlug,
      $provider->slug(),
      'Slug stored.'
    );

    Assert::equals(
      'Flow Test Provider',
      $provider->name(),
      'Name stored.'
    );

    $renamed = $providerService->rename($providerId, 'Marketplace Option');
    Assert::equals('Marketplace Option', $renamed->name(), 'Provider ticket-form option label is editable.');

    Assert::equals(
      ProviderCategory::MARKETPLACE,
      $provider->category(),
      'Category stored.'
    );

    Assert::equals(
      ProviderStatus::DISABLED,
      $provider->status(),
      'Initial status stored.'
    );

    Assert::equals(
      '1.0.0',
      $provider->version(),
      'Version stored.'
    );

    $configuration->update($providerSlug, [
      'client_id' => 'demo-client',
      'client_secret' => 'provider-secret',
      'redirect_uri' => 'https://example.com/callback',
    ]);

    $provider = $providerService->find($providerId);

    Assert::true($provider->hasSettings(), 'Validated settings stored.');

    Assert::equals(
      'demo-client',
      $configuration->clientId($providerSlug),
      'Hydrated provider settings are available through configuration.'
    );

    $encrypted = $cipher->encrypt('provider-secret');

    Assert::true(
      $encrypted !== 'provider-secret'
      && $cipher->decrypt($encrypted) === 'provider-secret',
      'Provider secrets encrypt and decrypt safely.'
    );

    Assert::true(
      $provider->settings()['client_secret'] !== 'provider-secret'
      && $configuration->clientSecret($providerSlug) === 'provider-secret'
      && $configuration->form($providerSlug)['fields'][1]['value'] === '',
      'Stored provider secrets are encrypted and omitted from configuration forms.'
    );

    $configuration->update($providerSlug, [
      'client_id' => 'updated-client',
      'client_secret' => '',
      'redirect_uri' => 'https://example.com/callback',
    ]);

    Assert::equals(
      'provider-secret',
      $configuration->clientSecret($providerSlug),
      'Blank secret updates preserve the existing credential.'
    );

    Assert::true(
      $connections->supports($providerSlug)
      && $connections->test($providerSlug)->isSuccessful(),
      'Provider-declared connection test succeeds with valid configuration.'
    );

    $provider = $providerService->find($providerId);

    Assert::notNull(
      $provider->lastConnectedAt(),
      'Successful connection test records provider health.'
    );

    $configuration->update($providerSlug, [
      'client_id' => 'fail-connection',
      'client_secret' => '',
      'redirect_uri' => 'https://example.com/callback',
    ]);

    Assert::true(
      ! $connections->test($providerSlug)->isSuccessful(),
      'Provider-declared connection failure is normalized.'
    );

    $provider = $providerService->find($providerId);

    Assert::true(
      $provider->hasError(),
      'Failed connection test records safe provider health.'
    );

    Assert::true(
      $provider->isDisabled(),
      'Provider initially disabled.'
    );

    // -------------------------------------------------
    // Enable Provider
    // -------------------------------------------------

    $providerService->enable($providerId);

    $provider = $providerService->find($providerId);

    Assert::true(
      $provider->isEnabled(),
      'Provider enabled.'
    );

    // -------------------------------------------------
    // Record Successful Connection
    // -------------------------------------------------

    $providerService->connected($providerId);

    $provider = $providerService->find($providerId);

    Assert::notNull(
      $provider->lastConnectedAt(),
      'Connection timestamp stored.'
    );

    // -------------------------------------------------
    // Record Connection Failure
    // -------------------------------------------------

    $providerService->connectionFailed(
      $providerId,
      'Invalid API credentials.'
    );

    $provider = $providerService->find($providerId);

    Assert::equals(
      'Invalid API credentials.',
      $provider->lastError(),
      'Connection error stored.'
    );

    Assert::true(
      $provider->hasError(),
      'Provider reports connection error.'
    );

    // -------------------------------------------------
    // Disable Provider
    // -------------------------------------------------

    $providerService->disable($providerId);

    $provider = $providerService->find($providerId);

    Assert::true(
      $provider->isDisabled(),
      'Provider disabled.'
    );

    Assert::true(
      $providerService->delete($providerId),
      'Test provider removed.'
    );

    echo "\n🎯 Provider Flow Test Passed.\n";
  }
}
