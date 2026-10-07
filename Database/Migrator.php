<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Database;

use Closure;
use PDO;
use TDarkCoder\Framework\Contracts\Migration;
use TDarkCoder\Framework\Exceptions\ServerErrorException;

class Migrator
{
    private array $loaded = [];
    private ?Closure $output = null;
    private readonly string $path;

    public function __construct(private readonly Database $database, ?string $path = null)
    {
        $this->path = rtrim($path ?? config('database.migrations') ?? basePath('/migrations'), '/');
    }

    /**
     * Receive log lines through a callback instead of having them echoed.
     */
    public function onOutput(Closure $callback): static
    {
        $this->output = $callback;

        return $this;
    }

    /**
     * @throws ServerErrorException
     */
    public function refresh(): void
    {
        $tables = $this->pdo()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        $this->pdo()->exec('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($tables as $table) {
            $this->pdo()->exec("DROP TABLE IF EXISTS `$table`");

            $this->log("Deleted table: $table");
        }

        $this->pdo()->exec('SET FOREIGN_KEY_CHECKS = 1');

        $this->run();

        $this->log('Database refreshed');
    }

    /**
     * @throws ServerErrorException
     */
    public function rollback(): void
    {
        $this->createTable();

        foreach (array_reverse($this->applied()) as $migration) {
            $this->log("Rolling back migration $migration");

            $this->pdo()->exec($this->load($migration)->down());
            $this->forget($migration);

            $this->log("Rolled back migration $migration");
        }

        $this->log('Migrations rollback completed');
    }

    /**
     * @throws ServerErrorException
     */
    public function run(): void
    {
        $this->createTable();

        $migrations = $this->pending();

        if ($migrations === []) {
            $this->log('All the migrations are applied');

            return;
        }

        foreach ($migrations as $migration) {
            $this->log("Applying migration $migration");

            $this->pdo()->exec($this->load($migration)->up());
            $this->record($migration);

            $this->log("Applied migration $migration");
        }
    }

    /**
     * @return string[]
     * @throws ServerErrorException
     */
    private function applied(): array
    {
        return $this->pdo()->query('SELECT `migration` FROM `migrations` ORDER BY `id`')->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @throws ServerErrorException
     */
    private function createTable(): void
    {
        $this->pdo()->exec('
            CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL UNIQUE,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=INNODB
        ');
    }

    /**
     * @throws ServerErrorException
     */
    private function forget(string $migration): void
    {
        $statement = $this->pdo()->prepare('DELETE FROM `migrations` WHERE `migration` = :migration');
        $statement->execute(['migration' => $migration]);
    }

    /**
     * Migration files return a Migration instance. Loaded instances are
     * cached so a refresh can roll back and re-run within one process.
     *
     * @throws ServerErrorException
     */
    private function load(string $migration): Migration
    {
        $file = "$this->path/$migration";

        if (!is_file($file)) {
            throw new ServerErrorException("Migration file [$migration] not found");
        }

        $this->loaded[$migration] ??= require $file;

        if (!$this->loaded[$migration] instanceof Migration) {
            throw new ServerErrorException("Migration $migration must return an instance of " . Migration::class);
        }

        return $this->loaded[$migration];
    }

    private function log(string $message): void
    {
        $line = sprintf('[%s] - %s', date('Y-m-d H:i:s'), $message);

        if ($this->output) {
            ($this->output)($line);

            return;
        }

        echo $line . PHP_EOL;
    }

    /**
     * @throws ServerErrorException
     */
    private function pdo(): PDO
    {
        return $this->database->pdo();
    }

    /**
     * @return string[]
     * @throws ServerErrorException
     */
    private function pending(): array
    {
        $files = array_map('basename', glob("$this->path/*.php") ?: []);
        $pending = array_values(array_diff($files, $this->applied()));

        sort($pending);

        return $pending;
    }

    /**
     * @throws ServerErrorException
     */
    private function record(string $migration): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO `migrations` (`migration`) VALUES (:migration)');
        $statement->execute(['migration' => $migration]);
    }
}
