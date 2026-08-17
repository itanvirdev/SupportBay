<?php

declare(strict_types=1);

namespace SupportBay\Modules\CustomFields\Events;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\CustomFields\Entities\CustomField;
use SupportBay\Modules\CustomFields\Entities\TicketCustomFieldValue;
use SupportBay\Modules\Tickets\Entities\Ticket;

final class TicketCustomFieldValueChanged extends AbstractEvent {
  public function __construct(
    private readonly Ticket $ticket,
    private readonly CustomField $field,
    private readonly ?TicketCustomFieldValue $previousValue,
    private readonly ?TicketCustomFieldValue $currentValue,
    private readonly string $action,
    private readonly ?int $actorId,
    private readonly AuthorType $actorType,
  ) { parent::__construct(); }

  public function ticket(): Ticket { return $this->ticket; }
  public function field(): CustomField { return $this->field; }
  public function previousValue(): ?TicketCustomFieldValue { return $this->previousValue; }
  public function currentValue(): ?TicketCustomFieldValue { return $this->currentValue; }
  public function action(): string { return $this->action; }
  public function actorId(): ?int { return $this->actorId; }
  public function actorType(): AuthorType { return $this->actorType; }
}
