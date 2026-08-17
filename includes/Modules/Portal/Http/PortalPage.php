<?php

declare(strict_types=1);

namespace SupportBay\Modules\Portal\Http;

use SupportBay\Modules\Auth\Services\MagicLoginService;

final class PortalPage {
  private const QUERY_VAR = 'sbay_customer_portal';

  public function __construct(
    private readonly MagicLoginService $magicLogin,
  ) {
  }

  /**
   * Register the public customer portal rewrite.
   */
  public static function registerRewriteRule(): void {
    add_rewrite_rule(
      '^support(?:/.*)?$',
      'index.php?' . self::QUERY_VAR . '=1',
      'top'
    );
  }

  /**
   * Register WordPress hooks.
   */
  public function register(): void {
    add_action('init', [self::class, 'registerRewriteRule']);
    add_filter('query_vars', [$this, 'queryVars']);
    add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    add_action('template_redirect', [$this, 'render']);
  }

  /**
   * Register the portal query variable.
   *
   * @param string[] $variables
   * @return string[]
   */
  public function queryVars(array $variables): array {
    $variables[] = self::QUERY_VAR;

    return $variables;
  }

  /**
   * Load the customer bundle only on the portal route.
   */
  public function enqueueAssets(): void {
    if (! $this->isPortal()) {
      return;
    }

    wp_enqueue_editor();

    $scriptPath = SBAY_PLUGIN_PATH . 'assets/dist/supportbay-customer.js';
    $stylePath = SBAY_PLUGIN_PATH . 'assets/dist/supportbay-customer.css';

    if (! file_exists($scriptPath) || ! file_exists($stylePath)) {
      return;
    }

    wp_enqueue_style(
      'supportbay-customer',
      SBAY_PLUGIN_URL . 'assets/dist/supportbay-customer.css',
      [],
      (string) filemtime($stylePath),
    );

    wp_enqueue_script(
      'supportbay-customer',
      SBAY_PLUGIN_URL . 'assets/dist/supportbay-customer.js',
      [],
      (string) filemtime($scriptPath),
      true,
    );

    wp_add_inline_script(
      'supportbay-customer',
      'window.supportBayPortal = ' . wp_json_encode([
        'restUrl'   => esc_url_raw(rest_url('sbay/v1/')),
        'restNonce' => wp_create_nonce('wp_rest'),
        'portalUrl' => esc_url_raw(home_url('/support/')),
        'logoutUrl' => esc_url_raw(wp_logout_url(home_url('/support/'))),
        'siteName'  => sanitize_text_field(get_bloginfo('name')),
        'homeUrl' => esc_url_raw(home_url('/')),
        'resetPasswordUrl' => esc_url_raw(wp_lostpassword_url(home_url('/support/login/'))),
        'registrationEnabled' => (bool) get_option('users_can_register'),
        'authenticated' => is_user_logged_in(),
      ]) . ';',
      'before',
    );
  }

  /**
   * Render the isolated React mount document.
   */
  public function render(): void {
    if (! $this->isPortal()) {
      return;
    }

    if (! is_user_logged_in()) {
      $this->handleMagicLogin();
    }

    status_header(200);
    nocache_headers();

    ?><!doctype html>
    <html <?php language_attributes(); ?>>
      <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo esc_html__('Customer Portal — SupportBay', 'supportbay'); ?></title>
        <?php wp_head(); ?>
      </head>
      <body <?php body_class('supportbay-portal'); ?>>
        <?php wp_body_open(); ?>
        <div id="supportbay-customer-portal"></div>
        <noscript>
          <?php echo esc_html__('JavaScript is required to use the SupportBay customer portal.', 'supportbay'); ?>
        </noscript>
        <?php wp_footer(); ?>
      </body>
    </html><?php

    exit;
  }

  private function isPortal(): bool {
    return (string) get_query_var(self::QUERY_VAR) === '1';
  }

  private function handleMagicLogin(): ?string {
    $plainToken = sanitize_text_field(
      wp_unslash($_GET['sbay_magic_token'] ?? '')
    );

    if ($plainToken === '') {
      return null;
    }

    $token = $this->magicLogin->login($plainToken);

    if (! $token) {
      return __('This sign-in link is invalid or has expired.', 'supportbay');
    }

    wp_safe_redirect(
      $this->portalRedirect($token->redirectTo())
    );
    exit;
  }

  private function portalRedirect(?string $redirectTo): string {
    $portalUrl = home_url('/support/');

    if (! $redirectTo) {
      return $portalUrl;
    }

    $path = wp_parse_url($redirectTo, PHP_URL_PATH);

    if (! is_string($path) || ! preg_match('#^/support(?:/|$)#', $path)) {
      return $portalUrl;
    }

    return home_url($path);
  }

}
