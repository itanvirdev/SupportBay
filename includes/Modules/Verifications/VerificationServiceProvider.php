<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Verifications\Repositories\VerificationRepository;
use SupportBay\Modules\Verifications\Services\VerificationService;

final class VerificationServiceProvider extends ServiceProvider {
  /**
   * Register services.
   */
  public function register(Container $container): void {
    $container->singleton(VerificationRepository::class);

    $container->singleton(VerificationService::class);
  }
}
