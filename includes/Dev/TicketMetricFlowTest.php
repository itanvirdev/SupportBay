<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Common\Enums\SourceType;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Modules\Messages\Repositories\MessageRepository;
use SupportBay\Modules\Tickets\Data\TicketMetricQuery;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Tickets\Enums\TicketState;
use SupportBay\Modules\Tickets\Enums\TicketStatus;
use SupportBay\Modules\Tickets\Http\Controllers\TicketMetricController;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;
use SupportBay\Modules\Tickets\Services\TicketMetricService;
use WP_Error;
use WP_REST_Request;
use SupportBay\Common\Utilities\CsvExporter;
use SupportBay\Modules\Categories\Services\CategoryService;
use SupportBay\Modules\Tags\Services\TagService;
use SupportBay\Modules\CustomFields\Services\CustomFieldService;

final class TicketMetricFlowTest extends FlowTest {
  protected static function title(): string { return 'Ticket Metric Flow Test'; }

  protected static function execute(...$services): void {
    /** @var TicketMetricService $metrics */
    /** @var TicketMetricController $controller */
    /** @var TicketRepository $tickets */
    /** @var MessageRepository $messages */
    /** @var CsvExporter $csvExporter */
    /** @var CategoryService $categories */
    /** @var TagService $tags */
    /** @var CustomFieldService $customFields */
    [$metrics, $controller, $tickets, $messages, $csvExporter, $categories, $tags, $customFields] = $services;
    $today = current_time('Y-m-d');
    $now = $today . ' 00:00:00';
    $ticketIds = [];
    $messageIds = [];
    $agentId = 900000000 + wp_rand(1, 9999999);
    $category = $categories->create([
      'name' => 'Metric Category ' . strtolower(
        wp_generate_password(8, false, false)
      ),
      'department_id' => 1,
    ]);
    $tag = $tags->create(['name' => 'Metric Tag ' . strtolower(wp_generate_password(8, false, false))]);
    $secondTag = $tags->create(['name' => 'Escalated ' . strtolower(wp_generate_password(8, false, false))]);
    $customField = $customFields->create([
      'name' => 'Metric Environment ' . strtolower(wp_generate_password(8, false, false)),
      'type' => 'select',
      'options' => ['Production', 'Staging'],
      'department_id' => 1,
    ]);

    try {
      $escaped = $csvExporter->generate([[
        'name' => 'Formula safety',
        'headers' => ['Value'],
        'rows' => [['=HYPERLINK("https://example.test")']],
      ]]);
      Assert::true(
        str_contains($escaped, "'=HYPERLINK"),
        'CSV export neutralizes spreadsheet formula prefixes.',
      );

      foreach ([TicketStatus::ANSWERED, TicketStatus::OPEN, TicketStatus::CLOSED] as $index => $status) {
        $ticketIds[] = $tickets->create([
          'track_id' => strtoupper(substr(wp_generate_password(9, false, false), 0, 9)),
          'customer_id' => 1,
          'department_id' => 1,
          'category_id' => $index < 2 ? $category->id() : null,
          'assigned_agent_id' => $agentId,
          'subject' => 'Metric flow ' . $index,
          'created_by_type' => AuthorType::CUSTOMER->value,
          'status' => $status->value,
          'state' => TicketState::ACTIVE->value,
          'priority' => TicketPriority::NORMAL->value,
          'source' => SourceType::WEB->value,
          'first_response_at' => $index === 0 ? $today . ' 00:15:00' : null,
          'closed_at' => $status === TicketStatus::CLOSED ? $now : null,
          'created_at' => $now,
          'updated_at' => $now,
        ]);
      }

      foreach ([[0, AuthorType::AGENT], [1, AuthorType::CUSTOMER]] as [$ticketIndex, $author]) {
        $message = $messages->create([
          'ticket_id' => $ticketIds[$ticketIndex],
          'author_id' => 1,
          'author_type' => $author->value,
          'type' => MessageType::REPLY->value,
          'content' => 'Metric fixture',
          'created_at' => $now,
        ]);
        $messageIds[] = $message;
      }
      $tags->attach($ticketIds[0], $tag->id());
      $tags->attach($ticketIds[1], $tag->id());
      $tags->attach($ticketIds[0], $secondTag->id());
      $customFields->setValue($ticketIds[0], $customField->id(), 'Production');
      $customFields->setValue($ticketIds[1], $customField->id(), 'Production');
      $customFields->setValue($ticketIds[2], $customField->id(), 'Staging');

      $report = $metrics->report(new TicketMetricQuery(
        dateFrom: $today,
        dateTo: $today,
        departmentId: 1,
        assignedAgentId: $agentId,
        priority: TicketPriority::NORMAL->value,
      ));
      Assert::true(
        $report['summary']['tickets'] === 3
        && $report['summary']['responses'] === 1
        && $report['summary']['need_reply'] === 1
        && $report['summary']['closed'] === 1
        && ! isset($report['summary']['sla'])
        && ! isset($report['summary']['response_bands']),
        'Ticket report derives MVP volume, response, queue, and closure metrics without SLA analysis.',
      );
      Assert::true(
        count($report['daily']) === 1
        && $report['daily'][0]['tickets'] === 3
        && $report['departments'][0]['tickets'] === 3
        && count($report['categories']) === 2
        && array_sum(array_column($report['categories'], 'tickets')) === 3
        && count($report['tags']) === 3
        && array_sum(array_column($report['tags'], 'tickets')) === 4,
        'Ticket report applies filters consistently and groups categorized and uncategorized workload.',
      );
      $categoryReport = $metrics->report(new TicketMetricQuery(
        dateFrom: $today,
        dateTo: $today,
        categoryId: $category->id(),
        assignedAgentId: $agentId,
      ));
      $uncategorizedReport = $metrics->report(new TicketMetricQuery(
        dateFrom: $today,
        dateTo: $today,
        uncategorized: true,
        assignedAgentId: $agentId,
      ));
      Assert::true(
        $categoryReport['summary']['tickets'] === 2
        && $categoryReport['categories'][0]['category'] === $category->name()
        && $uncategorizedReport['summary']['tickets'] === 1
        && $uncategorizedReport['categories'][0]['category'] === 'Uncategorized',
        'Ticket reports support exact category and uncategorized filters.',
      );
      $tagReport = $metrics->report(new TicketMetricQuery(
        dateFrom: $today,
        dateTo: $today,
        tagId: $tag->id(),
        assignedAgentId: $agentId,
      ));
      Assert::true(
        $tagReport['summary']['tickets'] === 2
        && count($tagReport['tags']) === 2
        && array_sum(array_column($tagReport['tags'], 'tickets')) === 3,
        'Ticket reports filter unique tickets by tag while preserving multi-tag workload membership.',
      );
      $customFieldReport = $metrics->report(new TicketMetricQuery(
        dateFrom: $today,
        dateTo: $today,
        customFieldId: $customField->id(),
        customFieldValue: 'Production',
        assignedAgentId: $agentId,
      ));
      Assert::true(
        $customFieldReport['summary']['tickets'] === 2
        && $customFieldReport['custom_fields'][0]['value'] === 'Production'
        && $customFieldReport['custom_fields'][0]['tickets'] === 2,
        'Ticket reports filter exact custom-field values and expose the selected-field workload.',
      );
      $csv = $metrics->export(new TicketMetricQuery(
        dateFrom: $today,
        dateTo: $today,
        assignedAgentId: $agentId,
      ));
      Assert::true(
        str_starts_with($csv, "\xEF\xBB\xBF")
        && str_contains($csv, 'Ticket performance summary')
        && str_contains($csv, 'Daily activity')
        && str_contains($csv, 'Agent workload')
        && str_contains($csv, 'Category workload')
        && str_contains($csv, 'Tag workload')
        && str_contains($csv, 'Custom field workload'),
        'Ticket report exports the filtered summary and breakdowns as UTF-8 CSV.',
      );

      if (did_action('rest_api_init') === 0) { do_action('rest_api_init', rest_get_server()); }
      Assert::true(
        isset(rest_get_server()->get_routes()['/sbay/v1/reports/tickets'])
        && isset(rest_get_server()->get_routes()['/sbay/v1/reports/tickets/export']),
        'Ticket report and export routes are registered.',
      );
      wp_set_current_user(0);
      Assert::true($controller->permissions() instanceof WP_Error, 'Anonymous ticket report access is rejected.');
      Assert::true($controller->exportPermissions() instanceof WP_Error, 'Anonymous report export is rejected.');
      wp_set_current_user(1);
      Assert::true($controller->permissions() === true, 'Authorized administrators can view ticket reports.');
      Assert::true($controller->exportPermissions() === true, 'Administrators can export ticket reports.');
      $request = new WP_REST_Request('GET', '/sbay/v1/reports/tickets');
      $request->set_query_params(['date_from' => $today, 'date_to' => $today, 'department_id' => 1, 'tag_id' => $tag->id(), 'custom_field_id' => $customField->id(), 'custom_field_value' => 'Production', 'assigned_agent_id' => $agentId]);
      $response = rest_do_request($request);
      Assert::true(
        $response->get_status() === 200
        && $response->get_data()['data']['filters']['tag_id'] === $tag->id()
        && $response->get_data()['data']['filters']['custom_field_id'] === $customField->id()
        && $response->get_data()['data']['filters']['custom_field_value'] === 'Production'
        && $response->get_data()['data']['summary']['tickets'] === 2,
        'Protected ticket report endpoint applies tag and custom-field filters.'
      );
      $exportRequest = new WP_REST_Request('GET', '/sbay/v1/reports/tickets/export');
      $exportRequest->set_query_params(['date_from' => $today, 'date_to' => $today, 'assigned_agent_id' => $agentId]);
      $exportData = rest_do_request($exportRequest)->get_data();
      Assert::true(
        str_ends_with((string) ($exportData['data']['filename'] ?? ''), '.csv')
        && str_contains((string) ($exportData['data']['content'] ?? ''), 'Ticket performance summary'),
        'Ticket export endpoint returns an authorized CSV download payload.',
      );
    } finally {
      foreach ($messageIds as $id) { $messages->delete($id); }
      foreach ($ticketIds as $id) {
        $customFields->removeValue($id, $customField->id());
        $tags->detach($id, $tag->id());
        $tags->detach($id, $secondTag->id());
        $tickets->delete($id);
      }
      $tags->delete($tag->id());
      $tags->delete($secondTag->id());
      $categories->delete($category->id());
      $customFields->delete($customField->id());
      wp_set_current_user(0);
    }
  }
}
