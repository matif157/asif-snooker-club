<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class BaseModel
{
    protected string $table = '';

    public function __get(string $name)
    {
        $data = $this->toArray();
        return $data[$name] ?? null;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public static function all(string $orderBy = 'id DESC'): array
    {
        $model = new static();
        return Database::query("SELECT * FROM {$model->table} ORDER BY {$orderBy}");
    }

    public static function find(int $id): ?static
    {
        $model = new static();
        $row = Database::fetchOne("SELECT * FROM {$model->table} WHERE id = ?", [$id]);

        if ($row === null) {
            return null;
        }

        $instance = new static();
        foreach ($row as $key => $value) {
            $instance->$key = $value;
        }

        return $instance;
    }

    public static function findBy(string $column, mixed $value): ?static
    {
        $model = new static();
        $row = Database::fetchOne(
            "SELECT * FROM {$model->table} WHERE {$column} = ? LIMIT 1",
            [$value]
        );

        if ($row === null) {
            return null;
        }

        $instance = new static();
        foreach ($row as $key => $value) {
            $instance->$key = $value;
        }

        return $instance;
    }

    public static function where(string $column, mixed $value): array
    {
        $model = new static();
        return Database::query(
            "SELECT * FROM {$model->table} WHERE {$column} = ?",
            [$value]
        );
    }

    public static function create(array $data): int
    {
        $model = new static();
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$model->table} ({$columns}) VALUES ({$placeholders})";

        return Database::insert($sql, $data);
    }

    public function update(array $data): bool
    {
        if (!isset($this->id)) {
            return false;
        }

        $sets = [];
        $params = ['id' => $this->id];

        foreach ($data as $key => $value) {
            $sets[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id";
        Database::execute($sql, $params);

        // Refresh this instance
        $fresh = static::find((int) $this->id);
        if ($fresh) {
            foreach ($fresh->toArray() as $key => $value) {
                $this->$key = $value;
            }
        }

        return true;
    }

    public function delete(): bool
    {
        if (!isset($this->id)) {
            return false;
        }

        Database::execute("DELETE FROM {$this->table} WHERE id = ?", [$this->id]);
        return true;
    }

    public static function count(string $where = '1=1', array $params = []): int
    {
        $model = new static();
        $row = Database::fetchOne(
            "SELECT COUNT(*) AS c FROM {$model->table} WHERE {$where}",
            $params
        );
        return (int) ($row['c'] ?? 0);
    }
}