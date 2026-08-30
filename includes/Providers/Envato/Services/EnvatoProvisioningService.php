<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato\Services;

use SupportBay\Modules\Providers\Enums\ProviderStatus;
use SupportBay\Modules\Providers\Services\ProviderService;
use SupportBay\Providers\Envato\EnvatoProvider;

final class EnvatoProvisioningService {
  public function __construct(
    private readonly ProviderService $providers,
    private readonly EnvatoProvider $envato,
  ) {
  }

  /**
   * Ensure the runtime integration has its persistent configuration record.
   */
  public function provision(): void {
    if ($this->providers->findBySlug($this->envato->slug())) {
      return;
    }

    $this->providers->create([
      'slug' => $this->envato->slug(),
      'name' => $this->envato->name(),
      'category' => $this->envato->category(),
      'version' => $this->envato->version(),
      'status' => ProviderStatus::DISABLED,
    ]);
  }
}
