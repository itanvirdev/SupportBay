<?php

declare(strict_types=1);

namespace SupportBay\Core\Events\Contracts;

interface Listener {
    /**
     * Handle an event.
     */
    public function handle(Event $event): void;
}
