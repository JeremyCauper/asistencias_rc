<?php

namespace App\Services;

use RuntimeException;
use Illuminate\Support\Collection;

class JsonDB
{
    protected static array $schemas = [];
    protected string $path;
    protected array $wheres = [];
    protected ?array $orderBy = null;
    protected array $structure = [];
    protected ?array $selectFields = null;
    protected ?string $keyByField = null;

    public static function schema(string $table, array $schema): void
    {
        self::$schemas[$table] = $schema;
    }

    public static function table(string $table, array $structure = []): self
    {
        $instance = new self();

        $config_path = storage_path('config/jsons');
        if (!file_exists($config_path)) {
            mkdir($config_path, 0777, true); // crea carpetas recursivamente
        }

        $instance->path = "$config_path/{$table}.json";

        if (!empty($structure)) {
            self::$schemas[$table] = $structure;
        }

        $instance->structure = self::$schemas[$table] ?? [];

        if (!file_exists($instance->path)) {
            file_put_contents($instance->path, json_encode([], JSON_PRETTY_PRINT), LOCK_EX);
            chmod($instance->path, 0777);
        }

        return $instance;
    }

    public function get(): Collection
    {
        $items = $this->readJson();

        foreach ($this->wheres as $where) {
            [$field, $operator, $value] = $where;
            $items = array_filter($items, fn($item) => $this->evaluateWhere($item, $field, $operator, $value));
        }

        if ($this->orderBy) {
            [$field, $direction] = $this->orderBy;
            usort($items, fn($a, $b) => $this->compare($a, $b, $field, $direction));
        }

        $collection = collect(array_map(fn($item) => (object) $item, array_values($items)));

        if ($this->selectFields) {
            $collection = $collection->map(fn($item) => (object) array_intersect_key((array) $item, array_flip($this->selectFields)));
        }

        if ($this->keyByField) {
            $collection = $collection->keyBy($this->keyByField);
        }

        $this->reset();

        return $collection;
    }

    public function first(): ?object
    {
        return $this->get()->first();
    }

    public function insert(array $data): object
    {
        $items = $this->readJson();

        $this->checkUniqueConstraints($data, $items);
        $pkField = $this->getPrimaryKeyField();

        $max = array_reduce($items, fn($carry, $item) => max($carry, (int) ($item[$pkField] ?? 0)), 0);

        $data = $this->applyDefaults($data);
        $data[$pkField] = $max + 1;
        $data = $this->formatData($data);

        $items[] = $data;
        $this->writeJson($items);

        return (object) $data;
    }

    public function update(array $data): ?int
    {
        $items = $this->readJson();
        $updated = 0;
        $pkField = $this->getPrimaryKeyField();

        foreach ($items as &$item) {
            if ($this->matchesWhere($item)) {
                $this->checkUniqueConstraints($data, $items, $pkField, $item[$pkField]);
                $item = array_merge($item, $data);
                $item = $this->formatData($item);
                $updated++;
            }
        }

        if ($updated > 0) {
            $this->writeJson($items);
        }

        $this->reset();

        return $updated;
    }

    public function delete(): bool
    {
        $items = $this->readJson();
        $found = false;

        foreach ($items as $k => $item) {
            if ($this->matchesWhere($item)) {
                unset($items[$k]);
                $found = true;
            }
        }

        if ($found) {
            $this->writeJson($items);
        }

        $this->reset();

        return $found;
    }

    public function where(string $field, string $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = [$field, $operator, $value];
        return $this;
    }

    public function whereIn(string $field, array $values): self
    {
        $this->wheres[] = [$field, 'in', $values];
        return $this;
    }

    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $this->orderBy = [$field, strtolower($direction)];
        return $this;
    }

    public function select(string ...$fields): self
    {
        $this->selectFields = $fields;
        return $this;
    }

    public function keyBy(string $field): Collection
    {
        $this->keyByField = $field;
        return $this->get();
    }

    protected function checkUniqueConstraints(array $data, array $items, string $ignoreField = null, $ignoreValue = null): void
    {
        foreach ($this->structure as $field => $typeInfo) {
            foreach (explode('|', $typeInfo) as $part) {
                if (str_starts_with($part, 'unique')) {
                    if (!isset($data[$field]))
                        continue;
                    $label = $field;
                    if (preg_match('/unique:"([^"]+)"/', $part, $m)) {
                        $label = $m[1];
                    }
                    foreach ($items as $item) {
                        if ($ignoreField && $item[$ignoreField] == $ignoreValue)
                            continue;
                        if (isset($item[$field]) && $item[$field] == $data[$field]) {
                            throw new RuntimeException("El valor ingresado <b>({$data[$field]})</b>, ya existe en el campo <b>$label</b>.", 409);
                        }
                    }
                }
            }
        }
    }

    protected function getPrimaryKeyField(): string
    {
        foreach ($this->structure as $f => $typeInfo) {
            $parts = explode('|', $typeInfo);
            if (in_array('primary_key', $parts)) {
                return $f;
            }
        }
        throw new RuntimeException('No se ha definido ningún campo primary_key en el schema.');
    }

    protected function evaluateWhere(array $item, string $field, string $operator, mixed $value): bool
    {
        if (!isset($item[$field]))
            return false;
        switch ($operator) {
            case '=':
                return $item[$field] == $value;
            case '!=':
                return $item[$field] != $value;
            case '>':
                return $item[$field] > $value;
            case '<':
                return $item[$field] < $value;
            case '>=':
                return $item[$field] >= $value;
            case '<=':
                return $item[$field] <= $value;
            case 'in':
                return in_array($item[$field], (array) $value);
        }
        return false;
    }

    protected function compare(array $a, array $b, string $field, string $direction): int
    {
        $aVal = $a[$field] ?? null;
        $bVal = $b[$field] ?? null;
        if ($aVal == $bVal)
            return 0;
        return ($direction === 'desc')
            ? (($aVal < $bVal) ? 1 : -1)
            : (($aVal > $bVal) ? 1 : -1);
    }

    protected function readJson(): array
    {
        $contents = file_get_contents($this->path);
        $data = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('JSON inválido en: ' . $this->path);
        }

        return $data;
    }

    protected function writeJson(array $data): void
    {
        if (!is_writable($this->path)) {
            throw new RuntimeException("El archivo no tiene permisos de escritura, Comuniquese con el area de sistemas.", 403);
        }
        file_put_contents($this->path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    protected function matchesWhere(array $item): bool
    {
        foreach ($this->wheres as [$field, $operator, $value]) {
            if (!isset($item[$field]))
                return false;

            switch ($operator) {
                case '=':
                    if ($item[$field] != $value)
                        return false;
                    break;
                case '!=':
                    if ($item[$field] == $value)
                        return false;
                    break;
                case '>':
                    if ($item[$field] <= $value)
                        return false;
                    break;
                case '<':
                    if ($item[$field] >= $value)
                        return false;
                    break;
                case '>=':
                    if ($item[$field] < $value)
                        return false;
                    break;
                case '<=':
                    if ($item[$field] > $value)
                        return false;
                    break;
                case 'in':
                    if (!in_array($item[$field], (array) $value))
                        return false;
                    break;
            }
        }
        return true;
    }

    protected function applyDefaults(array $data): array
    {
        foreach ($this->structure as $field => $typeInfo) {
            if (!array_key_exists($field, $data)) {
                if (str_contains($typeInfo, 'default:')) {
                    preg_match('/default:(.*)/', $typeInfo, $matches);
                    $default = trim($matches[1], '"');
                    $data[$field] = is_numeric($default) ? (int) $default : $default;
                } else {
                    $data[$field] = null;
                }
            }
        }
        return $data;
    }

    protected function reset(): void
    {
        $this->wheres = [];
        $this->orderBy = null;
        $this->selectFields = null;
        $this->keyByField = null;
    }

    private function formatData(array $data): array
    {
        $schema = $this->structure;

        $formatted = [];

        foreach ($schema as $key => $typeInfo) {
            if (array_key_exists($key, $data)) {
                $typeParts = explode('|', $typeInfo);
                $type = $typeParts[0];

                $value = $data[$key];

                switch ($type) {
                    case 'int':
                        $value = (int) $value;
                        break;
                    case 'float':
                        $value = (float) $value;
                        break;
                    case 'array':
                        $value = (array) $value;
                        break;
                    case 'string':
                        $value = (string) $value;
                        break;
                    case 'bool':
                        $value = (bool) $value;
                        break;
                }

                $formatted[$key] = $value;
            }
        }

        return $formatted;
    }
}
