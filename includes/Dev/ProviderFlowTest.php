<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Providers\Enums\ProviderCategory;
use SupportBay\Modules\Providers\Enums\ProviderStatus;
use SupportBay\Modules\Providers\Services\ProviderService;
use SupportBay\Modules\Providers\Services\ProviderConfiguration;
use SupportBay\Core\Security\SecretCipher;
use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Modules\Providers\Services\ProviderConnectionService;
use SupportBay\Providers\Envato\Api\EnvatoApiClient;
use SupportBay\Providers\Envato\Services\EnvatoOAuthService;
use SupportBay\Providers\Envato\Services\EnvatoCustomerService;

final class ProviderFlowTest extends FlowTest {
  /**
   * Test title.
   */
  protected static function title(): string {
    return 'Provider Flow Test';
  }

  /**
   * Execute flow.
   */
  protected static function execute(...$services): void {
    /** @var ProviderService $providerService */
    /** @var ProviderConfiguration $configuration */
    /** @var SecretCipher $cipher */
    /** @var IntegrationManager $integrations */
    /** @var ProviderConnectionService $connections */
    [$providerService, $configuration, $cipher, $integrations, $connections] = $services;

    echo "🚀 Starting SupportBay Provider Flow Test...\n\n";

    $envatoClientSource = (string) file_get_contents(
      dirname(__DIR__) . '/Providers/Envato/Api/EnvatoApiClient.php'
    );
    $envatoOAuthSource = (string) file_get_contents(
      dirname(__DIR__) . '/Providers/Envato/Services/EnvatoOAuthService.php'
    );
    $oauthRoutesSource = (string) file_get_contents(
      dirname(__DIR__) . '/Modules/Auth/Http/OAuthRoutes.php'
    );
    Assert::true(
      str_contains($envatoClientSource, "trim(\$accessToken) !== ''")
      && str_contains($envatoClientSource, 'application/x-www-form-urlencoded')
      && str_contains($envatoClientSource, "'',\n      \$body,\n      true,")
      && str_contains($envatoClientSource, "\$formEncoded ? \$body")
      && str_contains($envatoClientSource, "preg_replace('/^\\xEF\\xBB\\xBF/'")
      && str_contains($envatoClientSource, 'parse_str($raw, $form)')
      && str_contains($envatoClientSource, 'responseExcerpt')
      && str_contains($envatoClientSource, 'WP_DEBUG')
      && str_contains($envatoOAuthSource, 'client->postForm('),
      'Envato OAuth token requests use one form-body client authentication mechanism without an empty bearer header.',
    );

    Assert::true(
      str_contains($oauthRoutesSource, 'Sign in to an Envato Marketplace site')
      && str_contains($oauthRoutesSource, 'Login with Envato or Register with Envato again.'),
      'Envato Marketplace-only accounts receive an actionable activation message.',
    );

    $authorizationUrl = (new EnvatoOAuthService(new EnvatoApiClient()))->authorizationUrl(
      'oauth-client-id',
      'https://example.test/support/?sbayenvato=1',
      '',
    );
    $authorizationQuery = (string) wp_parse_url($authorizationUrl, PHP_URL_QUERY);
    parse_str($authorizationQuery, $authorizationParameters);

    Assert::true(
      str_starts_with($authorizationUrl, 'https://api.envato.com/authorization?')
      && ($authorizationParameters['response_type'] ?? '') === 'code'
      && ($authorizationParameters['client_id'] ?? '') === 'oauth-client-id'
      && ($authorizationParameters['redirect_uri'] ?? '') === 'https://example.test/support/?sbayenvato=1'
      && ! isset($authorizationParameters['state'])
      && ! isset($authorizationParameters['scope']),
      'Envato authorization URLs use the exact Support Genix-compatible parameter set.',
    );

    $capturedUrl = '';
    $capturedArgs = [];
    $interceptTokenRequest = static function (
      mixed $response,
      array $args,
      string $url,
    ) use (&$capturedUrl, &$capturedArgs): mixed {
      if ($url !== 'https://api.envato.com/token') {
        return $response;
      }

      $capturedUrl = $url;
      $capturedArgs = $args;

      return [
        'headers' => [],
        'body' => wp_json_encode([
          'access_token' => 'mock-access-token',
          'token_type' => 'bearer',
          'expires_in' => 3600,
        ]),
        'response' => ['code' => 200, 'message' => 'OK'],
        'cookies' => [],
        'filename' => null,
      ];
    };

    add_filter('pre_http_request', $interceptTokenRequest, 10, 3);

    try {
      $oauthResponse = (new EnvatoOAuthService(new EnvatoApiClient()))->exchangeCode(
        'oauth-client-id',
        'secret-application-key',
        'single-use-code',
      );
    } finally {
      remove_filter('pre_http_request', $interceptTokenRequest, 10);
    }

    Assert::true(
      ($capturedArgs['method'] ?? '') === 'POST'
      && empty($capturedArgs['headers']['Authorization'])
      && $capturedUrl === 'https://api.envato.com/token'
      && is_array($capturedArgs['body'] ?? null)
      && ($capturedArgs['body']['grant_type'] ?? '') === 'authorization_code'
      && ($capturedArgs['body']['client_id'] ?? '') === 'oauth-client-id'
      && ($capturedArgs['body']['client_secret'] ?? '') === 'secret-application-key'
      && ($capturedArgs['body']['code'] ?? '') === 'single-use-code'
      && ! isset($capturedArgs['body']['redirect_uri'])
      && ($oauthResponse['access_token'] ?? '') === 'mock-access-token',
      'Envato OAuth sends each application credential exactly once and unchanged.',
    );

    $profileRequests = [];
    $profileRequestArgs = [];
    $interceptProfileRequest = static function (
      mixed $response,
      array $args,
      string $url,
    ) use (&$profileRequests, &$profileRequestArgs): mixed {
      $responses = [
        'https://api.envato.com/v1/market/private/user/account.json' => [
          'account' => [
            'firstname' => 'Envato',
            'surname' => 'Customer',
            'image' => 'https://example.com/avatar.jpg',
            'country' => 'BD',
          ],
        ],
        'https://api.envato.com/v1/market/private/user/email.json' => [
          'email' => 'customer@example.com',
        ],
        'https://api.envato.com/v1/market/private/user/username.json' => [
          'username' => 'envato-customer',
        ],
      ];

      if (! isset($responses[$url])) {
        return $response;
      }

      $profileRequests[] = $url;
      $profileRequestArgs[] = $args;

      return [
        'headers' => [],
        'body' => wp_json_encode($responses[$url]),
        'response' => ['code' => 200, 'message' => 'OK'],
        'cookies' => [],
        'filename' => null,
      ];
    };

    add_filter('pre_http_request', $interceptProfileRequest, 10, 3);

    try {
      $customerService = new EnvatoCustomerService(new EnvatoApiClient());
      $envatoProfile = $customerService->profile('mock-access-token');
    } finally {
      remove_filter('pre_http_request', $interceptProfileRequest, 10);
    }

    Assert::true(
      count($profileRequests) === 3
      && count($profileRequestArgs) === 3
      && ($profileRequestArgs[0]['timeout'] ?? 0) === 120
      && array_keys($profileRequestArgs[0]['headers'] ?? []) === ['Authorization']
      && $customerService->username($envatoProfile) === 'envato-customer'
      && $customerService->email($envatoProfile) === 'customer@example.com'
      && $customerService->displayName($envatoProfile) === 'Envato Customer'
      && $customerService->country($envatoProfile) === 'BD',
      'Envato OAuth assembles customer identity from the account, email, and username endpoints.',
    );

    $interceptMarketplaceProfileFailure = static function (
      mixed $response,
      array $args,
      string $url,
    ): mixed {
      if ($url === 'https://api.envato.com/v1/market/private/user/email.json') {
        return [
          'headers' => [],
          'body' => wp_json_encode(['email' => 'account@example.com']),
          'response' => ['code' => 200, 'message' => 'OK'],
          'cookies' => [],
          'filename' => null,
        ];
      }

      if (in_array($url, [
        'https://api.envato.com/v1/market/private/user/account.json',
        'https://api.envato.com/v1/market/private/user/username.json',
      ], true)) {
        return [
          'headers' => [],
          'body' => wp_json_encode(['error' => 'User not found.']),
          'response' => ['code' => 404, 'message' => 'Not Found'],
          'cookies' => [],
          'filename' => null,
        ];
      }

      return $response;
    };

    add_filter('pre_http_request', $interceptMarketplaceProfileFailure, 10, 3);

    try {
      $emailOnlyProfile = $customerService->profile('mock-access-token');
    } finally {
      remove_filter('pre_http_request', $interceptMarketplaceProfileFailure, 10);
    }

    Assert::true(
      $customerService->email($emailOnlyProfile) === 'account@example.com'
      && $customerService->username($emailOnlyProfile) === null,
      'Envato OAuth accepts a valid account email when Marketplace profile enrichment is unavailable.',
    );

    $envato = $providerService->findBySlug('envato');
    Assert::notNull(
      $envato,
      'The built-in Envato integration has a persistent provider record.'
    );
    Assert::equals(
      ProviderStatus::DISABLED,
      $envato->status(),
      'Envato remains disabled until an administrator enables it.'
    );

    $providerSlug = 'fake-configurable';
    $existing = $providerService->findBySlug($providerSlug);

    if ($existing) {
      $providerService->delete($existing->id());
    }

    if (! $integrations->has($providerSlug)) {
      $integrations->register(new FakeConfigurableProvider());
    }

    // -------------------------------------------------
    // Create Provider
    // -------------------------------------------------

    $providerId = $providerService->create([
      'slug'     => $providerSlug,
      'name'     => 'Flow Test Provider',
      'category' => ProviderCategory::MARKETPLACE,
      'version'  => '1.0.0',
      'status'   => ProviderStatus::DISABLED,
    ]);

    Assert::true(
      $providerId > 0,
      'Provider created.'
    );

    // -------------------------------------------------
    // Retrieve Provider
    // -------------------------------------------------

    $provider = $providerService->find($providerId);

    Assert::notNull(
      $provider,
      'Provider retrieved.'
    );

    Assert::equals(
      $providerId,
      $provider->id(),
      'Provider ID matches.'
    );

    Assert::equals(
      $providerSlug,
      $provider->slug(),
      'Slug stored.'
    );

    Assert::equals(
      'Flow Test Provider',
      $provider->name(),
      'Name stored.'
    );

    $renamed = $providerService->rename($providerId, 'Marketplace Option');
    Assert::equals('Marketplace Option', $renamed->name(), 'Provider ticket-form option label is editable.');

    Assert::equals(
      ProviderCategory::MARKETPLACE,
      $provider->category(),
      'Category stored.'
    );

    Assert::equals(
      ProviderStatus::DISABLED,
      $provider->status(),
      'Initial status stored.'
    );

    Assert::equals(
      '1.0.0',
      $provider->version(),
      'Version stored.'
    );

    $configuration->update($providerSlug, [
      'client_id' => 'demo-client',
      'client_secret' => 'provider-secret',
      'redirect_uri' => 'https://example.com/callback',
    ]);

    $provider = $providerService->find($providerId);

    Assert::true($provider->hasSettings(), 'Validated settings stored.');

    Assert::equals(
      'demo-client',
      $configuration->clientId($providerSlug),
      'Hydrated provider settings are available through configuration.'
    );

    $encrypted = $cipher->encrypt('provider-secret');

    Assert::true(
      $encrypted !== 'provider-secret'
      && $cipher->decrypt($encrypted) === 'provider-secret',
      'Provider secrets encrypt and decrypt safely.'
    );

    Assert::true(
      $provider->settings()['client_secret'] !== 'provider-secret'
      && $configuration->clientSecret($providerSlug) === 'provider-secret'
      && $configuration->form($providerSlug)['fields'][1]['value'] === '',
      'Stored provider secrets are encrypted and omitted from configuration forms.'
    );

    $configuration->update($providerSlug, [
      'client_id' => 'updated-client',
      'client_secret' => '',
      'redirect_uri' => 'https://example.com/callback',
    ]);

    Assert::equals(
      'provider-secret',
      $configuration->clientSecret($providerSlug),
      'Blank secret updates preserve the existing credential.'
    );

    Assert::true(
      $connections->supports($providerSlug)
      && $connections->test($providerSlug)->isSuccessful(),
      'Provider-declared connection test succeeds with valid configuration.'
    );

    $provider = $providerService->find($providerId);

    Assert::notNull(
      $provider->lastConnectedAt(),
      'Successful connection test records provider health.'
    );

    $configuration->update($providerSlug, [
      'client_id' => 'fail-connection',
      'client_secret' => '',
      'redirect_uri' => 'https://example.com/callback',
    ]);

    Assert::true(
      ! $connections->test($providerSlug)->isSuccessful(),
      'Provider-declared connection failure is normalized.'
    );

    $provider = $providerService->find($providerId);

    Assert::true(
      $provider->hasError(),
      'Failed connection test records safe provider health.'
    );

    Assert::true(
      $provider->isDisabled(),
      'Provider initially disabled.'
    );

    // -------------------------------------------------
    // Enable Provider
    // -------------------------------------------------

    $providerService->enable($providerId);

    $provider = $providerService->find($providerId);

    Assert::true(
      $provider->isEnabled(),
      'Provider enabled.'
    );

    // -------------------------------------------------
    // Record Successful Connection
    // -------------------------------------------------

    $providerService->connected($providerId);

    $provider = $providerService->find($providerId);

    Assert::notNull(
      $provider->lastConnectedAt(),
      'Connection timestamp stored.'
    );

    // -------------------------------------------------
    // Record Connection Failure
    // -------------------------------------------------

    $providerService->connectionFailed(
      $providerId,
      'Invalid API credentials.'
    );

    $provider = $providerService->find($providerId);

    Assert::equals(
      'Invalid API credentials.',
      $provider->lastError(),
      'Connection error stored.'
    );

    Assert::true(
      $provider->hasError(),
      'Provider reports connection error.'
    );

    // -------------------------------------------------
    // Disable Provider
    // -------------------------------------------------

    $providerService->disable($providerId);

    $provider = $providerService->find($providerId);

    Assert::true(
      $provider->isDisabled(),
      'Provider disabled.'
    );

    Assert::true(
      $providerService->delete($providerId),
      'Test provider removed.'
    );

    echo "\n🎯 Provider Flow Test Passed.\n";
  }
}
