<?php

namespace App\Model\UseCase\Ports;

/**
 * Port interface for the ImportAttemptsUseCase (attempt persistence).
 *
 * Provides the minimal operation needed to persist student attempts in bulk.
 */
interface AttemptBulkInserterPort
{
    /**
     * Bulk insert attempt rows within a single transaction.
     *
     * Required to efficiently persist large batches of imported attempts.
     *
     * @param array<array<string,mixed>> $rows Rows to insert. Each row must contain:
     *                                         exercice_id, user, correct, eval_set, upload, aes0, aes1, aes2.
     * @return array{inserted:int, errors:list<string>} Result summary.
     */
    public function bulkInsert(array $rows): array;
}

