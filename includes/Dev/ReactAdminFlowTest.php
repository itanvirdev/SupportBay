<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Admin\AdminPage;

final class ReactAdminFlowTest extends FlowTest {
  protected static function title(): string {
    return 'React Admin Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var AdminPage $adminPage */
    [$adminPage] = $services;

    echo "🚀 Starting SupportBay React Admin Flow Test...\n\n";

    wp_set_current_user(1);

    Assert::true(
      has_action('admin_menu', [$adminPage, 'registerMenu']) !== false,
      'SupportBay administrator menu hook is registered.'
    );

    $adminPage->enqueueAssets('toplevel_page_supportbay');

    Assert::true(
      wp_style_is('supportbay-admin', 'enqueued'),
      'Compiled administrator stylesheet is enqueued.'
    );
    Assert::true(
      wp_script_is('supportbay-admin', 'enqueued'),
      'Compiled React administrator application is enqueued.'
    );

    $bootstrap = wp_scripts()->get_data('supportbay-admin', 'before');

    Assert::true(
      is_array($bootstrap)
      && str_contains(implode('', $bootstrap), 'restNonce')
      && str_contains(implode('', $bootstrap), 'adminUrl'),
      'Administrator bootstrap includes authenticated API configuration.'
    );

    ob_start();
    $adminPage->render();
    $markup = (string) ob_get_clean();

    Assert::true(
      str_contains($markup, 'supportbay-admin-app'),
      'Administrator page renders the React mount point.'
    );
  }
}
