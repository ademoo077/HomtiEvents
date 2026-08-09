<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Validation des entrées (email, requise, longueurs, dates, fichiers, etc.)
 */
final class Validator
{
    private array $errors = [];

    public function __construct(private readonly array $data, private readonly array $rules, private readonly array $messages = [])
    {
    }

    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $rules = explode('|', $ruleString);

            if ($value === null && in_array('nullable', $rules, true)) {
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }
                $this->applyRule($field, $value, $rule);
            }
        }

        return $this->errors === [];
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

        $ok = match ($name) {
            'required'        => $value !== null && $value !== '',
            'email'           => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'min'             => is_string($value) && mb_strlen($value) >= (int) $param,
            'max'             => (is_string($value) || is_array($value)) && count((array) $value) <= (int) $param,
            'string'          => is_string($value),
            'numeric'         => is_numeric($value),
            'integer'         => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'boolean'         => in_array($value, [true, false, 0, 1, '0', '1', 'on'], true),
            'date'            => $value !== null && $value !== '' && strtotime((string) $value) !== false,
            'date_after'      => $value !== null && $value !== '' && strtotime((string) $value) > strtotime((string) $param),
            'date_after_or_eq'=> $value !== null && $value !== '' && strtotime((string) $value) >= strtotime((string) $param),
            'between'         => $this->checkBetween($value, $param),
            'in'              => $value !== null && in_array($value, array_map('trim', explode(',', (string) $param)), true),
            'confirmed'       => ($this->data[$field . '_confirmation'] ?? null) === $value,
            'unique'          => ! $this->checkUnique($field, $value, (string) $param),
            'phone'           => is_string($value) && preg_match('/^[0-9+ ().-]{6,20}$/', $value) === 1,
            'uuid'            => is_string($value) && preg_match('/^[0-9a-fA-F-]{36}$/', $value) === 1,
            'url'             => is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false,
            'hex_color'       => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1,
            'array'           => is_array($value),
            'distinct'        => $this->checkDistinct($value),
            default           => true,
        };

        if (! $ok) {
            $this->addError($field, $name, $param);
        }
    }

    private function checkBetween(mixed $value, ?string $param): bool
    {
        if ($param === null) {
            return true;
        }
        [$min, $max] = array_map('intval', explode(',', $param));

        if (is_numeric($value)) {
            return $value >= $min && $value <= $max;
        }

        return is_string($value) && mb_strlen($value) >= $min && mb_strlen($value) <= $max;
    }

    private function checkUnique(string $field, mixed $value, string $param): bool
    {
        if ($value === null || $value === '' || $param === '') {
            return false;
        }

        [$table, $column, $ignoreId] = array_pad(explode(',', $param), 3, null);

        if ($ignoreId !== null) {
            return Database::exists(
                "SELECT id FROM {$table} WHERE {$column} = ? AND id <> ?",
                [$value, (int) $ignoreId]
            );
        }

        return Database::exists("SELECT id FROM {$table} WHERE {$column} = ?", [$value]);
    }

    private function checkDistinct(mixed $value): bool
    {
        if (! is_array($value)) {
            return true;
        }

        return count($value) === count(array_unique($value));
    }

    private function addError(string $field, string $rule, ?string $param): void
    {
        $key = "{$field}.{$rule}";
        $this->errors[$field] = $this->messages[$key]
            ?? $this->messages[$field . '.*']
            ?? $this->messages[$rule]
            ?? __("validation.{$rule}", ['attribute' => $field, 'param' => $param ?? '']);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors ? reset($this->errors) : null;
    }

    public function fails(): bool
    {
        return ! $this->validate();
    }
}
