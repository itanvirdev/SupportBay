<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Enums;

enum AuthTokenType: string {
  /**
   * Passwordless customer login.
   */
  case MAGIC_LOGIN = 'magic_login';

  /**
   * Reserved for future.
   */
  case PASSWORD_RESET = 'password_reset';
  case EMAIL_VERIFICATION = 'email_verification';
  case ACCOUNT_INVITATION = 'account_invitation';
  case API_ACCESS = 'api_access';
}
