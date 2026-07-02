<?php

declare(strict_types=1);

namespace SupportBay\Core\Integrations\Contracts;

use SupportBay\Modules\Providers\Enums\ProviderCategory;

/**
 * Runtime Integration Contract.
 *
 * Every external integration must implement this contract.
 *
 * Examples:
 *
 * - Envato
 * - Easy Digital Downloads
 * - WooCommerce
 * - Freemius
 * - Paddle
 * - Lemon Squeezy
 * - OpenAI
 * - Gemini
 * - SMTP
 * - Slack
 */
interface IntegrationProvider {
  /**
   * Unique integration identifier.
   *
   * Examples:
   *
   * envato
   * edd
   * woocommerce
   * openai
   */
  public function slug(): string;

  /**
   * Human-readable integration name.
   *
   * Examples:
   *
   * Envato
   * WooCommerce
   * OpenAI
   */
  public function name(): string;

  /**
   * Integration category.
   */
  public function category(): ProviderCategory;

  /**
   * Integration version.
   */
  public function version(): string;

  /**
   * Boot the integration.
   *
   * Called after the integration has been registered by the
   * IntegrationManager.
   */
  public function boot(): void;
}
