<?php

namespace Elqora\Dgp\Snapshots;

use DateTimeImmutable;
use Exception;

final class OrderSnapshotValidator
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, string>
     */
    public static function validate(array $payload): array
    {
        $errors = [];
        self::exactKeys($payload, [
            'version', 'mode', 'built_at', 'product_id', 'definition_schema_version',
            'selection', 'inputs', 'quantity', 'quantity_source', 'min', 'max',
            'service_ids', 'service_ids_by_node', 'fallbacks', 'utilities', 'meta',
        ], '', $errors);

        if (($payload['version'] ?? null) !== '1') $errors['version'] = 'Version must be "1".';
        if (!in_array($payload['mode'] ?? null, ['prod', 'dev'], true)) $errors['mode'] = 'Mode must be "prod" or "dev".';
        if (!self::isDateTime($payload['built_at'] ?? null)) $errors['built_at'] = 'Built_at must be an RFC 3339 date-time string.';
        if (!self::isServiceId($payload['product_id'] ?? null)) $errors['product_id'] = 'Product_id must be a string or integer.';
        if (($payload['definition_schema_version'] ?? null) !== '1') $errors['definition_schema_version'] = 'Definition_schema_version must be "1".';

        self::validateSelection($payload['selection'] ?? null, $errors);
        self::validateInputs($payload['inputs'] ?? null, $errors);

        if (!self::isFiniteNumber($payload['quantity'] ?? null)) $errors['quantity'] = 'Quantity must be a finite number.';
        self::validateQuantitySource($payload['quantity_source'] ?? null, $errors);

        foreach (['min', 'max'] as $key) {
            if (!is_int($payload[$key] ?? null)) $errors[$key] = ucfirst($key).' must be an integer.';
        }
        if (is_int($payload['min'] ?? null) && is_int($payload['max'] ?? null) && $payload['min'] > $payload['max']) {
            $errors['min'] = 'Min cannot exceed max.';
        }

        self::validateServiceIdList($payload['service_ids'] ?? null, 'service_ids', $errors);
        self::validateServiceMap($payload['service_ids_by_node'] ?? null, 'service_ids_by_node', $errors);
        self::validateFallbacks($payload['fallbacks'] ?? null, $errors);
        self::validateUtilities($payload['utilities'] ?? null, $errors);

        $meta = $payload['meta'] ?? null;
        if (!self::isObjectRepresentation($meta) || !self::isJsonValue($meta)) {
            $errors['meta'] = 'Meta must be a JSON-compatible object representation.';
        }

        return $errors;
    }

    /** @param array<string, string> $errors */
    private static function validateSelection(mixed $selection, array &$errors): void
    {
        if (!self::isObjectRepresentation($selection)) {
            $errors['selection'] = 'Selection must be an object representation.';
            return;
        }

        self::exactKeys($selection, ['filter_id', 'trigger_ids', 'fields'], 'selection', $errors);
        if (!is_string($selection['filter_id'] ?? null)) $errors['selection.filter_id'] = 'Filter_id must be a string.';
        self::validateStringList($selection['trigger_ids'] ?? null, 'selection.trigger_ids', $errors);

        $fields = $selection['fields'] ?? null;
        if (!is_array($fields) || !array_is_list($fields)) {
            $errors['selection.fields'] = 'Fields must be an array.';
            return;
        }
        foreach ($fields as $index => $field) {
            $path = "selection.fields.{$index}";
            if (!self::isObjectRepresentation($field)) {
                $errors[$path] = 'Field selection must be an object representation.';
                continue;
            }
            self::exactKeys($field, ['field_id', 'field_type', 'selected_option_ids'], $path, $errors);
            if (!is_string($field['field_id'] ?? null)) $errors["{$path}.field_id"] = 'Field_id must be a string.';
            if (!is_string($field['field_type'] ?? null)) $errors["{$path}.field_type"] = 'Field_type must be a string.';
            self::validateStringList($field['selected_option_ids'] ?? null, "{$path}.selected_option_ids", $errors);
        }
    }

    /** @param array<string, string> $errors */
    private static function validateInputs(mixed $inputs, array &$errors): void
    {
        if (!self::isObjectRepresentation($inputs)) {
            $errors['inputs'] = 'Inputs must be an object representation.';
            return;
        }
        self::exactKeys($inputs, ['form', 'selections'], 'inputs', $errors);

        $form = $inputs['form'] ?? null;
        if (!self::isObjectRepresentation($form)) {
            $errors['inputs.form'] = 'Form must be an object representation.';
        } else {
            foreach ($form as $key => $value) {
                if (!self::isJsonValue($value)) $errors['inputs.form.'.(string) $key] = 'Form values must be JSON-compatible.';
            }
        }

        $selections = $inputs['selections'] ?? null;
        if (!self::isObjectRepresentation($selections)) {
            $errors['inputs.selections'] = 'Selections must be an object representation.';
        } else {
            foreach ($selections as $key => $value) {
                self::validateStringList($value, 'inputs.selections.'.(string) $key, $errors);
            }
        }
    }

    /** @param array<string, string> $errors */
    private static function validateQuantitySource(mixed $source, array &$errors): void
    {
        if (!self::isObjectRepresentation($source)) {
            $errors['quantity_source'] = 'Quantity_source must be an object representation.';
            return;
        }
        self::exactKeys($source, ['kind', 'node_id', 'rule', 'defaulted_from_host'], 'quantity_source', $errors);

        $kind = $source['kind'] ?? null;
        $nodeId = $source['node_id'] ?? null;
        $rule = $source['rule'] ?? null;
        $defaulted = $source['defaulted_from_host'] ?? null;
        if ($kind === 'field_rule') {
            if (!is_string($nodeId)) $errors['quantity_source.node_id'] = 'Field-rule node_id must be a string.';
            if ($defaulted !== false) $errors['quantity_source.defaulted_from_host'] = 'Field-rule sources cannot be host defaults.';
            self::validateQuantityRule($rule, $errors);
            return;
        }
        if (in_array($kind, ['option_default', 'field_default', 'filter_default'], true)) {
            if (!is_string($nodeId)) $errors['quantity_source.node_id'] = 'Definition-default node_id must be a string.';
            if ($rule !== null) $errors['quantity_source.rule'] = 'Definition-default sources cannot contain a rule.';
            if ($defaulted !== false) $errors['quantity_source.defaulted_from_host'] = 'Definition-default sources cannot be host defaults.';
            return;
        }
        if ($kind === 'host_default') {
            if ($nodeId !== null) $errors['quantity_source.node_id'] = 'Host-default node_id must be null.';
            if ($rule !== null) $errors['quantity_source.rule'] = 'Host-default rule must be null.';
            if ($defaulted !== true) $errors['quantity_source.defaulted_from_host'] = 'Host-default sources must be marked as host defaults.';
            return;
        }
        $errors['quantity_source.kind'] = 'Quantity source kind is invalid.';
    }

    /** @param array<string, string> $errors */
    private static function validateQuantityRule(mixed $rule, array &$errors): void
    {
        if (!self::isObjectRepresentation($rule)) {
            $errors['quantity_source.rule'] = 'Field-rule sources require a quantity rule.';
            return;
        }
        self::knownKeys($rule, ['value_by', 'expression', 'multiply', 'clamp', 'fallback'], 'quantity_source.rule', $errors);
        if (!array_key_exists('value_by', $rule)) $errors['quantity_source.rule.value_by'] = 'Value_by is required.';
        $valueBy = $rule['value_by'] ?? null;
        if (!in_array($valueBy, ['value', 'length', 'eval'], true)) $errors['quantity_source.rule.value_by'] = 'Value_by is invalid.';

        if ($valueBy === 'eval') {
            $expression = $rule['expression'] ?? null;
            if (!self::isObjectRepresentation($expression)) {
                $errors['quantity_source.rule.expression'] = 'Eval rules require an expression.';
            } else {
                self::exactKeys($expression, ['language', 'body'], 'quantity_source.rule.expression', $errors);
                if (($expression['language'] ?? null) !== 'javascript') $errors['quantity_source.rule.expression.language'] = 'Expression language must be javascript.';
                if (!is_string($expression['body'] ?? null) || $expression['body'] === '') $errors['quantity_source.rule.expression.body'] = 'Expression body must be non-empty.';
            }
        } elseif (array_key_exists('expression', $rule)) {
            $errors['quantity_source.rule.expression'] = 'Only eval rules may contain an expression.';
        }

        foreach (['multiply', 'fallback'] as $key) {
            if (array_key_exists($key, $rule) && !self::isFiniteNumber($rule[$key])) {
                $errors["quantity_source.rule.{$key}"] = ucfirst($key).' must be a finite number.';
            }
        }
        if (array_key_exists('clamp', $rule)) {
            $clamp = $rule['clamp'];
            if (!self::isObjectRepresentation($clamp)) {
                $errors['quantity_source.rule.clamp'] = 'Clamp must be an object representation.';
            } else {
                self::knownKeys($clamp, ['min', 'max'], 'quantity_source.rule.clamp', $errors);
                foreach (['min', 'max'] as $key) {
                    if (array_key_exists($key, $clamp) && !self::isFiniteNumber($clamp[$key])) {
                        $errors["quantity_source.rule.clamp.{$key}"] = ucfirst($key).' must be a finite number.';
                    }
                }
            }
        }
    }

    /** @param array<string, string> $errors */
    private static function validateFallbacks(mixed $fallbacks, array &$errors): void
    {
        if ($fallbacks === null) return;
        if (!self::isObjectRepresentation($fallbacks)) {
            $errors['fallbacks'] = 'Fallbacks must be null or an object representation.';
            return;
        }
        self::knownKeys($fallbacks, ['nodes', 'global'], 'fallbacks', $errors);
        foreach (['nodes', 'global'] as $key) {
            if (array_key_exists($key, $fallbacks)) self::validateServiceMap($fallbacks[$key], "fallbacks.{$key}", $errors);
        }
    }

    /** @param array<string, string> $errors */
    private static function validateUtilities(mixed $utilities, array &$errors): void
    {
        if (!is_array($utilities) || !array_is_list($utilities)) {
            $errors['utilities'] = 'Utilities must be an array.';
            return;
        }
        foreach ($utilities as $index => $utility) {
            $path = "utilities.{$index}";
            if (!self::isObjectRepresentation($utility)) {
                $errors[$path] = 'Utility must be an object representation.';
                continue;
            }
            self::exactKeys($utility, ['node_id', 'mode', 'rate', 'percent_base', 'label', 'inputs', 'advisory_amount'], $path, $errors);
            if (!is_string($utility['node_id'] ?? null)) $errors["{$path}.node_id"] = 'Node_id must be a string.';
            if (!in_array($utility['mode'] ?? null, ['flat', 'per_quantity', 'per_value', 'percent'], true)) $errors["{$path}.mode"] = 'Utility mode is invalid.';
            if (!self::isFiniteNumber($utility['rate'] ?? null)) $errors["{$path}.rate"] = 'Rate must be a finite number.';
            if (!in_array($utility['percent_base'] ?? null, ['service_total', 'base_service', 'all', null], true)) $errors["{$path}.percent_base"] = 'Percent_base is invalid.';
            if (($utility['label'] ?? null) !== null && !is_string($utility['label'])) $errors["{$path}.label"] = 'Label must be a string or null.';
            if (!self::isFiniteNumber($utility['advisory_amount'] ?? null)) $errors["{$path}.advisory_amount"] = 'Advisory_amount must be a finite number.';
            self::validateUtilityInputs($utility['inputs'] ?? null, "{$path}.inputs", $errors);
        }
    }

    /** @param array<string, string> $errors */
    private static function validateUtilityInputs(mixed $inputs, string $path, array &$errors): void
    {
        if (!self::isObjectRepresentation($inputs)) {
            $errors[$path] = 'Utility inputs must be an object representation.';
            return;
        }
        self::exactKeys($inputs, ['quantity', 'value', 'value_by', 'base_amount'], $path, $errors);
        if (!self::isFiniteNumber($inputs['quantity'] ?? null)) $errors["{$path}.quantity"] = 'Quantity must be a finite number.';
        foreach (['value', 'base_amount'] as $key) {
            if (($inputs[$key] ?? null) !== null && !self::isFiniteNumber($inputs[$key])) $errors["{$path}.{$key}"] = ucfirst($key).' must be a finite number or null.';
        }
        if (!in_array($inputs['value_by'] ?? null, ['value', 'length', null], true)) $errors["{$path}.value_by"] = 'Value_by is invalid.';
    }

    /** @param array<string, string> $errors */
    private static function validateServiceMap(mixed $map, string $path, array &$errors): void
    {
        if (!self::isObjectRepresentation($map)) {
            $errors[$path] = ucfirst(str_replace('_', ' ', $path)).' must be an object representation.';
            return;
        }
        foreach ($map as $key => $ids) self::validateServiceIdList($ids, $path.'.'.(string) $key, $errors);
    }

    /** @param array<string, string> $errors */
    private static function validateServiceIdList(mixed $ids, string $path, array &$errors): void
    {
        if (!is_array($ids) || !array_is_list($ids)) {
            $errors[$path] = 'Service IDs must be an array.';
            return;
        }
        foreach ($ids as $index => $id) {
            if (!self::isServiceId($id)) $errors["{$path}.{$index}"] = 'Service IDs must be strings or integers.';
        }
    }

    /** @param array<string, string> $errors */
    private static function validateStringList(mixed $values, string $path, array &$errors): void
    {
        if (!is_array($values) || !array_is_list($values)) {
            $errors[$path] = 'Value must be an array of strings.';
            return;
        }
        foreach ($values as $index => $value) {
            if (!is_string($value)) $errors["{$path}.{$index}"] = 'Value must be a string.';
        }
    }

    private static function isServiceId(mixed $value): bool
    {
        return is_string($value) || is_int($value);
    }

    private static function isFiniteNumber(mixed $value): bool
    {
        return is_int($value) || (is_float($value) && is_finite($value));
    }

    private static function isJsonValue(mixed $value): bool
    {
        if ($value === null || is_string($value) || is_bool($value) || is_int($value)) return true;
        if (is_float($value)) return is_finite($value);
        if (!is_array($value)) return false;
        foreach ($value as $item) {
            if (!self::isJsonValue($item)) return false;
        }
        return true;
    }

    private static function isObjectRepresentation(mixed $value): bool
    {
        return is_array($value) && ($value === [] || !array_is_list($value));
    }

    private static function isDateTime(mixed $value): bool
    {
        if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/', $value) !== 1) return false;
        try {
            new DateTimeImmutable($value);
            $parsed = date_parse($value);
            return $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string> $expected
     * @param array<string, string> $errors
     */
    private static function exactKeys(array $value, array $expected, string $path, array &$errors): void
    {
        $missing = array_diff($expected, array_keys($value));
        $unknown = array_diff(array_keys($value), $expected);
        if ($missing !== [] || $unknown !== []) {
            $key = $path === '' ? '$' : $path;
            $errors[$key] = sprintf('Wire keys differ from the v1 contract; missing [%s], unknown [%s].', implode(', ', $missing), implode(', ', $unknown));
        }
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string> $known
     * @param array<string, string> $errors
     */
    private static function knownKeys(array $value, array $known, string $path, array &$errors): void
    {
        $unknown = array_diff(array_keys($value), $known);
        if ($unknown !== []) $errors[$path] = sprintf('Unknown wire keys [%s].', implode(', ', $unknown));
    }
}
