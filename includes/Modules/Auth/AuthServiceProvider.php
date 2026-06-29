<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Providers\ServiceProvider;
use SupportBay\Modules\Auth\Events\AuthTokenAuthenticated;
use SupportBay\Modules\Auth\Events\AuthTokenCreated;
use SupportBay\Modules\Auth\Events\AuthTokenRevoked;
use SupportBay\Modules\Auth\Listeners\LogAuthTokenAuthenticatedActivity;
use SupportBay\Modules\Auth\Listeners\LogAuthTokenCreatedActivity;
use SupportBay\Modules\Auth\Listeners\LogAuthTokenRevokedActivity;
use SupportBay\Modules\Auth\Repositories\AuthTokenRepository;
use SupportBay\Modules\Auth\Services\AuthService;

final class AuthServiceProvider extends ServiceProvider {
  /**
   * Event listeners.
   *
   * @var array<class-string, array<class-string>>
   */
  protected array $listeners = [
    AuthTokenCreated::class => [
      LogAuthTokenCreatedActivity::class,
    ],

    AuthTokenAuthenticated::class => [
      LogAuthTokenAuthenticatedActivity::class,
    ],

    AuthTokenRevoked::class => [
      LogAuthTokenRevokedActivity::class,
    ],
  ];

  /**
   * Register services.
   */
  public function register(Container $container): void {
    $container->singleton(AuthTokenRepository::class);

    $container->singleton(AuthService::class);
  }
}
