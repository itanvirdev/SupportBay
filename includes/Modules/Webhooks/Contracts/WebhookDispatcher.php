<?php

declare(strict_types=1);

namespace SupportBay\Modules\Webhooks\Contracts;

use SupportBay\Modules\Webhooks\Data\WebhookData;

interface WebhookDispatcher {
  public function dispatch(WebhookData $webhook): void;
}
