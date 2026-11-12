<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;

    private function __construct(?array $config = null, bool $autoConnect = true)
    {
        if (!$autoConnect) {
            return;
        }

        $config = $config ?? require __DIR__ . '/../../config/database.php';
        $driver = $config['driver'] ?? 'mysql';

        try {
            if ($driver === 'sqlite') {
                $dbFile = $config['sqlite']['database'] ?? ':memory:';
                $options = $config['sqlite']['options'] ?? [];
                $this->pdo = new PDO('sqlite:' . $dbFile, null, null, $options);
                $this->pdo->exec('PRAGMA foreign_keys = ON;');
            } else {
                $my = $config['mysql'];
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $my['host'],
                    $my['port'],
                    $my['database'],
                    $my['charset']
                );
                $this->pdo = new PDO($dsn, $my['username'], $my['password'], $my['options']);
            }
        } catch (PDOException $e) {
            throw new RuntimeException('Error de conexión a la base de datos: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public static function getInstance(?array $config = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public static function setConnection(PDO $pdo): void
    {
        if (self::$instance === null) {
            self::$instance = new self(null, false);
        }
        self::$instance->pdo = $pdo;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            throw new RuntimeException('La conexión PDO no está inicializada.');
        }
        return $this->pdo;
    }

    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }

    public function rollBack(): bool
    {
        if ($this->getConnection()->inTransaction()) {
            return $this->getConnection()->rollBack();
        }
        return false;
    }

    public function inTransaction(): bool
    {
        return $this->getConnection()->inTransaction();
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return $this->getConnection()->lastInsertId($name);
    }
}
