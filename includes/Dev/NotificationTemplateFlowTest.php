<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Notifications\Enums\NotificationRecipientType;
use SupportBay\Modules\Notifications\Enums\NotificationTemplateStatus;
use SupportBay\Modules\Notifications\Services\NotificationTemplateService;

final class NotificationTemplateFlowTest extends FlowTest {
  protected static function title(): string {
    return 'Notification Template Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var NotificationTemplateService $templates */
    [$templates] = $services;

    $existing = get_option('sbay_notification_templates', null);
    delete_option('sbay_notification_templates');

    Assert::count(
      9,
      $templates->all(),
      'The nine active ticket, reply, lifecycle, and assignment templates have safe defaults.'
    );

    $rendered = $templates->render(
      'ticket_created',
      NotificationRecipientType::CUSTOMER,
      [
        'customer_name' => 'Imtiaz Ahmed',
        'track_id' => '54E5DF43',
        'ticket_subject' => 'Demo Import Issue',
        'ticket_url' => 'https://example.com/support/tickets/1/',
      ],
    );

    Assert::true(
      $rendered !== null
      && str_contains($rendered->subject, '54E5DF43')
      && str_contains($rendered->plainTextContent, 'Imtiaz Ahmed')
      && str_contains($rendered->htmlContent, 'Demo Import Issue'),
      'Default templates render canonical placeholders for each output format.'
    );

    $closed = $templates->render(
      'ticket_closed',
      NotificationRecipientType::CUSTOMER,
      ['customer_name' => 'Imtiaz Ahmed', 'track_id' => '54E5DF43'],
    );
    $reopened = $templates->render(
      'ticket_reopened',
      NotificationRecipientType::CUSTOMER,
      ['customer_name' => 'Imtiaz Ahmed', 'track_id' => '54E5DF43'],
    );

    Assert::true(
      $closed !== null
      && str_contains($closed->subject, 'closed')
      && $reopened !== null
      && str_contains($reopened->subject, 'reopened'),
      'Ticket close and reopen templates render for customer recipients.'
    );

    $resolved = $templates->render(
      'ticket_resolved',
      NotificationRecipientType::CUSTOMER,
      ['customer_name' => 'Imtiaz Ahmed', 'track_id' => '54E5DF43'],
    );
    Assert::true(
      $resolved !== null && str_contains($resolved->subject, 'resolved'),
      'Ticket resolution template renders for the customer recipient.'
    );

    $assigned = $templates->render(
      'ticket_assigned',
      NotificationRecipientType::AGENT,
      ['agent_name' => 'Tanvir Agent', 'track_id' => '54E5DF43'],
    );
    Assert::true(
      $assigned !== null
      && str_contains($assigned->subject, 'assigned')
      && str_contains($assigned->plainTextContent, 'Tanvir Agent'),
      'Ticket assignment template renders for the assigned agent.'
    );

    $reassigned = $templates->render(
      'ticket_reassigned',
      NotificationRecipientType::AGENT,
      ['agent_name' => 'Tanvir Agent', 'track_id' => '54E5DF43'],
    );
    Assert::true(
      $reassigned !== null
      && str_contains($reassigned->subject, 'reassigned')
      && str_contains($reassigned->plainTextContent, 'Tanvir Agent'),
      'Ticket reassignment template renders for the newly assigned agent.'
    );

    $updated = $templates->update(
      'ticket_created',
      NotificationRecipientType::CUSTOMER,
      [
        'subject' => 'Ticket {track_id} for {{customer_name}}',
        'html_content' => '<p>Hello {{customer_name}}</p><script>alert(1)</script>',
        'plain_text_content' => "Hello {customer_name}\nTicket {{track_id}}",
      ],
    );
    $custom = $templates->render(
      'ticket_created',
      NotificationRecipientType::CUSTOMER,
      [
        'customer_name' => 'Imtiaz Ahmed',
        'track_id' => '54E5DF43',
      ],
    );

    Assert::true(
      ! str_contains($updated->htmlContent(), '<script')
      && $custom !== null
      && $custom->subject === 'Ticket 54E5DF43 for Imtiaz Ahmed'
      && str_contains($custom->plainTextContent, 'Ticket 54E5DF43'),
      'Saved templates are sanitized and support canonical and legacy placeholders.'
    );

    $templates->update(
      'ticket_created',
      NotificationRecipientType::CUSTOMER,
      ['status' => NotificationTemplateStatus::INACTIVE->value],
    );

    Assert::true(
      $templates->render(
        'ticket_created',
        NotificationRecipientType::CUSTOMER,
        [],
      ) === null,
      'Inactive templates suppress their notification variant.'
    );

    $agent = $templates->find(
      'ticket_created',
      NotificationRecipientType::AGENT,
    );

    Assert::true(
      $agent !== null && $agent->isActive(),
      'Customer and agent templates remain independently configurable.'
    );

    $templates->reset(
      'ticket_created',
      NotificationRecipientType::CUSTOMER,
    );
    $reset = $templates->find(
      'ticket_created',
      NotificationRecipientType::CUSTOMER,
    );

    Assert::true(
      $reset !== null
      && $reset->isActive()
      && str_contains($reset->subject(), 'We received your ticket'),
      'Reset restores the built-in fallback template.'
    );

    update_option('sbay_notification_templates', [
      'ticket_created:customer' => [
        'event' => 'ticket_created',
        'recipient_type' => 'customer',
        'status' => 'corrupt',
      ],
    ], false);
    $fallback = $templates->find(
      'ticket_created',
      NotificationRecipientType::CUSTOMER,
    );

    Assert::true(
      $fallback !== null
      && $fallback->isActive()
      && str_contains($fallback->subject(), 'We received your ticket'),
      'Invalid saved data falls back to the safe built-in template.'
    );

    if ($existing === null) {
      delete_option('sbay_notification_templates');
    } else {
      update_option('sbay_notification_templates', $existing, false);
    }
  }
}
