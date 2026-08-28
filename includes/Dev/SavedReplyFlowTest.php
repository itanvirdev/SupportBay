<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use InvalidArgumentException;
use SupportBay\Core\Database\DatabaseInstaller;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\SavedReplies\Enums\SavedReplyStatus;
use SupportBay\Modules\SavedReplies\Services\SavedReplyService;

final class SavedReplyFlowTest extends FlowTest {
  protected static function title(): string { return 'Saved Reply Flow Test'; }

  protected static function execute(...$services): void {
    /** @var SavedReplyService $replies */
    [$replies] = $services;
    DatabaseInstaller::install();
    $reply = $replies->create([
      'title' => '  Request site details  ',
      'content' => '<p>Please send the URL.</p><script>alert(1)</script>',
      'category' => 'Technical Support',
    ], 1);
    $second = $replies->create(['title' => 'Account access', 'content' => '<p>Please provide access.</p>', 'category' => 'Accounts', 'department_id' => 2], 1);

    try {
      Assert::true($reply->id() > 0 && $reply->title() === 'Request site details' && $reply->isActive(), 'A sanitized active saved reply is created.');
      Assert::true(str_contains($reply->content(), '<p>') && ! str_contains($reply->content(), '<script'), 'Saved reply rich text uses the shared safe formatting policy.');
      Assert::count(1, $replies->search('site details', SavedReplyStatus::ACTIVE), 'Active saved replies are searchable by content or title.');
      Assert::true($reply->category() === 'Technical Support' && count($replies->search('', SavedReplyStatus::ACTIVE, 'title', 'Technical Support')) === 1, 'Saved replies store sanitized categories and support exact category filtering.');
      Assert::count(12, $replies->placeholders(), 'Saved replies advertise only the approved ticket-context placeholder catalog.');
      $departmentTwoIds = array_map(static fn($item): int => $item->id(), $replies->search('', SavedReplyStatus::ACTIVE, 'title', null, 2, true));
      Assert::true(in_array($reply->id(), $departmentTwoIds, true) && in_array($second->id(), $departmentTwoIds, true), 'Department scope includes global and matching replies.');
      $departmentThreeIds = array_map(static fn($item): int => $item->id(), $replies->search('', SavedReplyStatus::ACTIVE, 'title', null, 3, true));
      Assert::true(in_array($reply->id(), $departmentThreeIds, true) && ! in_array($second->id(), $departmentThreeIds, true), 'Department scope excludes replies owned by another department.');
      $used = $replies->recordUsage($reply->id(), 1);
      Assert::true($used !== null && $used->usageCount() === 1 && $used->lastUsedBy() === 1 && $used->lastUsedAt() !== null, 'Active saved-reply insertion is tracked atomically with staff context.');
      $replies->recordUsage($second->id(), 1);
      $replies->recordUsage($second->id(), 1);
      Assert::equals($second->id(), $replies->search('', SavedReplyStatus::ACTIVE, 'usage')[0]->id(), 'Usage sorting prioritizes the most frequently inserted active reply.');

      $updated = $replies->update($reply->id(), ['title' => 'Request access', 'status' => SavedReplyStatus::INACTIVE->value]);
      Assert::true($updated !== null && $updated->title() === 'Request access' && ! $updated->isActive(), 'Saved replies can be updated and deactivated.');
      Assert::true(! in_array($reply->id(), array_map(static fn($item): int => $item->id(), $replies->search('', SavedReplyStatus::ACTIVE)), true), 'Inactive saved replies are excluded from the active collection.');
      Assert::true($replies->recordUsage($reply->id(), 1) === null, 'Inactive saved replies cannot record new insertions.');

      $invalidRejected = false;
      try { $replies->create(['title' => '', 'content' => ''], 1); } catch (InvalidArgumentException) { $invalidRejected = true; }
      Assert::true($invalidRejected, 'Empty saved replies are rejected.');
    } finally {
      $replies->delete($reply->id());
      $replies->delete($second->id());
    }

    Assert::true($replies->find($reply->id()) === null, 'Saved replies can be deleted cleanly.');
  }
}
