<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Data;

final readonly class RenderedNotificationTemplate {
  public function __construct(
    public string $subject,
    public string $htmlContent,
    public string $plainTextContent,
  ) {
  }
}
