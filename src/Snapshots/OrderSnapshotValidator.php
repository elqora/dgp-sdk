<?php

namespace Elqora\Dgp\Snapshots;

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
        if (!is_string($payload['built_at'] ?? null) || strtotime($payload['built_at']) === false) $errors['built_at'] = 'Built_at must be a date-time string.';
        if (!is_string($payload['product_id'] ?? null) && !is_int($payload['product_id'] ?? null)) $errors['product_id'] = 'Product_id must be a string or integer.';
        if (($payload['definition_schema_version'] ?? null) !== '1') $errors['definition_schema_version'] = 'Definition_schema_version must be "1".';

        $selection = $payload['selection'] ?? null;
        if (!is_array($selection)) {
            $errors['selection'] = 'Selection must be an object representation.';
        } else {
            self::exactKeys($selection, ['filter_id', 'trigger_ids', 'fields'], 'selection', $errors);
            if (!is_string($selection['filter_id'] ?? null)) $errors['selection.filter_id'] = 'Filter_id must be a string.';
            if (!is_array($selection['trigger_ids'] ?? null)) $errors['selection.trigger_ids'] = 'Trigger_ids must be an array.';
            if (!is_array($selection['fields'] ?? null)) {
                $errors['selection.fields'] = 'Fields must be an array.';
            } else {
                foreach ($selection['fields'] as $index => $field) {
                    if (!is_array($field)) { $errors["selection.fields.{$index}"] = 'Field selection must be an object representation.'; continue; }
                    self::exactKeys($field, ['field_id', 'field_type', 'selected_option_ids'], "selection.fields.{$index}", $errors);
                }
            }
        }

        $inputs = $payload['inputs'] ?? null;
        if (!is_array($inputs)) $errors['inputs'] = 'Inputs must be an object representation.';
        else self::exactKeys($inputs, ['form', 'selections'], 'inputs', $errors);

        if (!is_int($payload['quantity'] ?? null) && !is_float($payload['quantity'] ?? null)) $errors['quantity'] = 'Quantity must be numeric.';
        $source = $payload['quantity_source'] ?? null;
        if (!is_array($source)) $errors['quantity_source'] = 'Quantity_source must be an object representation.';
        else self::exactKeys($source, ['kind', 'node_id', 'rule', 'defaulted_from_host'], 'quantity_source', $errors);

        foreach (['min', 'max'] as $key) if (!is_int($payload[$key] ?? null)) $errors[$key] = ucfirst($key).' must be an integer.';
        if (is_int($payload['min'] ?? null) && is_int($payload['max'] ?? null) && $payload['min'] > $payload['max']) $errors['min'] = 'Min cannot exceed max.';
        foreach (['service_ids', 'service_ids_by_node', 'utilities', 'meta'] as $key) if (!is_array($payload[$key] ?? null)) $errors[$key] = ucfirst($key).' must be an array representation.';
        if (($payload['fallbacks'] ?? null) !== null && !is_array($payload['fallbacks'])) $errors['fallbacks'] = 'Fallbacks must be null or an object representation.';

        return $errors;
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
}
