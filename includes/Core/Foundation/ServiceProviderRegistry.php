<?php

declare(strict_types=1);

namespace SupportBay\Core\Foundation;

use SupportBay\Core\Container\Container;
use SupportBay\Modules\Tickets\TicketServiceProvider;
use SupportBay\Modules\Messages\MessageServiceProvider;
use SupportBay\Modules\Departments\DepartmentServiceProvider;
use SupportBay\Core\Events\EventServiceProvider;
use SupportBay\Modules\Activities\ActivityServiceProvider;
use SupportBay\Modules\Attachments\AttachmentServiceProvider;
use SupportBay\Modules\Customers\CustomerServiceProvider;
use SupportBay\Modules\Auth\AuthServiceProvider;
use SupportBay\Modules\Providers\ProviderServiceProvider;
use SupportBay\Providers\Envato\EnvatoServiceProvider;
use SupportBay\Providers\LemonSqueezy\LemonSqueezyServiceProvider;
use SupportBay\Modules\Verifications\VerificationServiceProvider;
use SupportBay\Modules\Portal\PortalServiceProvider;
use SupportBay\Modules\Notifications\NotificationServiceProvider;
use SupportBay\Modules\Webhooks\WebhookServiceProvider;
use SupportBay\Modules\Admin\AdminServiceProvider;
use SupportBay\Modules\SavedReplies\SavedReplyServiceProvider;
use SupportBay\Modules\Categories\CategoryServiceProvider;
use SupportBay\Modules\Tags\TagServiceProvider;
use SupportBay\Modules\CustomFields\CustomFieldServiceProvider;
use SupportBay\Modules\Roles\RoleServiceProvider;
use SupportBay\Modules\Settings\SettingsServiceProvider;
use SupportBay\Modules\AssignRules\AssignRuleServiceProvider;

final class ServiceProviderRegistry {
  /**
   * Registered providers
   */
  private static array $providers = [];

  /**
   * Register system providers
   */
  public static function register(Container $container): void {
    self::registerCoreProviders($container);
    self::registerModuleProviders($container);
    self::registerIntegrationProviders($container);
    self::bootProviders($container);
  }

  /**
   * Core providers
   */
  private static function registerCoreProviders(Container $container): void {
    self::addProvider(new EventServiceProvider());
  }

  /**
   * Module providers
   */
  private static function registerModuleProviders(Container $container): void {
    self::addProvider(new TicketServiceProvider());
    self::addProvider(new DepartmentServiceProvider());
    self::addProvider(new ActivityServiceProvider());
    self::addProvider(new MessageServiceProvider());
    self::addProvider(new AttachmentServiceProvider());
    self::addProvider(new CustomerServiceProvider());
    self::addProvider(new SettingsServiceProvider());
    self::addProvider(new AuthServiceProvider());
    self::addProvider(new ProviderServiceProvider());
    self::addProvider(new VerificationServiceProvider());
    self::addProvider(new NotificationServiceProvider());
    self::addProvider(new SavedReplyServiceProvider());
    self::addProvider(new CategoryServiceProvider());
    self::addProvider(new TagServiceProvider());
    self::addProvider(new CustomFieldServiceProvider());
    self::addProvider(new RoleServiceProvider());
    self::addProvider(new AssignRuleServiceProvider());
    self::addProvider(new WebhookServiceProvider());
    self::addProvider(new AdminServiceProvider());
    self::addProvider(new PortalServiceProvider());
  }

  /**
   * Integration Providers
   */
  private static function registerIntegrationProviders(Container $container): void {
    self::addProvider(new EnvatoServiceProvider());
    self::addProvider(new LemonSqueezyServiceProvider());
  }

  /**
   * Boot all providers
   */
  public static function bootProviders(Container $container): void {
    foreach (self::$providers as $provider) {

      if (method_exists($provider, 'register')) {
        $provider->register($container);
      }

      if (method_exists($provider, 'boot')) {
        $provider->boot($container);
      }
    }
  }

  /**
   * Add provider
   */
  public static function addProvider(object $provider): void {
    self::$providers[] = $provider;
  }
  /**
   * Reset providers (testing)
   */
  public static function reset(): void {
    self::$providers = [];
  }
}
