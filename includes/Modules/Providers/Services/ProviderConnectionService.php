<?php

declare(strict_types=1);

namespace SupportBay\Modules\Providers\Services;

use RuntimeException;
use SupportBay\Core\Integrations\Contracts\ConnectionTestProvider;
use SupportBay\Core\Integrations\Data\ProviderConnectionTestData;
use SupportBay\Core\Integrations\IntegrationManager;

final class ProviderConnectionService {
  public function __construct(
    private readonly ProviderService $providers,
    private readonly ProviderConfiguration $configuration,
    private readonly IntegrationManager $integrations,
  ) {
  }

  public function supports(string $slug): bool {
    return $this->integrations->has($slug)
      && $this->integrations->integration($slug) instanceof ConnectionTestProvider;
  }

  public function test(string $slug): ProviderConnectionTestData {
    $provider = $this->providers->findBySlug($slug);

    if (! $provider) {
      throw new RuntimeException('Provider was not found.');
    }

    if (! $this->configuration->configured($slug)) {
      throw new RuntimeException('Complete the provider configuration before testing the connection.');
    }

    $integration = $this->integrations->integration($slug);

    if (! $integration instanceof ConnectionTestProvider) {
      throw new RuntimeException('This provider does not support non-interactive connection testing.');
    }

    try {
      $result = $integration->testConnection($this->configuration->all($slug));

      if ($result->isSuccessful()) {
        $this->providers->connected($provider->id());
      } else {
        $this->providers->connectionFailed(
          $provider->id(),
          sanitize_text_field($result->message()),
        );
      }

      return $result;
    } catch (\Throwable $exception) {
      $message = sanitize_text_field($exception->getMessage());
      $this->providers->connectionFailed($provider->id(), $message);

      return new ProviderConnectionTestData(false, $message ?: 'Provider connection failed.');
    }
  }
}
