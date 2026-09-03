<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Portal\Http\PortalPage;

final class ReactPortalFlowTest extends FlowTest {
  protected static function title(): string {
    return 'React Portal Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var PortalPage $portalPage */
    [$portalPage] = $services;

    global $wp_query, $wp_rewrite;

    PortalPage::registerRewriteRule();

    Assert::true(shortcode_exists('supportbay'), 'The [supportbay] customer portal shortcode is registered.');
    $portalSettings=get_option('sbay_settings',[]);
    $portalSettings=is_array($portalSettings)?$portalSettings:[];
    update_option('sbay_settings',array_merge($portalSettings,['shortcode_mode'=>true]));
    Assert::true(str_contains($portalPage->shortcode(), 'supportbay-customer-portal'), 'Shortcode mode mounts the portal independently of the selected portal page.');
    update_option('sbay_settings',$portalSettings);
    $portalPageId=absint($portalSettings['support_portal_page_id']??0);
    $portalPost=$portalPageId>0?get_post($portalPageId):null;
    if ($portalPost instanceof \WP_Post) {
      Assert::true(($portalPage->postStates([], $portalPost)['supportbay']??'')==='SupportBay','The selected page is identified by a native SupportBay page state.');
    }

    Assert::true(
      count(array_filter(
        $wp_rewrite->extra_rules_top,
        static fn(string $target): bool => str_contains($target, 'sbay_customer_portal=1'),
      )) > 0,
      'The selected customer portal page rewrite is registered.'
    );

    if ($portalPost instanceof \WP_Post) {
      $portalUrl = get_permalink($portalPost);
      $pagePath = '/' . trim((string)wp_parse_url($portalUrl, PHP_URL_PATH), '/');
      $homePath = '/' . trim((string)wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
      if ($homePath !== '/' && ($pagePath === $homePath || str_starts_with($pagePath, $homePath . '/'))) {
        $pagePath = substr($pagePath, strlen($homePath));
      }
      $rewriteBase = trim($pagePath, '/');
      $reloadPaths = array_map(
        static fn(string $route): string => trim($rewriteBase . '/' . $route, '/'),
        ['login', 'register', 'guest-ticket'],
      );
      $portalPatterns = array_keys(array_filter(
        $wp_rewrite->extra_rules_top,
        static fn(string $target): bool => str_contains($target, 'sbay_customer_portal=1'),
      ));

      foreach ($reloadPaths as $reloadPath) {
        Assert::true(
          count(array_filter(
            $portalPatterns,
            static fn(string $pattern): bool => preg_match('#' . $pattern . '#', $reloadPath) === 1,
          )) > 0,
          sprintf('Direct reload route "%s" resolves to the portal.', $reloadPath),
        );
      }
    }

    $previousValue = $wp_query->query_vars['sbay_customer_portal'] ?? null;
    $wp_query->query_vars['sbay_customer_portal'] = '1';
    wp_set_current_user(1);

    $portalPage->enqueueAssets();

    Assert::true(
      wp_style_is('supportbay-customer', 'enqueued'),
      'Compiled customer portal stylesheet is enqueued.'
    );

    Assert::true(
      wp_script_is('supportbay-customer', 'enqueued'),
      'Compiled customer portal application is enqueued.'
    );

    $bootstrap = wp_scripts()
      ->get_data('supportbay-customer', 'before');

    Assert::true(
      is_array($bootstrap) &&
      str_contains(implode('', $bootstrap), 'restNonce') &&
      str_contains(implode('', $bootstrap), 'portalLogoUrl') &&
      str_contains(implode('', $bootstrap), 'logoutUrl') &&
      str_contains(implode('', $bootstrap), 'registrationEnabled') &&
      str_contains(implode('', $bootstrap), 'footerCopyrightText') &&
      str_contains(implode('', $bootstrap), 'removePoweredByBranding') &&
      str_contains(implode('', $bootstrap), 'wordpressAuthEnabled') &&
      str_contains(implode('', $bootstrap), 'wordpressLoginUrl') &&
      str_contains(implode('', $bootstrap), 'wordpressRegistrationUrl') &&
      str_contains(implode('', $bootstrap), 'wordpressProfileEnabled') &&
      str_contains(implode('', $bootstrap), 'wordpressProfileUrl') &&
      str_contains(implode('', $bootstrap), 'ticketListAutoRefreshEnabled') &&
      str_contains(implode('', $bootstrap), 'ticketListAutoRefreshInterval') &&
      str_contains(implode('', $bootstrap), 'fileUploadEnabled') &&
      str_contains(implode('', $bootstrap), 'fileUploadMaxSizeMb') &&
      str_contains(implode('', $bootstrap), 'fileUploadAllowedExtensions') &&
      str_contains(implode('', $bootstrap), 'attachmentPopupPreviewEnabled') &&
      str_contains(implode('', $bootstrap), 'ticketStatusLabels') &&
      str_contains(implode('', $bootstrap), 'resetPasswordUrl') &&
      str_contains(implode('', $bootstrap), 'guestTicketCreationEnabled') &&
      str_contains(implode('', $bootstrap), 'recaptchaSiteKey') &&
      str_contains(implode('', $bootstrap), 'recaptchaLoginEnabled') &&
      str_contains(implode('', $bootstrap), 'purchaseProviderFieldLabel') &&
      str_contains(implode('', $bootstrap), 'oauthLoginProviders') &&
      str_contains(implode('', $bootstrap), 'availabilityNotices'),
      'React bootstrap includes REST authentication configuration.'
    );

    $bootstrapSource = is_array($bootstrap) ? implode('', $bootstrap) : '';
    Assert::true(
      str_contains($bootstrapSource, 'action=logout')
      && str_contains($bootstrapSource, '_wpnonce=')
      && ! str_contains($bootstrapSource, 'amp%3B_wpnonce')
      && ! str_contains($bootstrapSource, 'amp;_wpnonce'),
      'Sign out uses a directly executable WordPress nonce URL without a confirmation step.',
    );

    $authPage = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/react/modules/auth/AuthPage.tsx'
    );
    $copyright = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/react/components/PortalCopyright.tsx'
    );
    $portalApp = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/react/App.tsx'
    );
    $portalLayout = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/react/components/PortalLayout.tsx'
    );
    Assert::true(
      is_string($authPage)
      && str_contains($authPage, 'config.portalLogoUrl')
      && str_contains($authPage, 'apiPost<{ redirect: string }>("auth/login"')
      && str_contains($authPage, 'apiPost<{ redirect: string }>("auth/register"')
      && str_contains($authPage, 'Username or Email Address')
      && str_contains($authPage, 'First Name')
      && str_contains($authPage, 'Last Name')
      && str_contains($authPage, 'Confirm Password')
      && str_contains($authPage, 'config.oauthLoginProviders')
      && str_contains($authPage, 'Login with ${provider.name}')
      && str_contains($authPage, 'Register with ${provider.name}')
      && str_contains($authPage, 'Register')
      && str_contains($authPage, 'Reset Password'),
      'The React portal includes native WordPress login and registration screens.',
    );
    Assert::true(
      is_string($authPage)
      && str_contains($authPage, 'Create Ticket as a Guest')
      && str_contains($authPage, 'portal/guest-tickets')
      && str_contains($authPage, 'Subject')
      && str_contains($authPage, 'Description')
      && str_contains($authPage, 'RichTextEditor')
      && str_contains($authPage, 'FilePicker')
      && str_contains($authPage, 'Returning user? Login')
      && is_string($portalApp)
      && str_contains($portalApp, 'guestTicketCreationEnabled'),
      'The public portal includes the setting-aware guest presales ticket flow.',
    );
    Assert::true(
      is_string($portalApp)
      && str_contains($portalApp, 'config.wordpressAuthEnabled')
      && str_contains($portalApp, 'config.wordpressRegistrationUrl')
      && str_contains($portalApp, 'config.wordpressLoginUrl')
      && str_contains($portalApp, "lazy(() => import('./modules/auth/AuthPage')")
      && str_contains($portalApp, "lazy(() => import('./modules/tickets/TicketDetailPage')")
      && str_contains($portalApp, 'Suspense'),
      'Portal authentication can redirect to native or custom WordPress authentication pages and route components load on demand.',
    );
    Assert::true(
      is_string($portalLayout)
      && str_contains($portalLayout, 'config.portalLogoUrl')
      && str_contains($portalLayout, 'config.wordpressProfileEnabled')
      && str_contains($portalLayout, 'config.wordpressProfileUrl')
      && str_contains($portalLayout, 'config.availabilityNotices')
      && is_string($portalApp)
      && str_contains($portalApp, 'Redirecting to your WordPress profile'),
      'Portal profile navigation can use the native WordPress profile.',
    );
    Assert::true(
      is_string($copyright)
      && str_contains($copyright, 'new Date().getFullYear()')
      && str_contains($copyright, 'href={config.homeUrl}')
      && str_contains($copyright, 'config.removePoweredByBranding')
      && str_contains($copyright, 'Powered by'),
      'Portal copyright uses the current year, linked site name, and SupportBay branding.',
    );

    Assert::true(
      has_filter('rest_pre_serve_request') !== false,
      'Secure attachment streaming hook is registered.'
    );

    $newTicketPage = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/react/modules/tickets/NewTicketPage.tsx'
    );

    Assert::true(
      is_string($newTicketPage)
      && str_contains($newTicketPage, 'portalApi.purchaseProviders()')
      && str_contains($newTicketPage, 'Promise.allSettled')
      && str_contains($newTicketPage, 'ticket.opening_message_id')
      && str_contains($newTicketPage, 'purchase_reference: purchaseReference.trim()')
      && str_contains($newTicketPage, 'portalApi.customFields(categoryId || null)')
      && str_contains($newTicketPage, 'custom_fields: customFieldValues')
      && str_contains($newTicketPage, 'selectedProvider.purchase_field_label')
      && str_contains($newTicketPage, 'required={selectedProvider.license_required}')
      && str_contains($newTicketPage, 'selectedProvider.check_support_expiry')
      && str_contains($newTicketPage, 'categories.length')
      && str_contains($newTicketPage, 'providers.length > 1')
      && str_contains($newTicketPage, 'config.purchaseProviderFieldLabel')
      && ! str_contains($newTicketPage, 'purchase_verification_id'),
      'Ticket creation renders provider-aware optional entitlement and category-aware custom fields.'
    );

    $ticketDetailPage = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/react/modules/tickets/TicketDetailPage.tsx'
    );
    $ticketsPage = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/react/modules/tickets/TicketsPage.tsx'
    );
    $ticketWorkspace = implode('', array_map(
      static fn(string $file): string => (string) file_get_contents(dirname(__DIR__, 2) . $file),
      ['/assets/src/shared/tickets/TicketWorkspace.tsx', '/assets/src/shared/tickets/workspace/TicketQueueTabs.tsx', '/assets/src/shared/tickets/workspace/TicketRow.tsx', '/assets/src/shared/tickets/workspace/TicketPagination.tsx'],
    ));
    $portalStyles = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/react/styles/portal.scss'
    );
    Assert::true(
      is_string($ticketDetailPage)
      && str_contains($ticketDetailPage, "['resolved', 'closed'].includes(detail.ticket.status)")
      && str_contains($ticketDetailPage, 'portalApi.reopenTicket(ticketId)')
      && ! str_contains($ticketDetailPage, 'detail.custom_fields.map')
      && str_contains($ticketDetailPage, 'config.ticketListAutoRefreshEnabled')
      && str_contains($ticketDetailPage, 'loadDetail(true)')
      && str_contains($ticketDetailPage, 'mutationPending.current')
      && str_contains($ticketDetailPage, "field.type === 'checkbox'")
      && str_contains($ticketDetailPage, "field.type === 'url'")
      && str_contains($ticketDetailPage, 'Ticket unavailable')
      && str_contains($ticketDetailPage, "message.author_type === 'agent' ? 'is-support' : 'is-customer'")
      && str_contains($ticketDetailPage, "message.author_type === 'agent' ? 'Support team' : 'You'")
      && str_contains($ticketDetailPage, 'retry={()=>void loadDetail(false)}'),
      'Customer ticket detail is reopenable, auto-refreshes safely, renders read-only custom-field values, and exposes a retryable failure state.'
    );

    Assert::true(
      is_string($ticketsPage)
      && str_contains($ticketsPage, 'show_categories')
      && is_string($ticketWorkspace)
      && str_contains($ticketWorkspace, 'result?.showCategories')
      && str_contains($ticketWorkspace, 'ticket.has_support_reply')
      && str_contains($ticketWorkspace, 'ticket.reply_count')
      && str_contains($ticketWorkspace, 'ticket.latest_reply_excerpt')
      && str_contains($ticketWorkspace, 'Agent Replied')
      && ! str_contains($ticketWorkspace, ': <span className="sbay-ticket-avatar">{ticket.subject.charAt(0)}</span>')
      && is_string($portalStyles)
      && str_contains($portalStyles, 'article.is-support')
      && str_contains($portalStyles, 'border-left: 4px solid var(--sbay-green)')
      && str_contains($portalStyles, 'background: #f1f8f4'),
      'Customer queues hide redundant default taxonomy metadata and support replies are visually distinguished.',
    );

    $profilePage = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/react/modules/profile/ProfilePage.tsx'
    );

    Assert::true(
      is_string($profilePage)
      && str_contains($profilePage, 'portalApi.providerConnections()')
      && str_contains($profilePage, 'Connected providers')
      && str_contains($profilePage, "provider.connected ? 'Reconnect' : 'Connect'")
      && str_contains($profilePage, 'Profile unavailable')
      && str_contains($profilePage, 'retry={()=>void load()}'),
      'Customer profile exposes connected-provider management and a retryable initial-load failure state.'
    );

    if ($previousValue === null) {
      unset($wp_query->query_vars['sbay_customer_portal']);
    } else {
      $wp_query->query_vars['sbay_customer_portal'] = $previousValue;
    }
  }
}
