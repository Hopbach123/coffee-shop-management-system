<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminMenuWriteLock
{
    public function run(callable $operation): mixed
    {
        $connection = DB::connection();
        if ($connection->transactionLevel() !== 0) {
            throw new \LogicException('Menu writes require a top-level transaction.');
        }
        $pdo = null;
        $name = 'admin-menu:'.substr(hash('sha256', $connection->getDatabaseName()), 0, 48);
        try {
            return $connection->transaction(function () use ($connection, $operation, $name, &$pdo) {
                if ($connection->getDriverName() === 'mysql') {
                    $writer = $connection->getPdo();
                    try {
                        $statement = $writer->prepare('SELECT GET_LOCK(?, 5)');
                        $statement->execute([$name]);
                    } catch (\PDOException) {
                        throw ValidationException::withMessages(['menu' => 'Không thể khóa dữ liệu menu. Vui lòng thử lại.']);
                    }
                    if ((int) $statement->fetchColumn() !== 1) {
                        throw ValidationException::withMessages(['menu' => 'Menu đang được chỉnh sửa. Vui lòng thử lại.']);
                    }
                    $pdo = $writer;
                } elseif ($connection->getDriverName() !== 'sqlite' || ! app()->environment('testing')) {
                    throw ValidationException::withMessages(['menu' => 'Môi trường lưu menu chưa được hỗ trợ.']);
                }

                return $operation();
            });
        } finally {
            if ($pdo !== null) {
                try {
                    $statement = $pdo->prepare('SELECT RELEASE_LOCK(?)');
                    $statement->execute([$name]);
                    if ((int) $statement->fetchColumn() !== 1) {
                        $connection->disconnect();
                    }
                } catch (\Throwable) {
                    // Terminate the session if explicit release fails after commit/rollback.
                    $connection->disconnect();
                }
            }
        }
    }
}
