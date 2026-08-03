<?php

namespace Elqora\Dgp\Catalog\Schemas;

final class ProductDefinitionValidator
{
    /**
     * Validate a serialized ProductDefinition array.
     *
     * @param array<string, mixed> $data
     * @return array<string, string> Key: path (e.g. 'fields.0.id'), Value: error message
     */
    public static function validate(array $data): array
    {
        $errors = [];

        $required = [
            'id',
            'name',
            'filters',
            'fields',
            'order_for_tags',
            'includes_for_buttons',
            'excludes_for_buttons',
            'option_effects_for_buttons',
            'value_effects_for_triggers',
            'schema_version',
            'fallbacks',
            'description',
            'notices',
            'meta',
        ];
        foreach ($required as $key) {
            if (!array_key_exists($key, $data)) {
                $errors[$key] = "{$key} is required by ProductDefinition schema version 1.";
            }
        }

        foreach (array_keys($data) as $key) {
            if (!in_array($key, $required, true)) {
                $errors[$key] = "Unknown ProductDefinition property '{$key}'.";
            }
        }

        if (!isset($data['id']) || (!is_string($data['id']) && !is_int($data['id']))) {
            $errors['id'] = 'Id is required and must be a string or integer.';
        }

        if (!isset($data['name']) || !is_string($data['name'])) {
            $errors['name'] = 'Name is required and must be a string.';
        }

        if (!isset($data['filters']) || !is_array($data['filters'])) {
            $errors['filters'] = 'Filters is a required array.';
        }

        if (!isset($data['fields']) || !is_array($data['fields'])) {
            $errors['fields'] = 'Fields is a required array.';
        }

        if (($data['schema_version'] ?? null) !== ProductDefinition::SCHEMA_VERSION) {
            $errors['schema_version'] = 'schema_version must be the string "1".';
        }

        foreach (['order_for_tags', 'includes_for_buttons', 'excludes_for_buttons', 'option_effects_for_buttons', 'value_effects_for_triggers', 'notices', 'meta'] as $arrayKey) {
            if (array_key_exists($arrayKey, $data) && !is_array($data[$arrayKey])) {
                $errors[$arrayKey] = "{$arrayKey} must be an array representation of its canonical JSON collection.";
            }
        }

        if (array_key_exists('fallbacks', $data) && $data['fallbacks'] !== null && !is_array($data['fallbacks'])) {
            $errors['fallbacks'] = 'fallbacks must be an array representation of an object or null.';
        }

        if (array_key_exists('description', $data) && $data['description'] !== null && !is_string($data['description'])) {
            $errors['description'] = 'description must be a string or null.';
        }

        if (!empty($errors)) {
            return $errors;
        }

        // Keep track of all known node IDs for reference checking
        $nodeIds = [];

        // Validate filters (tags)
        foreach ($data['filters'] as $index => $filter) {
            $path = "filters.{$index}";
            if (!is_array($filter)) {
                $errors[$path] = 'Filter must be an array.';
                continue;
            }

            if (!isset($filter['id']) || !is_string($filter['id'])) {
                $errors["{$path}.id"] = 'Filter id is required and must be a string.';
            } else {
                $nodeIds[] = $filter['id'];
            }

            if (!isset($filter['label']) || !is_string($filter['label'])) {
                $errors["{$path}.label"] = 'Filter label is required and must be a string.';
            }

            foreach (['flags', 'estimates', 'constraints', 'constraints_origin', 'constraints_overrides'] as $forbiddenFilter) {
                if (array_key_exists($forbiddenFilter, $filter)) {
                    $errors["{$path}.{$forbiddenFilter}"] = "Derived or legacy filter property '{$forbiddenFilter}' is not part of DGP v1.";
                }
            }
        }

        // Validate fields
        foreach ($data['fields'] as $index => $field) {
            $path = "fields.{$index}";
            if (!is_array($field)) {
                $errors[$path] = 'Field must be an array.';
                continue;
            }

            if (!isset($field['id']) || !is_string($field['id'])) {
                $errors["{$path}.id"] = 'Field id is required and must be a string.';
            } else {
                $nodeIds[] = $field['id'];
            }

            if (!isset($field['type']) || !is_string($field['type'])) {
                $errors["{$path}.type"] = 'Field type is required and must be a string.';
            }

            if (!isset($field['label']) || !is_string($field['label'])) {
                $errors["{$path}.label"] = 'Field label is required and must be a string.';
            }

            foreach (['component', 'flags', 'estimates', 'constraints'] as $forbiddenField) {
                if (array_key_exists($forbiddenField, $field)) {
                    $errors["{$path}.{$forbiddenField}"] = "Legacy field property '{$forbiddenField}' is not part of DGP v1.";
                }
            }

            if (isset($field['options']) && is_array($field['options'])) {
                self::validateOptions($field['options'], "{$path}.options", $nodeIds, $errors);
            }
        }

        // 2. Validate option_effects_for_buttons structure & reference checks
        if (isset($data['option_effects_for_buttons']) && is_array($data['option_effects_for_buttons'])) {
            foreach ($data['option_effects_for_buttons'] as $triggerId => $targets) {
                if (!in_array($triggerId, $nodeIds, true)) {
                    $errors["option_effects_for_buttons.{$triggerId}"] = "Trigger ID '{$triggerId}' does not exist in filters, fields, or options.";
                }

                if (is_array($targets)) {
                    foreach ($targets as $targetFieldId => $effect) {
                        if (!in_array($targetFieldId, $nodeIds, true)) {
                            $errors["option_effects_for_buttons.{$triggerId}.{$targetFieldId}"] = "Target field ID '{$targetFieldId}' does not exist.";
                        }
                        if (is_array($effect) && array_key_exists('forceVisible', $effect)) {
                            $errors["option_effects_for_buttons.{$triggerId}.{$targetFieldId}.forceVisible"] = 'Use canonical force_visible.';
                        }
                    }
                }
            }
        }

        // 3. Validate value_effects_for_triggers structure & reference checks
        if (isset($data['value_effects_for_triggers']) && is_array($data['value_effects_for_triggers'])) {
            foreach ($data['value_effects_for_triggers'] as $triggerId => $targets) {
                if (!in_array($triggerId, $nodeIds, true)) {
                    $errors["value_effects_for_triggers.{$triggerId}"] = "Trigger ID '{$triggerId}' does not exist in filters, fields, or options.";
                }

                if (is_array($targets)) {
                    foreach ($targets as $targetFieldId => $effect) {
                        if (!in_array($targetFieldId, $nodeIds, true)) {
                            $errors["value_effects_for_triggers.{$triggerId}.{$targetFieldId}"] = "Target field ID '{$targetFieldId}' does not exist.";
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * @param array<int, mixed> $options
     * @param list<string> $nodeIds
     * @param array<string, string> $errors
     */
    private static function validateOptions(array $options, string $basePath, array &$nodeIds, array &$errors): void
    {
        foreach ($options as $index => $option) {
            $path = "{$basePath}.{$index}";
            if (!is_array($option)) {
                $errors[$path] = 'Option must be an array.';
                continue;
            }
            if (!isset($option['id']) || !is_string($option['id'])) {
                $errors["{$path}.id"] = 'Option id is required and must be a string.';
            } else {
                $nodeIds[] = $option['id'];
            }
            if (!isset($option['label']) || !is_string($option['label'])) {
                $errors["{$path}.label"] = 'Option label is required and must be a string.';
            }
            if (isset($option['children']) && is_array($option['children'])) {
                self::validateOptions($option['children'], "{$path}.children", $nodeIds, $errors);
            }
        }
    }
}
