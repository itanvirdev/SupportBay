<?php

declare(strict_types=1);

namespace SupportBay\Providers\LemonSqueezy\Services;

use SupportBay\Modules\Providers\Enums\ProviderStatus;
use SupportBay\Modules\Providers\Services\ProviderService;
use SupportBay\Providers\LemonSqueezy\LemonSqueezyProvider;

final class LemonSqueezyProvisioningService {
  public function __construct(private readonly ProviderService $providers, private readonly LemonSqueezyProvider $lemonSqueezy) {}
  public function provision(): void {
    if ($this->providers->findBySlug($this->lemonSqueezy->slug())) return;
    $this->providers->create(['slug'=>$this->lemonSqueezy->slug(),'name'=>$this->lemonSqueezy->name(),'category'=>$this->lemonSqueezy->category(),'version'=>$this->lemonSqueezy->version(),'status'=>ProviderStatus::DISABLED]);
  }
}
