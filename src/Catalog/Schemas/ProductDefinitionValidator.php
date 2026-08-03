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

        // 1. Required fields
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

            // Validate options
            if (isset($field['options']) && is_array($field['options'])) {
                foreach ($field['options'] as $optIndex => $option) {
                    $optPath = "{$path}.options.{$optIndex}";
                    if (!is_array($option)) {
                        $errors[$optPath] = 'Option must be an array.';
                        continue;
                    }

                    if (!isset($option['id']) || !is_string($option['id'])) {
                        $errors["{$optPath}.id"] = 'Option id is required and must be a string.';
                    } else {
                        $nodeIds[] = $option['id'];
                    }

                    if (!isset($option['label']) || !is_string($option['label'])) {
                        $errors["{$optPath}.label"] = 'Option label is required and must be a string.';
                    }
                }
            }
        }

        // 2. Validate option_effects_for_buttons structure & reference checks
        if (isset($data['option_effects_for_buttons']) && is_array($data['option_effects_for_buttons'])) {
            foreach ($data['option_effects_for_buttons'] as $triggerId => $targets) {
                if (!in_array($triggerId, $nodeIds)) {
                    $errors["option_effects_for_buttons.{$triggerId}"] = "Trigger ID '{$triggerId}' does not exist in filters, fields, or options.";
                }

                if (is_array($targets)) {
                    foreach ($targets as $targetFieldId => $effect) {
                        if (!in_array($targetFieldId, $nodeIds)) {
                            $errors["option_effects_for_buttons.{$triggerId}.{$targetFieldId}"] = "Target field ID '{$targetFieldId}' does not exist.";
                        }
                    }
                }
            }
        }

        // 3. Validate value_effects_for_triggers structure & reference checks
        if (isset($data['value_effects_for_triggers']) && is_array($data['value_effects_for_triggers'])) {
            foreach ($data['value_effects_for_triggers'] as $triggerId => $targets) {
                if (!in_array($triggerId, $nodeIds)) {
                    $errors["value_effects_for_triggers.{$triggerId}"] = "Trigger ID '{$triggerId}' does not exist in filters, fields, or options.";
                }

                if (is_array($targets)) {
                    foreach ($targets as $targetFieldId => $effect) {
                        if (!in_array($targetFieldId, $nodeIds)) {
                            $errors["value_effects_for_triggers.{$triggerId}.{$targetFieldId}"] = "Target field ID '{$targetFieldId}' does not exist.";
                        }
                    }
                }
            }
        }

        return $errors;
    }
}
