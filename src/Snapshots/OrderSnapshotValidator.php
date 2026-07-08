<?php

namespace Elqora\Dgp\Snapshots;

final class OrderSnapshotValidator
{
    /**
     * Validate the standalone serialized snapshot payload.
     *
     * @param array<string, mixed> $payload
     * @return array<string, string> Key: field path, Value: error message
     */
    public static function validate(array $payload): array
    {
        $errors = [];

        // version check
        if (!isset($payload['version']) || $payload['version'] !== '1') {
            $errors['version'] = 'Version must be "1".';
        }

        // mode check
        if (!isset($payload['mode']) || !in_array($payload['mode'], ['prod', 'dev'], true)) {
            $errors['mode'] = 'Mode must be "prod" or "dev".';
        }

        // builtAt check
        if (!isset($payload['builtAt']) && !isset($payload['built_at'])) {
            $errors['builtAt'] = 'BuiltAt timestamp is required.';
        }

        // selection check
        if (!isset($payload['selection']) || !is_array($payload['selection'])) {
            $errors['selection'] = 'Selection is required and must be an array.';
        } else {
            $sel = $payload['selection'];
            if (!isset($sel['tag']) || !is_string($sel['tag'])) {
                $errors['selection.tag'] = 'Selection tag is required and must be a string.';
            }
            if (isset($sel['buttons']) && !is_array($sel['buttons'])) {
                $errors['selection.buttons'] = 'Selection buttons must be an array.';
            }
            if (isset($sel['fields']) && !is_array($sel['fields'])) {
                $errors['selection.fields'] = 'Selection fields must be an array.';
            }
        }

        // inputs check
        if (!isset($payload['inputs']) || !is_array($payload['inputs'])) {
            $errors['inputs'] = 'Inputs is required and must be an array.';
        } else {
            $inputs = $payload['inputs'];
            if (isset($inputs['form']) && !is_array($inputs['form'])) {
                $errors['inputs.form'] = 'Inputs form must be an array.';
            }
            if (isset($inputs['selections']) && !is_array($inputs['selections'])) {
                $errors['inputs.selections'] = 'Inputs selections must be an array.';
            }
        }

        // quantity check
        if (!isset($payload['quantity']) || !is_numeric($payload['quantity'])) {
            $errors['quantity'] = 'Quantity is required and must be numeric.';
        }

        // quantitySource check
        if (!isset($payload['quantitySource']) && !isset($payload['quantity_source'])) {
            $errors['quantitySource'] = 'QuantitySource is required.';
        } else {
            $qs = $payload['quantitySource'] ?? $payload['quantity_source'];
            if (!is_array($qs) || !isset($qs['kind']) || !is_string($qs['kind'])) {
                $errors['quantitySource.kind'] = 'QuantitySource kind is required and must be a string.';
            }
        }

        // min and max checks
        $min = $payload['min'] ?? null;
        $max = $payload['max'] ?? null;
        if ($min !== null && !is_numeric($min)) {
            $errors['min'] = 'Min must be numeric.';
        }
        if ($max !== null && !is_numeric($max)) {
            $errors['max'] = 'Max must be numeric.';
        }
        if (is_numeric($min) && is_numeric($max) && $min > $max) {
            $errors['min'] = 'Min cannot be greater than Max.';
        }

        // services check
        if (isset($payload['services']) && !is_array($payload['services'])) {
            $errors['services'] = 'Services must be an array.';
        }

        // serviceMap check
        if (isset($payload['serviceMap']) && !is_array($payload['serviceMap']) && isset($payload['service_map']) && !is_array($payload['service_map'])) {
            $errors['serviceMap'] = 'ServiceMap must be an array.';
        }

        return $errors;
    }
}
