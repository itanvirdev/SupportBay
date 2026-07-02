<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Providers\Enums\ProviderCategory;
use SupportBay\Modules\Providers\Enums\ProviderStatus;
use SupportBay\Modules\Providers\Services\ProviderService;

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
    [$providerService] = $services;

    echo "🚀 Starting SupportBay Provider Flow Test...\n\n";

    // -------------------------------------------------
    // Create Provider
    // -------------------------------------------------

    $providerId = $providerService->create([
      'slug'     => 'envato',
      'name'     => 'Envato',
      'category' => ProviderCategory::MARKETPLACE,
      'version'  => '1.0.0',
      'status'   => ProviderStatus::DISABLED,
      'settings' => [
        'client_id' => 'demo-client',
      ],
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
      'envato',
      $provider->slug(),
      'Slug stored.'
    );

    Assert::equals(
      'Envato',
      $provider->name(),
      'Name stored.'
    );

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

    Assert::true(
      $provider->hasSettings(),
      'Settings stored.'
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

    echo "\n🎯 Provider Flow Test Passed.\n";
  }
}
