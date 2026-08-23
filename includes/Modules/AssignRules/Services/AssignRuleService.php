<?php

declare(strict_types=1);

namespace SupportBay\Modules\AssignRules\Services;

use InvalidArgumentException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Modules\AssignRules\Entities\AssignRule;
use SupportBay\Modules\AssignRules\Enums\AssignRuleStatus;
use SupportBay\Modules\AssignRules\Enums\AssignRuleType;
use SupportBay\Modules\AssignRules\Repositories\AssignRuleRepository;
use SupportBay\Modules\Categories\Services\CategoryService;
use SupportBay\Modules\Notifications\Data\NotificationData;
use SupportBay\Modules\Notifications\Services\NotificationService;
use SupportBay\Modules\Roles\Entities\SupportRole;
use SupportBay\Modules\Roles\Services\SupportRoleService;
use SupportBay\Modules\Tickets\Entities\Ticket;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;
use SupportBay\Modules\Tickets\Services\TicketService;
use WP_User;

final class AssignRuleService {
  private const DEFAULTS_OPTION = 'sbay_assign_rule_defaults_installed';
  public function __construct(
    private readonly AssignRuleRepository $repository,
    private readonly CategoryService $categories,
    private readonly SupportRoleService $roles,
    private readonly TicketRepository $ticketRepository,
    private readonly TicketService $tickets,
    private readonly NotificationService $notifications,
  ) {}

  /** @return AssignRule[] */
  public function all(): array { return $this->repository->all(); }
  public function find(int $id): ?AssignRule { return $this->repository->find($id); }

  /** @param array<string, mixed> $data */
  public function create(array $data): AssignRule {
    $id = $this->repository->create($this->normalize($data));
    return $this->find($id) ?? throw new InvalidArgumentException('Assign rule could not be created.');
  }

  /** @param array<string, mixed> $data */
  public function update(int $id, array $data): ?AssignRule {
    $existing = $this->find($id);
    if (! $existing) { return null; }
    $merged = array_merge($existing->toArray(), $data);
    $this->repository->update($id, $this->normalize($merged));
    return $this->find($id);
  }

  public function delete(int $id): bool {
    return $this->find($id) ? $this->repository->delete($id) : false;
  }

  /** Provision the two MVP fallback rules once, including on existing installations. */
  public function provisionDefaults(): void {
    if ((bool) get_option(self::DEFAULTS_OPTION, false)) { return; }
    foreach (['sbay_agent', 'sbay_manager'] as $role) {
      $supportRole = $this->roles->find($role);
      if (! $supportRole || ! $supportRole->isActive() || ! $supportRole->isSupportRole()) { return; }
    }
    foreach (['sbay_agent', 'sbay_manager'] as $role) {
      $exists = array_filter($this->all(), static fn(AssignRule $rule): bool =>
        $rule->type() === AssignRuleType::ROLE
        && $rule->targetRole() === $role
        && $rule->appliesToAllCategories()
      );
      if ($exists !== []) { continue; }
      $this->create([
        'rule_type' => AssignRuleType::ROLE->value,
        'target_role' => $role,
        'all_categories' => true,
        'status' => AssignRuleStatus::ACTIVE->value,
      ]);
    }
    update_option(self::DEFAULTS_OPTION, true, false);
  }

  /** @param int[] $ids @return array{updated:int,deleted:int} */
  public function bulk(array $ids, string $action): array {
    $ids = array_values(array_unique(array_filter(array_map('absint', array_slice($ids, 0, 100)))));
    if ($ids === []) { throw new InvalidArgumentException('Select at least one assign rule.'); }
    if (! in_array($action, ['activate', 'deactivate', 'delete'], true)) {
      throw new InvalidArgumentException('Bulk action is invalid.');
    }
    $result = ['updated' => 0, 'deleted' => 0];
    foreach ($ids as $id) {
      if (! $this->find($id)) { continue; }
      if ($action === 'delete') { $result['deleted'] += $this->repository->delete($id) ? 1 : 0; }
      else {
        $this->repository->update($id, ['status' => $action === 'activate' ? 'active' : 'inactive']);
        $result['updated']++;
      }
    }
    return $result;
  }

  /** Apply active rules in ID order. Only the first assignment rule claims the ticket. */
  public function applyToTicket(Ticket $ticket): void {
    $assigned = $ticket->assignedAgentId() !== null;
    foreach ($this->repository->active() as $rule) {
      if (! $rule->matchesCategory($ticket->categoryId())) { continue; }
      if ($rule->type() === AssignRuleType::NOTIFY) {
        $this->notify($rule, $ticket);
        continue;
      }
      if ($assigned) { continue; }
      $agentId = $rule->type() === AssignRuleType::AGENT
        ? $rule->targetAgentId()
        : $this->agentForRole((string) $rule->targetRole());
      if ($agentId === null) { continue; }
      $agent = get_userdata($agentId);
      if (! $agent || ! user_can($agent, CapabilityManager::VIEW_TICKETS)) { continue; }
      $this->tickets->changeAssignment($ticket->id(), $agentId, 0);
      $assigned = true;
    }
  }

  /** @return array{roles:array<int,array<string,mixed>>,agents:array<int,array<string,mixed>>,categories:array<int,array<string,mixed>>,types:array<int,array<string,string>>} */
  public function options(): array {
    return [
      'roles' => array_map(static fn(SupportRole $role): array => ['slug' => $role->slug(), 'name' => $role->name()], $this->eligibleRoles()),
      'agents' => array_map(static fn(WP_User $user): array => ['id' => $user->ID, 'name' => $user->display_name, 'email' => $user->user_email], $this->eligibleAgents()),
      'categories' => array_map(static fn($category): array => ['id' => $category->id(), 'name' => $category->name()], $this->categories->active()),
      'types' => [
        ['value' => 'role', 'label' => 'Assign to Role'],
        ['value' => 'agent', 'label' => 'Assign to Agent'],
        ['value' => 'notify', 'label' => 'Notify Agent'],
      ],
    ];
  }

  /** @param array<string, mixed> $data @return array<string, mixed> */
  private function normalize(array $data): array {
    $type = AssignRuleType::tryFrom(sanitize_key((string) ($data['rule_type'] ?? '')));
    if (! $type) { throw new InvalidArgumentException('Please select a valid rule type.'); }
    $status = AssignRuleStatus::tryFrom(sanitize_key((string) ($data['status'] ?? AssignRuleStatus::ACTIVE->value)));
    if (! $status) { throw new InvalidArgumentException('Assign rule status is invalid.'); }
    $allCategories = filter_var($data['all_categories'] ?? false, FILTER_VALIDATE_BOOL);
    $categoryIds = $allCategories ? [] : array_values(array_unique(array_filter(array_map('absint', (array) ($data['category_ids'] ?? [])))));
    if (! $allCategories && $categoryIds === []) { throw new InvalidArgumentException('Choose at least one active category or select All Categories.'); }
    foreach ($categoryIds as $categoryId) {
      $category = $this->categories->find($categoryId);
      if (! $category || ! $category->isActive()) { throw new InvalidArgumentException('Assign rules can only use active categories.'); }
    }
    $targetRole = null;
    $targetAgentId = null;
    if ($type === AssignRuleType::ROLE) {
      $targetRole = sanitize_key((string) ($data['target_role'] ?? ''));
      $role = $this->roles->find($targetRole);
      if (! $role || ! $role->isActive() || ! $role->isSupportRole()) { throw new InvalidArgumentException('Please select an active support role.'); }
    } else {
      $targetAgentId = absint($data['target_agent_id'] ?? 0);
      $agent = get_userdata($targetAgentId);
      if (! $agent || ! user_can($agent, CapabilityManager::VIEW_TICKETS)) { throw new InvalidArgumentException('Please select an available support agent or manager.'); }
    }
    return ['rule_type' => $type->value, 'target_role' => $targetRole, 'target_agent_id' => $targetAgentId, 'category_ids' => $categoryIds, 'status' => $status->value];
  }

  /** @return SupportRole[] */
  private function eligibleRoles(): array {
    return array_values(array_filter($this->roles->all(), static fn(SupportRole $role): bool => $role->isActive() && $role->isSupportRole()));
  }

  /** @return WP_User[] */
  private function eligibleAgents(): array {
    return array_values(array_filter(get_users(['orderby' => 'display_name', 'order' => 'ASC']), static fn(WP_User $user): bool => user_can($user, CapabilityManager::VIEW_TICKETS)));
  }

  private function agentForRole(string $role): ?int {
    $ids = array_map('absint', get_users(['role' => $role, 'fields' => 'ids']));
    $ids = array_values(array_filter($ids, static function(int $id): bool {
      $user = get_userdata($id);
      return $user instanceof WP_User && user_can($user, CapabilityManager::VIEW_TICKETS);
    }));
    if ($ids === []) { return null; }
    $counts = $this->ticketRepository->activeAssignmentCounts($ids);
    usort($ids, static fn(int $a, int $b): int => ($counts[$a] ?? 0) <=> ($counts[$b] ?? 0) ?: $a <=> $b);
    return $ids[0];
  }

  private function notify(AssignRule $rule, Ticket $ticket): void {
    $agent = get_userdata((int) $rule->targetAgentId());
    if (! $agent || ! user_can($agent, CapabilityManager::VIEW_TICKETS)) { return; }
    $this->notifications->enqueue(new NotificationData(
      event: 'assign_rule_notification',
      recipient: (string) $agent->user_email,
      subject: sprintf('New ticket #%s: %s', $ticket->trackId(), $ticket->subject()),
      content: sprintf("Hello %s,\n\nA new ticket matching one of your category notification rules was created.\n\nTicket: #%s — %s\n\nView: %s", $agent->display_name, $ticket->trackId(), $ticket->subject(), admin_url('admin.php?page=supportbay&ticket=' . $ticket->id())),
      metadata: ['ticket_id' => $ticket->id(), 'user_id' => $agent->ID, 'assign_rule_id' => $rule->id()],
    ));
  }
}
