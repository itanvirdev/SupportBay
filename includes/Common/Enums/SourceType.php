<?php

declare(strict_types=1);

namespace SupportBay\Common\Enums;

enum SourceType: string {
  case WEB = 'web';
  case API = 'api';
  case EMAIL = 'email';
  case LIVE_CHAT = 'live_chat';
  case IMPORT = 'import';

  public static function default(): self {
    return self::WEB;
  }
}
