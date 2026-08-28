<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Enums\CustomerState;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Departments\Services\DepartmentService;
use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Notifications\Data\NotificationData;
use SupportBay\Modules\Notifications\Enums\NotificationStatus;
use SupportBay\Modules\Notifications\Repositories\NotificationLogRepository;
use SupportBay\Modules\Notifications\Services\NotificationService;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\AssignRules\Services\AssignRuleService;

final class NotificationFlowTest extends FlowTest {
  protected static function title(): string {
    return 'Notification Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var TicketService $tickets */
    /** @var MessageService $messages */
    /** @var CustomerService $customers */
    /** @var DepartmentService $departments */
    /** @var NotificationService $notifications */
    /** @var NotificationLogRepository $logs */
    /** @var ActivityService $activities */
    /** @var AssignRuleService $assignRules */
    [$tickets, $messages, $customers, $departments, $notifications, $logs, $activities, $assignRules] = $services;

    echo "🚀 Starting SupportBay Notification Flow Test...\n\n";

    $deliveries = [];
    $deliverySucceeds = true;
    $capture = static function (
      null|bool $return,
      array $attributes,
    ) use (&$deliveries, &$deliverySucceeds): bool {
      $deliveries[] = $attributes;

      return $deliverySucceeds;
    };

    add_filter('pre_wp_mail', $capture, 10, 2);

    $activeRuleIds = array_map(
      static fn($rule): int => $rule->id(),
      array_values(array_filter($assignRules->all(), static fn($rule): bool => $rule->isActive())),
    );
    if ($activeRuleIds !== []) {
      $assignRules->bulk($activeRuleIds, 'deactivate');
    }

    try {

    $suffix = strtolower(wp_generate_password(8, false, false));
    $userId = wp_insert_user([
      'user_login'   => 'sbay-notify-' . $suffix,
      'user_pass'    => wp_generate_password(32, true, true),
      'user_email'   => 'sbay-notify-' . $suffix . '@example.com',
      'display_name' => 'Notification Customer',
      'role'         => 'subscriber',
    ]);

    Assert::true(is_int($userId), 'Notification test user created.');

    $customerId = $customers->create([
      'user_id' => $userId,
      'state'   => CustomerState::REGISTERED->value,
      'source'  => CustomerSource::REGISTRATION->value,
    ]);
    $departmentId = $departments->create([
      'name' => 'Notification Test ' . $suffix,
      'slug' => 'notification-test-' . $suffix,
    ]);
    $ticketId = $tickets->create([
      'customer_id'   => $customerId,
      'department_id' => $departmentId,
      'subject'       => 'Notification delivery test',
    ]);

    Assert::count(
      0,
      $deliveries,
      'Ticket creation queues email without blocking the request.'
    );

    $ticketLogs = $notifications->logsForTicket($ticketId);

    Assert::count(
      2,
      $ticketLogs,
      'Ticket creation persists one delivery log per recipient.'
    );

    Assert::true(
      $ticketLogs[0]->status() === NotificationStatus::PENDING
      && $ticketLogs[0]->scheduledAt() !== null,
      'Queued notification records are pending immediate dispatch.'
    );

    Assert::true(
      $notifications->dispatch($ticketLogs[0]->id())
      && $notifications->dispatch($ticketLogs[1]->id()),
      'Pending ticket notifications can be dispatched from their audit records.'
    );

    $ticketLogs = $notifications->logsForTicket($ticketId);

    Assert::true(
      count($deliveries) === 2
      && $ticketLogs[0]->status() === NotificationStatus::SENT
      && $ticketLogs[0]->sentAt() !== null
      && $ticketLogs[0]->retryCount() === 0
      && $ticketLogs[0]->channel() === 'email'
      && $ticketLogs[0]->provider() === 'wordpress',
      'First queued delivery records success without consuming a retry attempt.'
    );

    $initial = $messages->create([
      'ticket_id'  => $ticketId,
      'author_id'  => $customerId,
      'author_type' => AuthorType::CUSTOMER->value,
      'content'    => 'Initial ticket message.',
    ]);

    Assert::count(
      2,
      $deliveries,
      'Initial ticket content does not create a duplicate reply email.'
    );

    $customerReply = $messages->create([
      'ticket_id'   => $ticketId,
      'author_id'   => $customerId,
      'author_type' => AuthorType::CUSTOMER->value,
      'content'     => 'Customer follow-up.',
    ]);

    $customerReplyLogs = $notifications->logsForTicket($ticketId);
    $notifications->dispatch(
      $customerReplyLogs[count($customerReplyLogs) - 1]->id()
    );

    Assert::count(
      3,
      $deliveries,
      'Customer reply notifies the administrator.'
    );

    $agentReply = $messages->create([
      'ticket_id'   => $ticketId,
      'author_id'   => 1,
      'author_type' => AuthorType::AGENT->value,
      'content'     => 'Agent response.',
    ]);

    $agentReplyLogs = $notifications->logsForTicket($ticketId);
    $notifications->dispatch(
      $agentReplyLogs[count($agentReplyLogs) - 1]->id()
    );

    Assert::equals(
      'sbay-notify-' . $suffix . '@example.com',
      $deliveries[3]['to'],
      'Agent reply notifies the ticket customer.'
    );

    $firstAssignmentLogs = array_values(array_filter(
      $agentReplyLogs,
      static fn($log): bool => $log->event() === 'ticket_assigned',
    ));
    Assert::true(
      isset($firstAssignmentLogs[0])
      && $notifications->dispatch($firstAssignmentLogs[0]->id()),
      'First public staff reply queues assignment email for its responder.'
    );

    $internal = $messages->create([
      'ticket_id'   => $ticketId,
      'author_id'   => 1,
      'author_type' => AuthorType::AGENT->value,
      'type'        => MessageType::INTERNAL_NOTE->value,
      'content'     => 'Private note.',
    ]);

    Assert::count(
      5,
      $deliveries,
      'Internal notes never send customer notifications.'
    );

    Assert::count(
      5,
      $notifications->logsForTicket($ticketId),
      'Only actual public notification attempts create delivery logs.'
    );

    $tickets->resolve($ticketId, 1);
    $resolvedLogs = $notifications->logsForTicket($ticketId);
    $resolvedLog = $resolvedLogs[count($resolvedLogs) - 1];
    $notifications->dispatch($resolvedLog->id());

    Assert::true(
      $resolvedLog->event() === 'ticket_resolved'
      && str_contains((string) $deliveries[5]['subject'], 'resolved'),
      'Resolving a ticket queues and delivers the customer resolution template.'
    );
    $resolutionActivities = array_filter(
      $activities->getByTicket($ticketId),
      static fn($activity): bool =>
        $activity->eventType() === ActivityType::TICKET_RESOLVED,
    );
    Assert::count(
      1,
      $resolutionActivities,
      'Resolving a ticket records one actor-aware timeline activity.'
    );

    $tickets->reopen($ticketId);
    $resolvedReopenLogs = $notifications->logsForTicket($ticketId);
    $resolvedReopenLog = $resolvedReopenLogs[count($resolvedReopenLogs) - 1];
    $notifications->dispatch($resolvedReopenLog->id());

    Assert::true(
      $resolvedReopenLog->event() === 'ticket_reopened'
      && str_contains((string) $deliveries[6]['subject'], 'reopened'),
      'A resolved ticket can be reopened for continued support.'
    );

    $tickets->close($ticketId);
    $closedLogs = $notifications->logsForTicket($ticketId);
    $closedLog = $closedLogs[count($closedLogs) - 1];
    $notifications->dispatch($closedLog->id());

    Assert::true(
      $closedLog->event() === 'ticket_closed'
      && str_contains((string) $deliveries[7]['subject'], 'closed'),
      'Closing a ticket queues and delivers the customer lifecycle template.'
    );

    $tickets->reopen($ticketId);
    $reopenedLogs = $notifications->logsForTicket($ticketId);
    $reopenedLog = $reopenedLogs[count($reopenedLogs) - 1];
    $notifications->dispatch($reopenedLog->id());

    Assert::true(
      $reopenedLog->event() === 'ticket_reopened'
      && str_contains((string) $deliveries[8]['subject'], 'reopened'),
      'Reopening a ticket queues and delivers the customer lifecycle template.'
    );

    Assert::count(
      9,
      $notifications->logsForTicket($ticketId),
      'Resolution, close, and reopen actions add one customer delivery record each.'
    );

    $agentId = wp_insert_user([
      'user_login' => 'sbay-notify-agent-' . $suffix,
      'user_pass' => wp_generate_password(32, true, true),
      'user_email' => 'sbay-notify-agent-' . $suffix . '@example.com',
      'display_name' => 'Notification Agent',
      'role' => 'sbay_agent',
    ]);
    Assert::true(is_int($agentId), 'Notification test agent created.');

    $tickets->changeAssignment($ticketId, (int) $agentId, 1);
    $assignedLogs = $notifications->logsForTicket($ticketId);
    $assignedLog = $assignedLogs[count($assignedLogs) - 1];
    $notifications->dispatch($assignedLog->id());

    Assert::true(
      $assignedLog->event() === 'ticket_reassigned'
      && ($deliveries[9]['to'] ?? '') === 'sbay-notify-agent-' . $suffix . '@example.com',
      'Reassigning a ticket queues distinct email to the newly assigned agent.'
    );

    $tickets->changeAssignment($ticketId, (int) $agentId, 1);
    Assert::count(
      10,
      $notifications->logsForTicket($ticketId),
      'Assigning the current agent again is a notification no-op.'
    );

    $tickets->changeAssignment($ticketId, null, 1);
    Assert::count(
      10,
      $notifications->logsForTicket($ticketId),
      'Unassigning a ticket does not create an email delivery record.'
    );

    Assert::false(
      $notifications->send(new NotificationData(
        event: 'system_alert',
        recipient: 'not-an-email',
        subject: 'Invalid recipient test',
        content: 'This notification must fail before channel delivery.',
        metadata: ['ticket_id' => $ticketId],
      )),
      'Invalid notification recipients fail safely.'
    );

    $failedLogs = $notifications->logsForTicket($ticketId);
    $failedLog = $failedLogs[count($failedLogs) - 1];

    Assert::true(
      $failedLog->failed()
      && $failedLog->canRetry()
      && str_contains((string) $failedLog->errorMessage(), 'invalid'),
      'Failed notification attempts retain a retryable audit record and error.'
    );

    Assert::false(
      $notifications->retry($failedLog->id()),
      'First invalid-recipient retry fails safely.'
    );
    Assert::false(
      $notifications->retry($failedLog->id()),
      'Second invalid-recipient retry fails safely.'
    );
    Assert::false(
      $notifications->retry($failedLog->id()),
      'Third invalid-recipient retry fails safely.'
    );

    $retryLimitEnforced = false;

    try {
      $notifications->retry($failedLog->id());
    } catch (\RuntimeException $exception) {
      $retryLimitEnforced = str_contains(
        $exception->getMessage(),
        'cannot be retried'
      );
    }

    $exhaustedLog = $logs->find($failedLog->id());

    Assert::true(
      $retryLimitEnforced
      && $exhaustedLog !== null
      && $exhaustedLog->retryCount() === 3
      && ! $exhaustedLog->canRetry(),
      'Notification retries stop after three attempts.'
    );

    $deliverySucceeds = false;
    Assert::false(
      $notifications->send(new NotificationData(
        event: 'system_alert',
        recipient: 'retry-' . $suffix . '@example.com',
        subject: 'Channel retry test',
        content: 'This notification succeeds on retry.',
        metadata: ['ticket_id' => $ticketId],
      )),
      'Channel delivery failure is recorded for retry.'
    );

    $retryableLogs = $notifications->logsForTicket($ticketId);
    $retryableLog = $retryableLogs[count($retryableLogs) - 1];
    $deliverySucceeds = true;

    Assert::true(
      $notifications->retry($retryableLog->id()),
      'Stored notification payload is delivered successfully on retry.'
    );

    $retriedLog = $logs->find($retryableLog->id());

    Assert::true(
      $retriedLog !== null
      && $retriedLog->status() === NotificationStatus::SENT
      && $retriedLog->retryCount() === 1
      && $retriedLog->errorMessage() === null,
      'Successful retry updates the original audit record and clears its error.'
    );

    Assert::count(
      12,
      $notifications->logsForTicket($ticketId),
      'Retries reuse their original audit records without creating duplicates.'
    );

    remove_filter('pre_wp_mail', $capture, 10);
    Assert::equals(
      12,
      $logs->deleteByTicket($ticketId),
      'Test notification delivery logs deleted.'
    );
    $messages->delete($internal->id());
    $messages->delete($agentReply->id());
    $messages->delete($customerReply->id());
    $messages->delete($initial->id());
    $tickets->delete($ticketId);
    $departments->delete($departmentId);
    $customers->deleteWithUser($customerId);
    if (! function_exists('wp_delete_user')) {
      require_once ABSPATH . 'wp-admin/includes/user.php';
    }
    wp_delete_user((int) $agentId);
    } finally {
      remove_filter('pre_wp_mail', $capture, 10);
      if ($activeRuleIds !== []) {
        $assignRules->bulk($activeRuleIds, 'activate');
      }
    }
  }
}
