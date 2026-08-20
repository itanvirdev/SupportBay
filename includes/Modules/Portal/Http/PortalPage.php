<?php

declare(strict_types=1);

namespace SupportBay\Modules\Portal\Http;

use SupportBay\Modules\Auth\Services\MagicLoginService;
use SupportBay\Modules\Settings\Services\GeneralSettingsService;

final class PortalPage {
  private const QUERY_VAR = 'sbay_customer_portal';
  private const SHORTCODE_PAGE_QUERY_VAR = 'sbay_shortcode_portal_page';
  private const REWRITE_VERSION = '3';

  public function __construct(
    private readonly MagicLoginService $magicLogin,
    private readonly GeneralSettingsService $settings,
  ) {
  }

  /**
   * Register the public customer portal rewrite.
   */
  public static function registerRewriteRule(): void {
    $settings = get_option('sbay_settings', []);
    $settings = is_array($settings) ? $settings : [];
    $selectedPageId = absint($settings['support_portal_page_id'] ?? 0);

    self::registerPageRewrite($selectedPageId);

    if (! (bool) ($settings['shortcode_mode'] ?? false)) {
      return;
    }

    foreach (get_pages(['post_status' => 'publish']) as $page) {
      if ($page->ID === $selectedPageId || ! has_shortcode($page->post_content, 'supportbay')) {
        continue;
      }

      self::registerPageRewrite($page->ID, true);
    }
  }

  /**
   * Register WordPress hooks.
   */
  public function register(): void {
    add_action('init', [self::class, 'registerRewriteRule']);
    add_action('init', [$this, 'maybeFlushRewriteRules'], 99);
    add_filter('query_vars', [$this, 'queryVars']);
    add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    add_action('template_redirect', [$this, 'render']);
    add_shortcode('supportbay', [$this, 'shortcode']);
    add_filter('display_post_states', [$this, 'postStates'], 10, 2);
  }

  public function maybeFlushRewriteRules(): void {
    if ((string)get_option('sbay_portal_rewrite_version','')===self::REWRITE_VERSION) { return; }
    flush_rewrite_rules(false);
    update_option('sbay_portal_rewrite_version',self::REWRITE_VERSION,false);
  }

  /**
   * Register the portal query variable.
   *
   * @param string[] $variables
   * @return string[]
   */
  public function queryVars(array $variables): array {
    $variables[] = self::QUERY_VAR;
    $variables[] = self::SHORTCODE_PAGE_QUERY_VAR;

    return $variables;
  }

  /** @param array<string, string> $states @return array<string, string> */
  public function postStates(array $states, \WP_Post $post): array {
    if ($post->post_type === 'page' && $post->ID === $this->settings->portalPageId()) {
      $states['supportbay'] = __('SupportBay', 'supportbay');
    }
    return $states;
  }

  /**
   * Load the customer bundle only on the portal route.
   */
  public function enqueueAssets(): void {
    if (! $this->isPortalRequest()) {
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
    wp_add_inline_style('supportbay-customer',$this->settings->supportBayCss());

    wp_enqueue_script(
      'supportbay-customer',
      SBAY_PLUGIN_URL . 'assets/dist/supportbay-customer.js',
      [],
      (string) filemtime($scriptPath),
      true,
    );

    $portalUrl=$this->currentPortalUrl();
    wp_add_inline_script(
      'supportbay-customer',
      'window.supportBayPortal = ' . wp_json_encode([
        'restUrl'   => esc_url_raw(rest_url('sbay/v1/')),
        'restNonce' => wp_create_nonce('wp_rest'),
        'portalUrl' => esc_url_raw($portalUrl),
        'logoutUrl' => esc_url_raw(wp_logout_url($portalUrl)),
        'siteName'  => sanitize_text_field(get_bloginfo('name')),
        'portalLogoUrl' => esc_url_raw($this->settings->portalLogoUrl()),
        'homeUrl' => esc_url_raw(home_url('/')),
        'footerCopyrightText' => $this->settings->footerCopyrightText(),
        'removePoweredByBranding' => $this->settings->removePoweredByBranding(),
        'wordpressAuthEnabled' => $this->settings->wordpressAuthEnabled(),
        'wordpressLoginUrl' => esc_url_raw($this->settings->wordpressLoginUrl($portalUrl)),
        'wordpressRegistrationUrl' => esc_url_raw($this->settings->wordpressRegistrationUrl()),
        'wordpressProfileEnabled' => $this->settings->wordpressProfileEnabled(),
        'wordpressProfileUrl' => esc_url_raw(admin_url('profile.php')),
        'ticketListAutoRefreshEnabled' => $this->settings->ticketListAutoRefreshEnabled(),
        'ticketListAutoRefreshInterval' => $this->settings->ticketListAutoRefreshInterval(),
        'fileUploadEnabled' => $this->settings->fileUploadEnabled(),
        'fileUploadMaxSizeMb' => $this->settings->fileUploadMaxSizeMb(),
        'fileUploadAllowedExtensions' => $this->settings->allowedFileExtensions(),
        'attachmentPopupPreviewEnabled' => $this->settings->attachmentPopupPreviewEnabled(),
        'ticketStatusLabels' => $this->settings->ticketStatusLabels(),
        'resetPasswordUrl' => esc_url_raw(wp_lostpassword_url(trailingslashit($portalUrl) . 'login/')),
        'registrationEnabled' => $this->settings->registrationEnabled(),
        'authenticated' => is_user_logged_in(),
      ]) . ';',
      'before',
    );
  }

  /**
   * Render the isolated React mount document.
   */
  public function render(): void {
    if (! $this->isPortalRequest()) {
      return;
    }

    if ($this->settings->shortcodeMode() && ! $this->isPortal() && ! $this->isSelectedPage() && $this->hasPortalShortcode()) {
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

  private function isPortalRequest(): bool {
    return $this->isPortal()
      || $this->isSelectedPage()
      || ($this->settings->shortcodeMode() && $this->hasPortalShortcode());
  }

  private function isSelectedPage(): bool {
    $pageId=$this->settings->portalPageId();
    return $pageId>0&&is_page($pageId);
  }

  private function hasPortalShortcode(): bool {
    global $post;
    return $post instanceof \WP_Post && has_shortcode($post->post_content, 'supportbay');
  }

  private function currentPortalUrl(): string {
    global $post;

    $shortcodePageId = absint(get_query_var(self::SHORTCODE_PAGE_QUERY_VAR));
    if ($this->settings->shortcodeMode() && $shortcodePageId > 0) {
      $url = get_permalink($shortcodePageId);
      if (is_string($url) && $url !== '') { return $url; }
    }

    if ($this->settings->shortcodeMode() && ! $this->isSelectedPage() && $this->hasPortalShortcode() && $post instanceof \WP_Post) {
      $url=get_permalink($post);
      if (is_string($url)&&$url!=='') { return $url; }
    }
    return $this->settings->portalUrl();
  }

  public function shortcode(): string {
    if (! $this->settings->shortcodeMode()) {
      return '';
    }

    return '<div id="supportbay-customer-portal"></div><noscript>'.esc_html__('JavaScript is required to use the SupportBay customer portal.','supportbay').'</noscript>';
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
    $portalUrl = $this->currentPortalUrl();

    if (! $redirectTo) {
      return $portalUrl;
    }

    $path = wp_parse_url($redirectTo, PHP_URL_PATH);

    $portalPath=(string)wp_parse_url($portalUrl,PHP_URL_PATH);
    if (! is_string($path) || $portalPath==='' || !str_starts_with($path,rtrim($portalPath,'/'))) {
      return $portalUrl;
    }

    return home_url($path);
  }

  private static function registerPageRewrite(int $pageId, bool $shortcodePage = false): void {
    if ($pageId < 1 || get_post_status($pageId) !== 'publish') {
      return;
    }

    $url = get_permalink($pageId);
    if (! is_string($url) || $url === '') {
      return;
    }

    $path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');
    $pattern = $path === ''
      ? '^(?:login|register|tickets(?:/.*)?|purchases|profile)/?$'
      : '^' . preg_quote($path, '#') . '(?:/.*)?$';
    $target = 'index.php?' . self::QUERY_VAR . '=1';

    if ($shortcodePage) {
      $target .= '&' . self::SHORTCODE_PAGE_QUERY_VAR . '=' . $pageId;
    }

    add_rewrite_rule($pattern, $target, 'top');
  }

}
