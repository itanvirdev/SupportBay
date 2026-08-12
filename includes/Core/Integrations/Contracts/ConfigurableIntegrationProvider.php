<?php

declare(strict_types=1);

namespace SupportBay\Core\Integrations\Contracts;

use SupportBay\Core\Integrations\Data\ProviderConfigurationField;

interface ConfigurableIntegrationProvider extends IntegrationProvider {
  /** @return ProviderConfigurationField[] */
  public function configurationFields(): array;
}
