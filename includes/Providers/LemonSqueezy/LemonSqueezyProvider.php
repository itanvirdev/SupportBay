<?php

declare(strict_types=1);

namespace SupportBay\Providers\LemonSqueezy;

use RuntimeException;
use SupportBay\Core\Integrations\Contracts\ConfigurableIntegrationProvider;
use SupportBay\Core\Integrations\Contracts\IntegrationProvider;
use SupportBay\Core\Integrations\Contracts\PurchaseVerificationProvider;
use SupportBay\Core\Integrations\Data\ProviderConfigurationField;
use SupportBay\Core\Integrations\Data\PurchaseVerificationData;
use SupportBay\Modules\Providers\Enums\ProviderCategory;
use SupportBay\Providers\LemonSqueezy\Api\LemonSqueezyApiClient;

final class LemonSqueezyProvider implements IntegrationProvider, ConfigurableIntegrationProvider, PurchaseVerificationProvider {
  public function __construct(private readonly LemonSqueezyApiClient $client) {}
  public function slug(): string { return 'lemonsqueezy'; }
  public function name(): string { return 'Lemon Squeezy'; }
  public function category(): ProviderCategory { return ProviderCategory::MARKETPLACE; }
  public function version(): string { return '1.0.0'; }
  public function boot(): void {}

  /** @return ProviderConfigurationField[] */
  public function configurationFields(): array {
    return [
      new ProviderConfigurationField(key: 'purchase_verification_enabled', label: 'Click to enable', type: 'toggle', group: 'main'),
      new ProviderConfigurationField(key: 'store_id', label: 'Lemon Squeezy Store ID', required: true, description: 'Only license keys issued by this Lemon Squeezy store can create support tickets.', group: 'main', requiredWhen: 'purchase_verification_enabled'),
      new ProviderConfigurationField(key: 'purchase_field_label', label: 'Purchase Code/Key Field Label', defaultValue: 'Lemon Squeezy License Key', group: 'main'),
      new ProviderConfigurationField(key: 'purchase_provider_option_label', label: 'Purchase Provider Option Label', defaultValue: 'Lemon Squeezy', description: 'The label customers see for Lemon Squeezy in the Purchase Provider dropdown.', group: 'main'),
      new ProviderConfigurationField(key: 'license_required', label: 'Enable License Required', type: 'toggle', defaultValue: '1', group: 'main'),
      new ProviderConfigurationField(key: 'check_support_expiry', label: 'Check Support Expiry', type: 'toggle', defaultValue: '0', group: 'main'),
      new ProviderConfigurationField(key: 'custom_support_expiry_enabled', label: 'Use Custom Support Expiry', type: 'toggle', defaultValue: '0', description: 'Calculate support from the Lemon Squeezy license creation date. Useful for lifetime licenses with a limited support term.', group: 'main'),
      new ProviderConfigurationField(key: 'custom_support_expiry_amount', label: 'Support Duration', required: true, description: 'Number of days, months, or years of support from the license creation date.', group: 'main', requiredWhen: 'custom_support_expiry_enabled'),
      new ProviderConfigurationField(key: 'custom_support_expiry_unit', label: 'Support Duration Unit', defaultValue: 'years', group: 'main'),
    ];
  }

  /** @param array<string, mixed> $context */
  public function verifyPurchase(string $reference, array $context = []): PurchaseVerificationData {
    $licenseKey = trim($reference);
    if ($licenseKey === '') throw new RuntimeException('Lemon Squeezy License Key is required.');
    $payload = $this->client->validateLicense($licenseKey);
    $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
    $license = is_array($payload['license_key'] ?? null) ? $payload['license_key'] : [];
    $configuredStoreId = trim((string) ($context['store_id'] ?? ''));
    if ($configuredStoreId !== '' && (string) ($meta['store_id'] ?? '') !== $configuredStoreId) {
      throw new RuntimeException('This license key does not belong to the configured Lemon Squeezy store.');
    }
    $expiry = $this->earliestExpiry(
      $this->customSupportExpiry($license, $context),
      $this->nullableDate($license['expires_at'] ?? null),
    );
    $status = sanitize_key((string) ($license['status'] ?? ''));
    if (in_array($status, ['expired', 'disabled'], true) || ! empty($license['disabled'])) {
      throw new RuntimeException('This Lemon Squeezy license key is no longer active.');
    }
    $customerEmail = sanitize_email((string) ($meta['customer_email'] ?? ''));
    return new PurchaseVerificationData(
      provider: $this->slug(), providerReference: $licenseKey,
      providerCustomerReference: $customerEmail !== '' ? $customerEmail : null,
      productId: isset($meta['product_id']) ? (string) $meta['product_id'] : null,
      productName: $this->nullableText($meta['product_name'] ?? null),
      licenseType: $this->nullableText($meta['variant_name'] ?? null),
      supportExpiresAt: $expiry,
      purchasedAt: $this->nullableDate($license['created_at'] ?? null),
      status: 'verified', snapshot: $this->snapshot($payload),
    );
  }

  private function nullableText(mixed $value): ?string { $value = sanitize_text_field((string) $value); return $value !== '' ? $value : null; }
  private function nullableDate(mixed $value): ?string { $value = sanitize_text_field((string) $value); return $value !== '' ? $value : null; }
  private function earliestExpiry(?string $customExpiry, ?string $licenseExpiry): ?string {
    if ($customExpiry === null) return $licenseExpiry;
    if ($licenseExpiry === null) return $customExpiry;
    try {
      return new \DateTimeImmutable($licenseExpiry) < new \DateTimeImmutable($customExpiry)
        ? $licenseExpiry
        : $customExpiry;
    } catch (\Exception) {
      return $customExpiry;
    }
  }
  /** @param array<string,mixed> $license @param array<string,mixed> $context */
  private function customSupportExpiry(array $license, array $context): ?string {
    if (! filter_var($context['custom_support_expiry_enabled'] ?? false, FILTER_VALIDATE_BOOL)) return null;
    $amount = absint($context['custom_support_expiry_amount'] ?? 0);
    $unit = sanitize_key((string) ($context['custom_support_expiry_unit'] ?? 'years'));
    if ($amount < 1 || ! in_array($unit, ['days', 'months', 'years'], true)) throw new RuntimeException('A valid custom support duration is required.');
    $createdAt = $this->nullableDate($license['created_at'] ?? null);
    if ($createdAt === null) throw new RuntimeException('Lemon Squeezy did not return the license creation date.');
    try {
      return (new \DateTimeImmutable($createdAt))->modify(sprintf('+%d %s', $amount, $unit))->format('Y-m-d H:i:s');
    } catch (\Exception) {
      throw new RuntimeException('Lemon Squeezy returned an invalid license creation date.');
    }
  }
  /** @param array<string,mixed> $payload @return array<string,mixed> */
  private function snapshot(array $payload): array { if (isset($payload['license_key']['key'])) $payload['license_key']['key'] = '***'; return $payload; }
}
