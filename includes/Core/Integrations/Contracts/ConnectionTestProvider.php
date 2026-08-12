<?php

declare(strict_types=1);

namespace SupportBay\Core\Integrations\Contracts;

use SupportBay\Core\Integrations\Data\ProviderConnectionTestData;

interface ConnectionTestProvider extends IntegrationProvider {
  /** @param array<string, mixed> $configuration */
  public function testConnection(array $configuration): ProviderConnectionTestData;
}
