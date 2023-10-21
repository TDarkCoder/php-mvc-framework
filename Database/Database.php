<?php

namespace TDarkCoder\Framework\Database;

use Exception;
use PDO;
use TDarkCoder\Framework\Exceptions\ServerErrorException;

class Database
{
    private PDO $pdo;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        try {
            $this->pdo = new PDO(config('database.dsn'), config('database.username'), config('database.password'));

            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception) {
            throw new ServerErrorException();
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function rollbackMigrations(): void
    {
        foreach (array_reverse($this->appliedMigrations()) as $migration) {
            $this->log("Rolling back migration $migration");

            $migrationClass = include_once basePath("/migrations/$migration");

            $this->pdo->exec($migrationClass->down());

            $this->log("Rolled back migration $migration");
        }

        $this->pdo->exec("TRUNCATE TABLE `migrations`");

        $this->log('Migrations rollback completed');
    }

    public function refreshDatabase(): void
    {
        $statement = $this->pdo->prepare("SHOW TABLES");
        $statement->execute();

        foreach ($statement->fetchAll() ?? [] as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS $table[0]");

            $this->log("Deleted table: $table[0]");
        }

        $this->runMigrations();

        $this->log('Database refreshed');
    }

    public function runMigrations(): void
    {
        $newMigrations = [];
        $this->createMigrationsTable();

        $migrations = array_diff(scandir(basePath('/migrations')), ['.', '..', ...$this->appliedMigrations()]);

        foreach ($migrations as $migration) {
            $this->log("Applying migration $migration");

            $newMigrations[] = $migration;
            $migrationClass = include_once basePath("/migrations/$migration");

            $this->pdo->exec($migrationClass->up());

            $this->log("Applied migration $migration");
        }

        if (!empty($newMigrations)) {
            $this->saveMigrations($newMigrations);
        } else {
            $this->log('All the migrations are applied');
        }
    }

    private function appliedMigrations(): array
    {
        $statement = $this->pdo->prepare("SELECT `migration` from `migrations`");
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_COLUMN) ?? [];
    }

    private function createMigrationsTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=INNODB
        ");
    }

    private function log(string $message): void
    {
        echo sprintf('[%s] - %s' . PHP_EOL, date('Y-m-d H:i:s'), $message);
    }

    private function saveMigrations(array $migrations): void
    {
        $migrations = implode(',', array_map(fn(string $migration): string => "('$migration')", $migrations));

        $statement = $this->pdo->prepare("INSERT INTO `migrations` (`migration`) VALUES $migrations");
        $statement->execute();
    }
}