<?php

declare(strict_types=1);

namespace SupportBay\Modules\Settings\Services;

use RuntimeException;

final class RecaptchaService {
  private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
  private const MINIMUM_SCORE = 0.5;

  public function __construct(private readonly GeneralSettingsService $settings) {}

  public function enabledFor(string $action): bool {
    $config=$this->settings->recaptchaConfiguration();
    $key=match($action){'login'=>'show_login','registration'=>'show_registration','guest_ticket'=>'show_guest_ticket',default=>''};
    return $config['enabled'] && $key!=='' && $config[$key];
  }

  public function siteKey(): string { return $this->settings->recaptchaConfiguration()['site_key']; }

  public function verify(string $token,string $action,?string $remoteIp=null): void {
    if (! $this->enabledFor($action)) { return; }
    if (trim($token)==='') { throw new RuntimeException('Security verification is required. Please try again.'); }
    $config=$this->settings->recaptchaConfiguration();
    $response=wp_remote_post(self::VERIFY_URL,['timeout'=>10,'body'=>array_filter([
      'secret'=>$config['secret_key'],'response'=>$token,'remoteip'=>$remoteIp,
    ])]);
    if (is_wp_error($response)) { throw new RuntimeException('Security verification is temporarily unavailable.'); }
    $body=json_decode((string)wp_remote_retrieve_body($response),true);
    if (! is_array($body)) { throw new RuntimeException('Security verification returned an invalid response.'); }
    $hostname=(string)($body['hostname']??'');
    $expectedHost=(string)wp_parse_url(home_url('/'),PHP_URL_HOST);
    if (!($body['success']??false) || ($body['action']??'')!==$action
      || (float)($body['score']??0)<self::MINIMUM_SCORE || ($hostname!=='' && !hash_equals($expectedHost,$hostname))) {
      throw new RuntimeException('Security verification failed. Please try again.');
    }
  }
}
