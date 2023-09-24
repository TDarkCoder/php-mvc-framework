<?php

namespace TDarkCoder\Framework;

use TDarkCoder\Framework\Exceptions\NotFoundException;
use TDarkCoder\Framework\Exceptions\ServerErrorException;
use PDOStatement;

abstract class Model
{
    protected array $fillable = [];
    protected array $data = [];

    public string $primaryKey = 'id';

    abstract public function table(): string;

    public static function all(): array
    {
        $model = (new static());

        $statement = $model->prepare("SELECT * FROM " . $model->table());
        $statement->execute();

        return $statement->fetchAll() ?: [];
    }

    public static function findOne(array $params): ?static
    {
        $statement = (new static())->handleWhere($params);

        return $statement->fetchObject(static::class) ?: null;
    }

    /**
     * @throws NotFoundException
     */
    public static function findOrFail(array $params): ?static
    {
        $object = static::findOne($params);

        if (!$object) {
            throw new NotFoundException();
        }

        return $object;
    }

    public static function findAll(array $params): array
    {
        $statement = (new static())->handleWhere($params);

        return $statement->fetchAll() ?: [];
    }

    private function handleWhere(array $params): bool|PDOStatement
    {
        $where = [];

        foreach ($params as $key => $value) {
            $where[] = "`$key` = :$key";
        }

        $statement = $this->prepare("SELECT * FROM " . $this->table() . " WHERE " . implode('AND', $where));

        foreach ($params as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        $statement->execute();

        return $statement;
    }

    /**
     * @throws ServerErrorException
     */
    public static function create(array $data): static
    {
        $model = (new static());

        foreach ($data as $key => $value) {
            $model->{$key} = $value;
        }

        return $model->save();
    }

    /**
     * @throws ServerErrorException
     */
    public function save(): static
    {
        $keys = $attributes = [];

        foreach ($this->data as $key => $value) {
            if (!in_array($key, $this->fillable)) {
                throw new ServerErrorException('Mass assignment failed for ' . $this->table());
            }

            $keys[] = "`$key`";
            $attributes[] = ":$key";
        }

        $statement = $this->prepare("INSERT INTO " . $this->table() . " (" . implode(', ', $keys) . ") VALUES (" . implode(',', $attributes) . ")");

        foreach ($this->data as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        $statement->execute();

        $this->{$this->primaryKey} = app()->database->pdo()->lastInsertId();

        return $this;
    }

    public function update(array $data): bool
    {
        $operations = [];

        foreach ($data as $key => $operation) {
            $operations[] = "`$key` = :$key";
        }

        $statement = $this->prepare("UPDATE " . $this->table() . " SET " . implode(',', $operations) . " WHERE `" . $this->primaryKey . "` = :primaryKey");
        $statement->bindValue(":primaryKey", $this->{$this->primaryKey});

        foreach ($data as $key => $operation) {
            $statement->bindValue(":$key", $operation);
        }

        return $statement->execute();
    }

    public function delete(): bool
    {
        $statement = $this->prepare("DELETE FROM {$this->table()} WHERE {$this->primaryKey} = :primaryKey");
        $statement->bindValue(":primaryKey", $this->{$this->primaryKey});

        return $statement->execute();
    }

    private function prepare(string $query): PDOStatement|false
    {
        return app()->database->pdo()->prepare($query);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function __get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }
}