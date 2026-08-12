<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Integrations\Contracts\ConfigurableIntegrationProvider;
use SupportBay\Core\Integrations\Contracts\ConnectionTestProvider;
use SupportBay\Core\Integrations\Data\ProviderConfigurationField;
use SupportBay\Core\Integrations\Data\ProviderConnectionTestData;
use SupportBay\Modules\Providers\Enums\ProviderCategory;

final class FakeConfigurableProvider implements ConfigurableIntegrationProvider, ConnectionTestProvider {
  public function slug(): string { return 'fake-configurable'; }
  public function name(): string { return 'Fake Configurable'; }
  public function category(): ProviderCategory { return ProviderCategory::OTHER; }
  public function version(): string { return '1.0.0'; }
  public function boot(): void { }

  public function configurationFields(): array {
    return [
      new ProviderConfigurationField('client_id', 'Client ID', required: true),
      new ProviderConfigurationField('client_secret', 'Client Secret', 'secret', true),
      new ProviderConfigurationField('redirect_uri', 'Redirect URI', 'url', true),
    ];
  }

  public function testConnection(array $configuration): ProviderConnectionTestData {
    $successful = ($configuration['client_id'] ?? '') !== 'fail-connection';

    return new ProviderConnectionTestData(
      $successful,
      $successful ? 'Provider connection succeeded.' : 'Provider rejected the configured credentials.',
    );
  }
}
