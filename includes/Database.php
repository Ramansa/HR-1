<?php

declare(strict_types=1);

class wpdb
{
    private mysqli $mysqli;

    public function __construct(string $dbuser, string $dbpassword, string $dbname, string $dbhost)
    {
        $this->mysqli = new mysqli($dbhost, $dbuser, $dbpassword, $dbname);
        if ($this->mysqli->connect_error) {
            throw new RuntimeException('Database connection failed: ' . $this->mysqli->connect_error);
        }
        $this->mysqli->set_charset(DB_CHARSET);
    }

    public function get_results(string $query, string $output = MYSQLI_ASSOC): array
    {
        $result = $this->mysqli->query($query);
        if (!$result) {
            throw new RuntimeException('Query error: ' . $this->mysqli->error);
        }

        return $result->fetch_all($output);
    }

    public function get_row(string $query, string $output = MYSQLI_ASSOC): ?array
    {
        $result = $this->mysqli->query($query);
        if (!$result) {
            throw new RuntimeException('Query error: ' . $this->mysqli->error);
        }

        $row = $result->fetch_array($output);
        return $row ?: null;
    }

    public function query(string $query): bool
    {
        return (bool) $this->mysqli->query($query);
    }

    public function insert(string $table, array $data): bool
    {
        [$columns, $placeholders, $types, $values] = $this->buildInsertParts($data);
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Prepare failed: ' . $this->mysqli->error);
        }
        $stmt->bind_param($types, ...$values);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function update(string $table, array $data, array $where): bool
    {
        $sets = [];
        $types = '';
        $values = [];

        foreach ($data as $column => $value) {
            $sets[] = "{$column} = ?";
            $types .= $this->detectType($value);
            $values[] = $value;
        }

        $whereParts = [];
        foreach ($where as $column => $value) {
            $whereParts[] = "{$column} = ?";
            $types .= $this->detectType($value);
            $values[] = $value;
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $sets) . ' WHERE ' . implode(' AND ', $whereParts);
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Prepare failed: ' . $this->mysqli->error);
        }
        $stmt->bind_param($types, ...$values);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function delete(string $table, array $where): bool
    {
        $whereParts = [];
        $types = '';
        $values = [];

        foreach ($where as $column => $value) {
            $whereParts[] = "{$column} = ?";
            $types .= $this->detectType($value);
            $values[] = $value;
        }

        $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereParts);
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Prepare failed: ' . $this->mysqli->error);
        }
        $stmt->bind_param($types, ...$values);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function prepare(string $query, array $args = []): string
    {
        foreach ($args as $arg) {
            $safe = $this->mysqli->real_escape_string((string) $arg);
            $query = preg_replace('/%s/', "'{$safe}'", $query, 1);
            $query = preg_replace('/%d/', (string) ((int) $arg), $query, 1);
            $query = preg_replace('/%f/', (string) ((float) $arg), $query, 1);
        }

        return $query;
    }

    public function insert_id(): int
    {
        return $this->mysqli->insert_id;
    }

    private function buildInsertParts(array $data): array
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $types = '';
        $values = [];

        foreach ($data as $value) {
            $types .= $this->detectType($value);
            $values[] = $value;
        }

        return [$columns, $placeholders, $types, $values];
    }

    private function detectType(mixed $value): string
    {
        return match (true) {
            is_int($value) => 'i',
            is_float($value) => 'd',
            default => 's',
        };
    }
}
