<?php

namespace TDarkCoder\Framework\Database;

use Exception;
use PDO;
use TDarkCoder\Framework\Exceptions\ServerErrorException;

class Database
{
    private array $loadedMigrations = [];
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

            $this->pdo->exec($this->loadMigration($migration)->down());
            $this->forgetMigration($migration);

            $this->log("Rolled back migration $migration");
        }

        $this->log('Migrations rollback completed');
    }

    public function refreshDatabase(): void
    {
        $statement = $this->pdo->query("SHOW TABLES");

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `$table`");

            $this->log("Deleted table: $table");
        }

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        $this->runMigrations();

        $this->log('Database refreshed');
    }

    public function runMigrations(): void
    {
        $this->createMigrationsTable();

        $files = array_map('basename', glob(basePath('/migrations/*.php')) ?: []);
        $migrations = array_diff($files, $this->appliedMigrations());

        if (empty($migrations)) {
            $this->log('All the migrations are applied');

            return;
        }

        sort($migrations);

        foreach ($migrations as $migration) {
            $this->log("Applying migration $migration");

            $this->pdo->exec($this->loadMigration($migration)->up());
            $this->saveMigration($migration);

            $this->log("Applied migration $migration");
        }
    }

    private function appliedMigrations(): array
    {
        return $this->pdo->query("SELECT `migration` FROM `migrations` ORDER BY `id`")->fetchAll(PDO::FETCH_COLUMN);
    }

    private function createMigrationsTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL UNIQUE,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=INNODB
        ");
    }

    private function forgetMigration(string $migration): void
    {
        $statement = $this->pdo->prepare("DELETE FROM `migrations` WHERE `migration` = :migration");
        $statement->execute(['migration' => $migration]);
    }

    /**
     * @throws ServerErrorException
     */
    private function loadMigration(string $migration): Migration
    {
        if (!isset($this->loadedMigrations[$migration])) {
            $this->loadedMigrations[$migration] = require basePath("/migrations/$migration");
        }

        $instance = $this->loadedMigrations[$migration];

        if (!$instance instanceof Migration) {
            throw new ServerErrorException("Migration $migration must return an instance of " . Migration::class);
        }

        return $instance;
    }

    private function log(string $message): void
    {
        echo sprintf('[%s] - %s' . PHP_EOL, date('Y-m-d H:i:s'), $message);
    }

    private function saveMigration(string $migration): void
    {
        $statement = $this->pdo->prepare("INSERT INTO `migrations` (`migration`) VALUES (:migration)");
        $statement->execute(['migration' => $migration]);
    }
}
