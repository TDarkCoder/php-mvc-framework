<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Database;

use ArrayAccess;
use JsonSerializable;
use PDO;
use PDOStatement;
use TDarkCoder\Framework\Exceptions\NotFoundException;
use TDarkCoder\Framework\Exceptions\ServerErrorException;

/**
 * @implements ArrayAccess<string, mixed>
 */
abstract class Model implements ArrayAccess, JsonSerializable
{
    protected array $data = [];
    protected array $fillable = [];
    protected array $hidden = [];

    public bool $exists = false;
    public string $primaryKey = 'id';

    final public function __construct()
    {
    }

    abstract public function table(): string;

    public function __get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function __unset(string $key): void
    {
        unset($this->data[$key]);
    }

    /**
     * @return static[]
     * @throws ServerErrorException
     */
    public static function all(): array
    {
        return static::select('SELECT * FROM ' . static::tableName());
    }

    /**
     * @throws ServerErrorException
     */
    public static function create(array $attributes): static
    {
        return (new static())->fill($attributes)->save();
    }

    /**
     * @throws ServerErrorException
     */
    public function delete(): bool
    {
        if (is_null($this->getKey())) {
            return false;
        }

        $statement = static::query(
            sprintf('DELETE FROM %s WHERE %s = ?', $this->table(), static::wrap($this->primaryKey)),
            [$this->getKey()],
        );

        $this->exists = false;

        return $statement->rowCount() > 0;
    }

    /**
     * Mass assign attributes, allowing only the fillable ones.
     *
     * @throws ServerErrorException
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (!in_array($key, $this->fillable, true)) {
                throw new ServerErrorException(sprintf('Attribute [%s] is not fillable on %s', $key, static::class));
            }

            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * @return static[]
     * @throws ServerErrorException
     */
    public static function findAll(array $conditions): array
    {
        [$where, $bindings] = static::compileWhere($conditions);

        return static::select('SELECT * FROM ' . static::tableName() . $where, $bindings);
    }

    /**
     * @throws ServerErrorException
     */
    public static function findOne(array $conditions): ?static
    {
        [$where, $bindings] = static::compileWhere($conditions);

        return static::selectOne('SELECT * FROM ' . static::tableName() . $where . ' LIMIT 1', $bindings);
    }

    /**
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public static function findOrFail(array $conditions): static
    {
        return static::findOne($conditions) ?? throw new NotFoundException();
    }

    public function getKey(): mixed
    {
        return $this->data[$this->primaryKey] ?? null;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * Insert the model, or update it when it was loaded from the database.
     *
     * @throws ServerErrorException
     */
    public function save(): static
    {
        if (!$this->exists) {
            $this->performInsert($this->data);

            return $this;
        }

        $attributes = $this->data;

        unset($attributes[$this->primaryKey]);

        if ($attributes !== []) {
            $this->performUpdate($attributes);
        }

        return $this;
    }

    /**
     * Attributes without the hidden ones, for output and JSON.
     */
    public function toArray(): array
    {
        return array_diff_key($this->data, array_flip($this->hidden));
    }

    /**
     * @throws ServerErrorException
     */
    public function update(array $attributes): bool
    {
        if (is_null($this->getKey())) {
            return false;
        }

        $this->fill($attributes);

        unset($attributes[$this->primaryKey]);

        if ($attributes !== []) {
            $this->performUpdate($attributes);
        }

        return true;
    }

    /**
     * @return array{0: string, 1: array}
     * @throws ServerErrorException
     */
    protected static function compileWhere(array $conditions): array
    {
        if ($conditions === []) {
            return ['', []];
        }

        $clauses = $bindings = [];

        foreach ($conditions as $column => $value) {
            if (is_null($value)) {
                $clauses[] = static::wrap((string) $column) . ' IS NULL';

                continue;
            }

            $clauses[] = static::wrap((string) $column) . ' = ?';
            $bindings[] = $value;
        }

        return [' WHERE ' . implode(' AND ', $clauses), $bindings];
    }

    protected static function hydrate(array $row): static
    {
        $model = new static();
        $model->data = $row;
        $model->exists = true;

        return $model;
    }

    /**
     * Prepare and execute a query with positional bindings.
     *
     * @throws ServerErrorException
     */
    protected static function query(string $sql, array $bindings = []): PDOStatement
    {
        $statement = app()->database->pdo()->prepare($sql);

        foreach (array_values($bindings) as $index => $value) {
            $statement->bindValue($index + 1, $value, match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            });
        }

        $statement->execute();

        return $statement;
    }

    /**
     * Run a query and hydrate every row into a model.
     *
     * @return static[]
     * @throws ServerErrorException
     */
    protected static function select(string $sql, array $bindings = []): array
    {
        $rows = static::query($sql, $bindings)->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static fn(array $row): static => static::hydrate($row), $rows);
    }

    /**
     * @throws ServerErrorException
     */
    protected static function selectOne(string $sql, array $bindings = []): ?static
    {
        $row = static::query($sql, $bindings)->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : static::hydrate($row);
    }

    protected static function tableName(): string
    {
        return (new static())->table();
    }

    /**
     * Quote a column name, rejecting anything that is not a plain identifier.
     *
     * @throws ServerErrorException
     */
    protected static function wrap(string $identifier): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) !== 1) {
            throw new ServerErrorException("Invalid identifier [$identifier]");
        }

        return "`$identifier`";
    }

    /**
     * @throws ServerErrorException
     */
    private function performInsert(array $attributes): void
    {
        $columns = array_map(static fn(string|int $column): string => static::wrap((string) $column), array_keys($attributes));

        static::query(
            sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                $this->table(),
                implode(', ', $columns),
                implode(', ', array_fill(0, count($columns), '?')),
            ),
            array_values($attributes),
        );

        if (is_null($this->getKey())) {
            $id = app()->database->pdo()->lastInsertId();

            if ($id !== '' && $id !== '0') {
                $this->data[$this->primaryKey] = is_numeric($id) ? (int) $id : $id;
            }
        }

        $this->exists = true;
    }

    /**
     * @throws ServerErrorException
     */
    private function performUpdate(array $attributes): void
    {
        $sets = array_map(static fn(string|int $column): string => static::wrap((string) $column) . ' = ?', array_keys($attributes));

        static::query(
            sprintf('UPDATE %s SET %s WHERE %s = ?', $this->table(), implode(', ', $sets), static::wrap($this->primaryKey)),
            [...array_values($attributes), $this->getKey()],
        );
    }
}
