<?php

namespace Elqora\Dgp\Catalog\Schemas;

final class ProductDefinitionValidator
{
    /**
     * Validate the complete canonical ProductDefinition v1 wire structure.
     * Semantic publication rules are deliberately handled separately.
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function validate(array $data): array
    {
        $errors = [];
        self::exactKeys($data, [
            'id', 'name', 'filters', 'fields', 'order_for_tags', 'includes_for_buttons',
            'excludes_for_buttons', 'option_effects_for_buttons', 'value_effects_for_triggers',
            'schema_version', 'fallbacks', 'description', 'notices', 'meta',
        ], '', $errors);

        if (!self::isServiceId($data['id'] ?? null)) $errors['id'] = 'Id must be a string or integer.';
        if (!is_string($data['name'] ?? null)) $errors['name'] = 'Name must be a string.';
        if (($data['schema_version'] ?? null) !== ProductDefinition::SCHEMA_VERSION) {
            $errors['schema_version'] = 'Schema_version must be the string "1".';
        }
        if (($data['description'] ?? null) !== null && !is_string($data['description'])) {
            $errors['description'] = 'Description must be a string or null.';
        }
        self::validateFilters($data['filters'] ?? null, $errors);
        self::validateFields($data['fields'] ?? null, $errors);
        foreach (['order_for_tags', 'includes_for_buttons', 'excludes_for_buttons'] as $key) {
            self::validateStringMap($data[$key] ?? null, $key, $errors);
        }
        self::validateOptionEffects($data['option_effects_for_buttons'] ?? null, $errors);
        self::validateValueEffects($data['value_effects_for_triggers'] ?? null, $errors);
        self::validateFallbacks($data['fallbacks'] ?? null, $errors);
        self::validateNotices($data['notices'] ?? null, $errors);
        if (!self::isObjectRepresentation($data['meta'] ?? null) || !self::isJsonValue($data['meta'])) {
            $errors['meta'] = 'Meta must be a JSON-compatible object representation.';
        }
        self::validateRelationshipReferences($data, $errors);

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $errors
     */
    private static function validateRelationshipReferences(array $data, array &$errors): void
    {
        if (!is_array($data['filters'] ?? null) || !is_array($data['fields'] ?? null)) return;
        $nodeIds = [];
        $fieldIds = [];
        foreach ($data['filters'] as $filter) {
            if (is_array($filter) && is_string($filter['id'] ?? null)) $nodeIds[$filter['id']] = true;
        }
        foreach ($data['fields'] as $field) {
            if (!is_array($field) || !is_string($field['id'] ?? null)) continue;
            $nodeIds[$field['id']] = true;
            $fieldIds[$field['id']] = true;
            self::collectOptionIds($field['options'] ?? null, $nodeIds);
        }
        foreach (['option_effects_for_buttons', 'value_effects_for_triggers'] as $mapKey) {
            $map = $data[$mapKey] ?? null;
            if (!is_array($map)) continue;
            foreach ($map as $triggerId => $targets) {
                $triggerPath = $mapKey.'.'.(string) $triggerId;
                if (!isset($nodeIds[(string) $triggerId])) $errors[$triggerPath] = "Trigger ID '{$triggerId}' does not exist.";
                if (!is_array($targets)) continue;
                foreach ($targets as $fieldId => $_effect) {
                    if (!isset($fieldIds[(string) $fieldId])) $errors[$triggerPath.'.'.(string) $fieldId] = "Target field ID '{$fieldId}' does not exist.";
                }
            }
        }
    }

    /** @param array<string, true> $nodeIds */
    private static function collectOptionIds(mixed $options, array &$nodeIds): void
    {
        if (!is_array($options)) return;
        foreach ($options as $option) {
            if (!is_array($option)) continue;
            if (is_string($option['id'] ?? null)) $nodeIds[$option['id']] = true;
            self::collectOptionIds($option['children'] ?? null, $nodeIds);
        }
    }

    /** @param array<string, string> $errors */
    private static function validateFilters(mixed $filters, array &$errors): void
    {
        if (!is_array($filters) || !array_is_list($filters)) {
            $errors['filters'] = 'Filters must be an array.';
            return;
        }
        foreach ($filters as $index => $filter) {
            $path = "filters.{$index}";
            if (!self::isObjectRepresentation($filter)) {
                $errors[$path] = 'Filter must be an object representation.';
                continue;
            }
            self::knownKeys($filter, [
                'id', 'label', 'bind_id', 'service_id', 'includes', 'excludes',
                'capabilities', 'quantity_default', 'meta',
            ], $path, $errors);
            if (!is_string($filter['id'] ?? null)) $errors["{$path}.id"] = 'Filter id must be a string.';
            if (!is_string($filter['label'] ?? null)) $errors["{$path}.label"] = 'Filter label must be a string.';
            if (array_key_exists('bind_id', $filter) && !is_string($filter['bind_id'])) {
                $errors["{$path}.bind_id"] = 'Bind_id must be a string.';
            }
            if (array_key_exists('service_id', $filter) && !self::isServiceId($filter['service_id'])) {
                $errors["{$path}.service_id"] = 'Service_id must be a string or integer.';
            }
            foreach (['includes', 'excludes'] as $key) {
                if (array_key_exists($key, $filter)) self::validateStringList($filter[$key], "{$path}.{$key}", $errors);
            }
            if (array_key_exists('capabilities', $filter)) {
                $capabilities = $filter['capabilities'];
                if (!self::isObjectRepresentation($capabilities)) {
                    $errors["{$path}.capabilities"] = 'Capabilities must be an object representation.';
                } else {
                    foreach ($capabilities as $key => $required) {
                        if (!is_bool($required)) $errors["{$path}.capabilities.".(string) $key] = 'Capability requirements must be booleans.';
                    }
                }
            }
            self::validateOptionalFinite($filter, 'quantity_default', $path, $errors);
            self::validateOptionalJsonObject($filter, 'meta', $path, $errors);
        }
    }

    /** @param array<string, string> $errors */
    private static function validateFields(mixed $fields, array &$errors): void
    {
        if (!is_array($fields) || !array_is_list($fields)) {
            $errors['fields'] = 'Fields must be an array.';
            return;
        }
        foreach ($fields as $index => $field) {
            self::validateField($field, "fields.{$index}", $errors);
        }
    }

    /** @param array<string, string> $errors */
    private static function validateField(mixed $field, string $path, array &$errors): void
    {
        if (!self::isObjectRepresentation($field)) {
            $errors[$path] = 'Field must be an object representation.';
            return;
        }
        self::knownKeys($field, [
            'id', 'type', 'variant', 'label', 'bind_id', 'name', 'required', 'multiple',
            'default_value', 'defaults', 'options', 'description', 'pricing_role', 'validation',
            'quantity_default', 'quantity', 'utility', 'meta', 'button', 'service_id',
        ], $path, $errors);
        foreach (['id', 'type', 'label'] as $key) {
            if (!is_string($field[$key] ?? null)) $errors["{$path}.{$key}"] = ucfirst($key).' must be a string.';
        }
        foreach (['variant', 'name', 'description'] as $key) {
            if (array_key_exists($key, $field) && !is_string($field[$key])) {
                $errors["{$path}.{$key}"] = ucfirst($key).' must be a string.';
            }
        }
        foreach (['required', 'multiple', 'button'] as $key) {
            if (array_key_exists($key, $field) && !is_bool($field[$key])) {
                $errors["{$path}.{$key}"] = ucfirst($key).' must be a boolean.';
            }
        }
        if (array_key_exists('bind_id', $field)) {
            $bind = $field['bind_id'];
            if (is_string($bind)) {
                // valid scalar binding
            } elseif (is_array($bind) && array_is_list($bind)) {
                self::validateStringList($bind, "{$path}.bind_id", $errors);
            } else {
                $errors["{$path}.bind_id"] = 'Bind_id must be a string or array of strings.';
            }
        }
        if (array_key_exists('default_value', $field) && !self::isJsonValue($field['default_value'])) {
            $errors["{$path}.default_value"] = 'Default_value must be JSON-compatible.';
        }
        self::validateOptionalJsonObject($field, 'defaults', $path, $errors);
        self::validateOptionalJsonObject($field, 'meta', $path, $errors);
        if (array_key_exists('pricing_role', $field) && !in_array($field['pricing_role'], ['base', 'utility'], true)) {
            $errors["{$path}.pricing_role"] = 'Pricing_role is invalid.';
        }
        if (array_key_exists('service_id', $field)) {
            if (($field['button'] ?? null) !== true) $errors["{$path}.service_id"] = 'Only button fields may select a service.';
            if (!self::isServiceId($field['service_id'])) $errors["{$path}.service_id"] = 'Service_id must be a string or integer.';
        }
        if (array_key_exists('options', $field)) self::validateOptions($field['options'], "{$path}.options", $errors);
        if (array_key_exists('validation', $field)) self::validateRules($field['validation'], "{$path}.validation", $errors);
        self::validateOptionalFinite($field, 'quantity_default', $path, $errors);
        if (array_key_exists('quantity', $field)) self::validateQuantityRule($field['quantity'], "{$path}.quantity", $errors);
        if (array_key_exists('utility', $field)) self::validateUtility($field['utility'], "{$path}.utility", $errors);
    }

    /** @param array<string, string> $errors */
    private static function validateOptions(mixed $options, string $path, array &$errors): void
    {
        if (!is_array($options) || !array_is_list($options)) {
            $errors[$path] = 'Options must be an array.';
            return;
        }
        foreach ($options as $index => $option) {
            $optionPath = "{$path}.{$index}";
            if (!self::isObjectRepresentation($option)) {
                $errors[$optionPath] = 'Option must be an object representation.';
                continue;
            }
            self::knownKeys($option, [
                'id', 'label', 'value', 'service_id', 'pricing_role', 'quantity_default',
                'utility', 'meta', 'children',
            ], $optionPath, $errors);
            if (!is_string($option['id'] ?? null)) $errors["{$optionPath}.id"] = 'Option id must be a string.';
            if (!is_string($option['label'] ?? null)) $errors["{$optionPath}.label"] = 'Option label must be a string.';
            if (array_key_exists('value', $option) && !self::isJsonPrimitive($option['value'])) {
                $errors["{$optionPath}.value"] = 'Option value must be a JSON primitive.';
            }
            if (array_key_exists('service_id', $option) && !self::isServiceId($option['service_id'])) {
                $errors["{$optionPath}.service_id"] = 'Service_id must be a string or integer.';
            }
            if (array_key_exists('pricing_role', $option) && !in_array($option['pricing_role'], ['base', 'utility'], true)) {
                $errors["{$optionPath}.pricing_role"] = 'Pricing_role is invalid.';
            }
            self::validateOptionalFinite($option, 'quantity_default', $optionPath, $errors);
            self::validateOptionalJsonObject($option, 'meta', $optionPath, $errors);
            if (array_key_exists('utility', $option)) self::validateUtility($option['utility'], "{$optionPath}.utility", $errors);
            if (array_key_exists('children', $option)) self::validateOptions($option['children'], "{$optionPath}.children", $errors);
        }
    }

    /** @param array<string, string> $errors */
    private static function validateRules(mixed $rules, string $path, array &$errors): void
    {
        if (!is_array($rules) || !array_is_list($rules)) {
            $errors[$path] = 'Validation must be an array.';
            return;
        }
        foreach ($rules as $index => $rule) {
            $rulePath = "{$path}.{$index}";
            if (!self::isObjectRepresentation($rule)) {
                $errors[$rulePath] = 'Validation rule must be an object representation.';
                continue;
            }
            self::knownKeys($rule, [
                'op', 'value_by', 'expression', 'value', 'min', 'max', 'values',
                'pattern', 'pattern_flags', 'code', 'message',
            ], $rulePath, $errors);
            if (!in_array($rule['op'] ?? null, ['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'between', 'in', 'nin', 'truthy', 'falsy', 'match'], true)) {
                $errors["{$rulePath}.op"] = 'Validation operator is invalid.';
            }
            $valueBy = $rule['value_by'] ?? 'value';
            if (!in_array($valueBy, ['value', 'length', 'eval'], true)) $errors["{$rulePath}.value_by"] = 'Value_by is invalid.';
            if ($valueBy === 'eval') self::validateExpression($rule['expression'] ?? null, "{$rulePath}.expression", $errors);
            elseif (array_key_exists('expression', $rule)) $errors["{$rulePath}.expression"] = 'Only eval rules may contain an expression.';
            if (array_key_exists('value', $rule) && !self::isJsonValue($rule['value'])) $errors["{$rulePath}.value"] = 'Value must be JSON-compatible.';
            foreach (['min', 'max'] as $key) self::validateOptionalFinite($rule, $key, $rulePath, $errors);
            if (array_key_exists('values', $rule)) {
                if (!is_array($rule['values']) || !array_is_list($rule['values'])) $errors["{$rulePath}.values"] = 'Values must be an array.';
                elseif (!self::isJsonValue($rule['values'])) $errors["{$rulePath}.values"] = 'Values must be JSON-compatible.';
            }
            foreach (['pattern', 'pattern_flags', 'code', 'message'] as $key) {
                if (array_key_exists($key, $rule) && !is_string($rule[$key])) $errors["{$rulePath}.{$key}"] = ucfirst($key).' must be a string.';
            }
        }
    }

    /** @param array<string, string> $errors */
    private static function validateQuantityRule(mixed $rule, string $path, array &$errors): void
    {
        if (!self::isObjectRepresentation($rule)) {
            $errors[$path] = 'Quantity must be an object representation.';
            return;
        }
        self::knownKeys($rule, ['value_by', 'expression', 'multiply', 'clamp', 'fallback'], $path, $errors);
        $valueBy = $rule['value_by'] ?? null;
        if (!in_array($valueBy, ['value', 'length', 'eval'], true)) $errors["{$path}.value_by"] = 'Value_by is invalid.';
        if ($valueBy === 'eval') self::validateExpression($rule['expression'] ?? null, "{$path}.expression", $errors);
        elseif (array_key_exists('expression', $rule)) $errors["{$path}.expression"] = 'Only eval rules may contain an expression.';
        foreach (['multiply', 'fallback'] as $key) self::validateOptionalFinite($rule, $key, $path, $errors);
        if (array_key_exists('clamp', $rule)) {
            $clamp = $rule['clamp'];
            if (!self::isObjectRepresentation($clamp)) $errors["{$path}.clamp"] = 'Clamp must be an object representation.';
            else {
                self::knownKeys($clamp, ['min', 'max'], "{$path}.clamp", $errors);
                foreach (['min', 'max'] as $key) self::validateOptionalFinite($clamp, $key, "{$path}.clamp", $errors);
            }
        }
    }

    /** @param array<string, string> $errors */
    private static function validateExpression(mixed $expression, string $path, array &$errors): void
    {
        if (!self::isObjectRepresentation($expression)) {
            $errors[$path] = 'Expression must be an object representation.';
            return;
        }
        self::exactKeys($expression, ['language', 'body'], $path, $errors);
        if (($expression['language'] ?? null) !== 'javascript') $errors["{$path}.language"] = 'Expression language must be javascript.';
        if (!is_string($expression['body'] ?? null) || $expression['body'] === '') $errors["{$path}.body"] = 'Expression body must be non-empty.';
    }

    /** @param array<string, string> $errors */
    private static function validateUtility(mixed $utility, string $path, array &$errors): void
    {
        if (!self::isObjectRepresentation($utility)) {
            $errors[$path] = 'Utility must be an object representation.';
            return;
        }
        self::knownKeys($utility, ['rate', 'mode', 'value_by', 'percent_base', 'label'], $path, $errors);
        if (!self::isFiniteNumber($utility['rate'] ?? null)) $errors["{$path}.rate"] = 'Rate must be a finite number.';
        if (!in_array($utility['mode'] ?? null, ['flat', 'per_quantity', 'per_value', 'percent'], true)) $errors["{$path}.mode"] = 'Utility mode is invalid.';
        if (array_key_exists('value_by', $utility) && !in_array($utility['value_by'], ['value', 'length'], true)) $errors["{$path}.value_by"] = 'Value_by is invalid.';
        if (array_key_exists('percent_base', $utility) && !in_array($utility['percent_base'], ['service_total', 'base_service', 'all'], true)) $errors["{$path}.percent_base"] = 'Percent_base is invalid.';
        if (array_key_exists('label', $utility) && !is_string($utility['label'])) $errors["{$path}.label"] = 'Label must be a string.';
    }

    /** @param array<string, string> $errors */
    private static function validateOptionEffects(mixed $map, array &$errors): void
    {
        if (!self::isObjectRepresentation($map)) {
            $errors['option_effects_for_buttons'] = 'Option effects must be an object representation.';
            return;
        }
        foreach ($map as $trigger => $targets) {
            $path = 'option_effects_for_buttons.'.(string) $trigger;
            if (!self::isObjectRepresentation($targets)) {
                $errors[$path] = 'Effect targets must be an object representation.';
                continue;
            }
            foreach ($targets as $field => $effect) {
                $effectPath = $path.'.'.(string) $field;
                if (!self::isObjectRepresentation($effect)) {
                    $errors[$effectPath] = 'Option effect must be an object representation.';
                    continue;
                }
                self::knownKeys($effect, ['force_visible', 'include', 'exclude'], $effectPath, $errors);
                if (array_key_exists('force_visible', $effect) && !is_bool($effect['force_visible'])) $errors["{$effectPath}.force_visible"] = 'Force_visible must be a boolean.';
                foreach (['include', 'exclude'] as $key) {
                    if (array_key_exists($key, $effect)) self::validateStringList($effect[$key], "{$effectPath}.{$key}", $errors);
                }
            }
        }
    }

    /** @param array<string, string> $errors */
    private static function validateValueEffects(mixed $map, array &$errors): void
    {
        if (!self::isObjectRepresentation($map)) {
            $errors['value_effects_for_triggers'] = 'Value effects must be an object representation.';
            return;
        }
        foreach ($map as $trigger => $targets) {
            $path = 'value_effects_for_triggers.'.(string) $trigger;
            if (!self::isObjectRepresentation($targets)) {
                $errors[$path] = 'Effect targets must be an object representation.';
                continue;
            }
            foreach ($targets as $field => $effect) {
                $effectPath = $path.'.'.(string) $field;
                if (!self::isObjectRepresentation($effect)) {
                    $errors[$effectPath] = 'Value effect must be an object representation.';
                    continue;
                }
                self::knownKeys($effect, ['value', 'mode', 'clear_on_deactivate'], $effectPath, $errors);
                if (!array_key_exists('value', $effect) || !self::isJsonValue($effect['value'] ?? null)) $errors["{$effectPath}.value"] = 'Value must be JSON-compatible.';
                if (array_key_exists('mode', $effect) && !in_array($effect['mode'], ['always', 'if_empty'], true)) $errors["{$effectPath}.mode"] = 'Mode is invalid.';
                if (array_key_exists('clear_on_deactivate', $effect) && !is_bool($effect['clear_on_deactivate'])) $errors["{$effectPath}.clear_on_deactivate"] = 'Clear_on_deactivate must be a boolean.';
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
    private static function validateNotices(mixed $notices, array &$errors): void
    {
        if (!is_array($notices) || !array_is_list($notices)) {
            $errors['notices'] = 'Notices must be an array.';
            return;
        }
        foreach ($notices as $index => $notice) {
            $path = "notices.{$index}";
            if (!self::isObjectRepresentation($notice)) {
                $errors[$path] = 'Notice must be an object representation.';
                continue;
            }
            self::knownKeys($notice, ['id', 'type', 'kind', 'severity', 'target', 'title', 'description', 'reason', 'marked_at', 'meta'], $path, $errors);
            foreach (['id', 'title'] as $key) if (!is_string($notice[$key] ?? null)) $errors["{$path}.{$key}"] = ucfirst($key).' must be a string.';
            if (!in_array($notice['type'] ?? null, ['public', 'private'], true)) $errors["{$path}.type"] = 'Notice type is invalid.';
            if (!in_array($notice['kind'] ?? null, ['label', 'warning', 'deprecation', 'compat', 'migration', 'policy'], true)) $errors["{$path}.kind"] = 'Notice kind is invalid.';
            if (!in_array($notice['severity'] ?? null, ['info', 'warning', 'error'], true)) $errors["{$path}.severity"] = 'Notice severity is invalid.';
            self::validateNoticeTarget($notice['target'] ?? null, "{$path}.target", $errors);
            foreach (['description', 'reason', 'marked_at'] as $key) {
                if (array_key_exists($key, $notice) && !is_string($notice[$key])) $errors["{$path}.{$key}"] = ucfirst($key).' must be a string.';
            }
            self::validateOptionalJsonObject($notice, 'meta', $path, $errors);
        }
    }

    /** @param array<string, string> $errors */
    private static function validateNoticeTarget(mixed $target, string $path, array &$errors): void
    {
        if (!self::isObjectRepresentation($target)) {
            $errors[$path] = 'Notice target must be an object representation.';
            return;
        }
        if (($target['scope'] ?? null) === 'global') {
            self::exactKeys($target, ['scope'], $path, $errors);
            return;
        }
        self::exactKeys($target, ['scope', 'node_kind', 'node_id'], $path, $errors);
        if (($target['scope'] ?? null) !== 'node') $errors["{$path}.scope"] = 'Target scope is invalid.';
        if (!in_array($target['node_kind'] ?? null, ['tag', 'field', 'option'], true)) $errors["{$path}.node_kind"] = 'Node_kind is invalid.';
        if (!is_string($target['node_id'] ?? null)) $errors["{$path}.node_id"] = 'Node_id must be a string.';
    }

    /** @param array<string, string> $errors */
    private static function validateStringMap(mixed $map, string $path, array &$errors): void
    {
        if (!self::isObjectRepresentation($map)) {
            $errors[$path] = ucfirst($path).' must be an object representation.';
            return;
        }
        foreach ($map as $key => $values) self::validateStringList($values, $path.'.'.(string) $key, $errors);
    }

    /** @param array<string, string> $errors */
    private static function validateServiceMap(mixed $map, string $path, array &$errors): void
    {
        if (!self::isObjectRepresentation($map)) {
            $errors[$path] = 'Service map must be an object representation.';
            return;
        }
        foreach ($map as $key => $values) {
            $itemPath = $path.'.'.(string) $key;
            if (!is_array($values) || !array_is_list($values)) {
                $errors[$itemPath] = 'Service IDs must be an array.';
                continue;
            }
            foreach ($values as $index => $value) {
                if (!self::isServiceId($value)) $errors["{$itemPath}.{$index}"] = 'Service ID must be a string or integer.';
            }
        }
    }

    /** @param array<string, string> $errors */
    private static function validateStringList(mixed $values, string $path, array &$errors): void
    {
        if (!is_array($values) || !array_is_list($values)) {
            $errors[$path] = 'Value must be an array of strings.';
            return;
        }
        foreach ($values as $index => $value) if (!is_string($value)) $errors["{$path}.{$index}"] = 'Value must be a string.';
    }

    /**
     * @param array<string, mixed> $owner
     * @param array<string, string> $errors
     */
    private static function validateOptionalFinite(array $owner, string $key, string $path, array &$errors): void
    {
        if (array_key_exists($key, $owner) && !self::isFiniteNumber($owner[$key])) {
            $errors["{$path}.{$key}"] = ucfirst($key).' must be a finite number.';
        }
    }

    /**
     * @param array<string, mixed> $owner
     * @param array<string, string> $errors
     */
    private static function validateOptionalJsonObject(array $owner, string $key, string $path, array &$errors): void
    {
        if (!array_key_exists($key, $owner)) return;
        if (!self::isObjectRepresentation($owner[$key]) || !self::isJsonValue($owner[$key])) {
            $errors["{$path}.{$key}"] = ucfirst($key).' must be a JSON-compatible object representation.';
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

    private static function isJsonPrimitive(mixed $value): bool
    {
        return $value === null || is_string($value) || is_bool($value) || is_int($value) || (is_float($value) && is_finite($value));
    }

    private static function isJsonValue(mixed $value): bool
    {
        if (self::isJsonPrimitive($value)) return true;
        if (!is_array($value)) return false;
        foreach ($value as $item) if (!self::isJsonValue($item)) return false;
        return true;
    }

    private static function isObjectRepresentation(mixed $value): bool
    {
        return is_array($value) && ($value === [] || !array_is_list($value));
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
            $errors[$path === '' ? '$' : $path] = sprintf(
                'Wire keys differ from the v1 contract; missing [%s], unknown [%s].',
                implode(', ', $missing), implode(', ', $unknown),
            );
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
        foreach ($unknown as $key) {
            $errors[$path.'.'.$key] = "Unknown wire key '{$key}'.";
        }
    }
}
