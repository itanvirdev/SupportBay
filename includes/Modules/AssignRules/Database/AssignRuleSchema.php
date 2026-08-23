<?php

declare(strict_types=1);

namespace SupportBay\Modules\AssignRules\Database;

use SupportBay\Modules\AssignRules\Enums\AssignRuleStatus;

final class AssignRuleSchema {
  public static function tableName(): string {
    global $wpdb;
    return $wpdb->prefix . 'sbay_assign_rules';
  }

  public static function schema(): string {
    global $wpdb;
    return "CREATE TABLE " . self::tableName() . " (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      rule_type VARCHAR(20) NOT NULL,
      target_role VARCHAR(100) NULL,
      target_agent_id BIGINT UNSIGNED NULL,
      category_ids LONGTEXT NOT NULL,
      status VARCHAR(20) NOT NULL DEFAULT '" . AssignRuleStatus::ACTIVE->value . "',
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY rule_type (rule_type),
      KEY target_agent_id (target_agent_id),
      KEY status (status)
    ) {$wpdb->get_charset_collate()};";
  }
}
