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

    Assert::true(
      isset($wp_rewrite->extra_rules_top['^support(?:/.*)?$']),
      'Customer portal rewrite is registered.'
    );

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
      str_contains(implode('', $bootstrap), 'logoutUrl'),
      'React bootstrap includes REST authentication configuration.'
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
      && str_contains($newTicketPage, 'purchase_reference: purchaseReference.trim()')
      && str_contains($newTicketPage, 'portalApi.customFields(departmentId)')
      && str_contains($newTicketPage, 'custom_fields: customFieldValues')
      && str_contains($newTicketPage, 'Purchase Code/Key')
      && ! str_contains($newTicketPage, 'purchase_verification_id'),
      'Ticket creation requires entitlement and renders department custom fields.'
    );

    $ticketDetailPage = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/react/modules/tickets/TicketDetailPage.tsx'
    );
    Assert::true(
      is_string($ticketDetailPage)
      && str_contains($ticketDetailPage, "['resolved', 'closed'].includes(detail.ticket.status)")
      && str_contains($ticketDetailPage, 'portalApi.reopenTicket(ticketId)')
      && str_contains($ticketDetailPage, 'detail.custom_fields.map')
      && str_contains($ticketDetailPage, 'Additional information')
      && str_contains($ticketDetailPage, "field.type === 'checkbox'")
      && str_contains($ticketDetailPage, "field.type === 'url'"),
      'Customer ticket detail is reopenable and renders safe read-only custom-field values.'
    );

    $profilePage = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/react/modules/profile/ProfilePage.tsx'
    );

    Assert::true(
      is_string($profilePage)
      && str_contains($profilePage, 'portalApi.providerConnections()')
      && str_contains($profilePage, 'Connected providers')
      && str_contains($profilePage, "provider.connected ? 'Reconnect' : 'Connect'"),
      'Customer profile exposes connected-provider management.'
    );

    if ($previousValue === null) {
      unset($wp_query->query_vars['sbay_customer_portal']);
    } else {
      $wp_query->query_vars['sbay_customer_portal'] = $previousValue;
    }
  }
}
