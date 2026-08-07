<?php

declare(strict_types=1);

namespace SupportBay\Modules\Webhooks\Dispatchers;

use SupportBay\Modules\Webhooks\Contracts\WebhookDispatcher;
use SupportBay\Modules\Webhooks\Data\WebhookData;

final class WordPressHookDispatcher implements WebhookDispatcher {
  public function dispatch(WebhookData $webhook): void {
    do_action('supportbay_webhook_dispatch', $webhook);
  }
}
