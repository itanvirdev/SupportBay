<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Modules\Verifications\Entities\Verification;
use SupportBay\Modules\Verifications\Enums\VerificationStatus;
use SupportBay\Modules\Verifications\Data\VerificationDirectoryItem;
use SupportBay\Modules\Verifications\Data\VerificationDirectoryQuery;
use SupportBay\Modules\Customers\Database\CustomerSchema;
use SupportBay\Modules\Tickets\Database\TicketSchema;

final class VerificationRepository extends Repository {
  /**
   * Database table.
   */
  protected function table(): string {
    return $this->db->prefix . 'sbay_purchase_verifications';
  }

  /**
   * Hydrate entity.
   */
  protected function hydrate(array $row): Verification {
    return new Verification(
      id: (int) $row['id'],
      provider: $row['provider'],
      providerReference: $row['provider_reference'],
      customerId: $row['customer_id'] !== null
        ? (int) $row['customer_id']
        : null,
      providerCustomerReference: $row['provider_customer_reference'],
      productId: $row['product_id'],
      productName: $row['product_name'],
      licenseType: $row['license_type'],
      supportExpiresAt: $row['support_expires_at'],
      purchasedAt: $row['purchased_at'],
      verifiedAt: $row['verified_at'],
      lastCheckedAt: $row['last_checked_at'],
      status: VerificationStatus::from($row['verification_status']),
      providerSnapshot: $row['provider_snapshot'],
      metadata: $row['metadata'],
      createdAt: $row['created_at'],
      updatedAt: $row['updated_at'],
    );
  }

  /**
   * Find by provider reference.
   */
  public function findByReference(
    string $provider,
    string $reference,
  ): ?Verification {
    /** @var Verification|null */
    return $this->first([
      'provider'           => $provider,
      'provider_reference' => $reference,
    ]);
  }

  /**
   * Find customer verifications.
   *
   * @return Verification[]
   */
  public function findByCustomer(int $customerId): array {
    /** @var Verification[] */
    return $this->findWhere([
      'customer_id' => $customerId,
    ]);
  }

  /**
   * Find provider verifications.
   *
   * @return Verification[]
   */
  public function findByProvider(string $provider): array {
    /** @var Verification[] */
    return $this->findWhere([
      'provider' => $provider,
    ]);
  }

  /**
   * Find by verification status.
   *
   * @return Verification[]
   */
  public function findByStatus(
    VerificationStatus $status,
  ): array {
    /** @var Verification[] */
    return $this->findWhere([
      'verification_status' => $status->value,
    ]);
  }

  /**
   * Find verified purchases for a customer.
   *
   * @return Verification[]
   */
  public function findVerifiedByCustomer(
    int $customerId,
  ): array {
    /** @var Verification[] */
    return $this->findWhere([
      'customer_id'         => $customerId,
      'verification_status' => VerificationStatus::VERIFIED->value,
    ]);
  }

  /** @return array{items: VerificationDirectoryItem[], total: int} */
  public function search(VerificationDirectoryQuery $query): array {
    $verificationTable = $this->table();
    $customerTable = CustomerSchema::tableName();
    $ticketTable = TicketSchema::tableName();
    $userTable = $this->db->users;
    $clauses = [];
    $values = [];

    if ($query->provider !== null) {
      $clauses[] = 'v.provider = %s';
      $values[] = $query->provider;
    }

    if ($query->status !== null) {
      $clauses[] = 'v.verification_status = %s';
      $values[] = $query->status;
    }

    if ($query->search !== null && $query->search !== '') {
      $like = '%' . $this->db->esc_like($query->search) . '%';
      $clauses[] = '(v.provider_reference LIKE %s OR v.product_name LIKE %s OR v.product_id LIKE %s OR v.provider_customer_reference LIKE %s OR u.display_name LIKE %s)';
      array_push($values, $like, $like, $like, $like, $like);
    }

    $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
    $countSql = "SELECT COUNT(*) FROM {$verificationTable} v LEFT JOIN {$customerTable} c ON c.id = v.customer_id LEFT JOIN {$userTable} u ON u.ID = c.user_id {$where}";
    $total = (int) ($values
      ? $this->db->get_var($this->db->prepare($countSql, ...$values))
      : $this->db->get_var($countSql));
    $ticketAggregate = "(SELECT purchase_verification_id, COUNT(*) ticket_count FROM {$ticketTable} WHERE purchase_verification_id IS NOT NULL GROUP BY purchase_verification_id) tq";
    $orderBy = match ($query->orderBy) {
      'product' => 'v.product_name',
      'provider' => 'v.provider',
      'status' => 'v.verification_status',
      'support_expires_at' => 'v.support_expires_at',
      'verified_at' => 'v.verified_at',
      default => 'v.updated_at',
    };
    $direction = strtoupper($query->direction) === 'ASC' ? 'ASC' : 'DESC';
    $sql = "SELECT v.*, u.display_name customer_name, COALESCE(tq.ticket_count, 0) ticket_count FROM {$verificationTable} v LEFT JOIN {$customerTable} c ON c.id = v.customer_id LEFT JOIN {$userTable} u ON u.ID = c.user_id LEFT JOIN {$ticketAggregate} ON tq.purchase_verification_id = v.id {$where} ORDER BY {$orderBy} {$direction}, v.id DESC LIMIT %d OFFSET %d";
    $rows = $this->db->get_results($this->db->prepare(
      $sql,
      ...[...$values, $query->perPage, ($query->page - 1) * $query->perPage],
    ), ARRAY_A);

    return [
      'items' => array_map(static fn(array $row): VerificationDirectoryItem => new VerificationDirectoryItem($row), $rows),
      'total' => $total,
    ];
  }

  /** @return string[] */
  public function providerSlugs(): array {
    return array_values(array_filter(array_map(
      'sanitize_key',
      $this->db->get_col("SELECT DISTINCT provider FROM {$this->table()} ORDER BY provider ASC"),
    )));
  }
}
