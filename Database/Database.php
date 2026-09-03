<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Database;

use PDO;
use PDOException;
use TDarkCoder\Framework\Exceptions\ServerErrorException;

class Database
{
    private ?PDO $pdo = null;

    public function __construct(private readonly array $config = [])
    {
    }

    public function migrator(?string $path = null): Migrator
    {
        return new Migrator($this, $path);
    }

    /**
     * @throws ServerErrorException
     */
    public function pdo(): PDO
    {
        return $this->pdo ??= $this->connect();
    }

    /**
     * @throws ServerErrorException
     */
    public function refreshDatabase(): void
    {
        $this->migrator()->refresh();
    }

    /**
     * @throws ServerErrorException
     */
    public function rollbackMigrations(): void
    {
        $this->migrator()->rollback();
    }

    /**
     * @throws ServerErrorException
     */
    public function runMigrations(): void
    {
        $this->migrator()->run();
    }

    /**
     * @throws ServerErrorException
     */
    private function connect(): PDO
    {
        $config = $this->config ?: (config('database') ?? []);

        try {
            return new PDO(
                $config['dsn'] ?? '',
                $config['username'] ?? null,
                $config['password'] ?? null,
                ($config['options'] ?? []) + [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            );
        } catch (PDOException $exception) {
            throw new ServerErrorException('Could not connect to the database', previous: $exception);
        }
    }
}
