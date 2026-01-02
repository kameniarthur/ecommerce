<?php
// app/helpers/Validator.php

class Validator
{
    private $data;
    private $rules;
    private $errors = [];
    private $messages;

    public function __construct($data, $rules, $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    public function validate()
    {
        foreach ($this->rules as $field => $rules) {
            $rules = explode('|', $rules);
            foreach ($rules as $rule) {
                $this->applyRule($field, $rule);
            }
        }
        return $this;
    }

    private function applyRule($field, $rule)
    {
        $value = $this->data[$field] ?? null;

        if (strpos($rule, ':') !== false) {
            list($ruleName, $params) = explode(':', $rule, 2);
            $params = explode(',', $params);
        } else {
            $ruleName = $rule;
            $params = [];
        }

        $method = lcfirst($ruleName);
        if (method_exists($this, $method)) {
            $this->$method($field, $value, $params);
        }
    }

    public function required($field, $value)
    {
        if (empty($value) && $value !== '0') {
            $this->errors[$field][] = $this->messages['required'] ?? "Le champ {$field} est obligatoire.";
        }
    }

    public function email($field, $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $this->messages['email'] ?? "Le format de l'email est invalide.";
        }
    }

    public function min($field, $value, $params)
    {
        if (strlen($value) < $params[0]) {
            $this->errors[$field][] = $this->messages['min'] ?? "Le champ {$field} doit contenir au moins {$params[0]} caractères.";
        }
    }

    public function max($field, $value, $params)
    {
        if (strlen($value) > $params[0]) {
            $this->errors[$field][] = $this->messages['max'] ?? "Le champ {$field} ne doit pas dépasser {$params[0]} caractères.";
        }
    }

    public function numeric($field, $value)
    {
        if (!is_numeric($value)) {
            $this->errors[$field][] = $this->messages['numeric'] ?? "Le champ {$field} doit être un nombre.";
        }
    }

    public function unique($field, $value, $params)
    {
        $table = $params[0];
        $column = $params[1] ?? $field;
        $stmt = Database::getInstance()->query("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?", [$value]);
        if ($stmt->fetchColumn() > 0) {
            $this->errors[$field][] = $this->messages['unique'] ?? "La valeur de {$field} existe déjà.";
        }
    }

    public function confirmed($field, $value, $params)
    {
        $confirmField = $params[0] ?? $field . '_confirmation';
        if ($value !== ($this->data[$confirmField] ?? null)) {
            $this->errors[$field][] = $this->messages['confirmed'] ?? "La confirmation de {$field} ne correspond pas.";
        }
    }

    public function between($field, $value, $params)
    {
        $min = $params[0];
        $max = $params[1];
        if (!is_numeric($value) || $value < $min || $value > $max) {
            $this->errors[$field][] = $this->messages['between'] ?? "La valeur de {$field} doit être entre {$min} et {$max}.";
        }
    }

    public function in($field, $value, $params)
    {
        if (!in_array($value, $params)) {
            $this->errors[$field][] = $this->messages['in'] ?? "La valeur de {$field} n'est pas autorisée.";
        }
    }

    public function passes()
    {
        return empty($this->errors);
    }

    public function fails()
    {
        return !$this->passes();
    }

    public function errors()
    {
        return $this->errors;
    }

    public function firstError($field)
    {
        return $this->errors[$field][0] ?? null;
    }
}
