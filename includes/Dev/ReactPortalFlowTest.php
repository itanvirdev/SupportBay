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

    if ($previousValue === null) {
      unset($wp_query->query_vars['sbay_customer_portal']);
    } else {
      $wp_query->query_vars['sbay_customer_portal'] = $previousValue;
    }
  }
}
