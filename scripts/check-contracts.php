<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$spec = dirname($root) . DIRECTORY_SEPARATOR . 'dgp-spec';
$fixtures = [
    'valid/service-capability.json' => 'service-capability.json',
    'invalid/service-capability-missing-enabled.json' => 'service-capability-missing-enabled.json',
    'invalid/service-capability-null-meta.json' => 'service-capability-null-meta.json',
    'valid/handler-service.json' => 'handler-service.json',
    'invalid/handler-service-legacy-fields.json' => 'handler-service-legacy-fields.json',
    'invalid/handler-service-capability-key-mismatch.json' => 'handler-service-capability-key-mismatch.json',
    'valid/product-definition.json' => 'product-definition.json',
    'invalid/product-definition-camel-case-effect.json' => 'product-definition-camel-case-effect.json',
    'invalid/product-definition-derived-capabilities.json' => 'product-definition-derived-capabilities.json',
    'invalid/product-definition-expression-missing-body.json' => 'product-definition-expression-missing-body.json',
    'invalid/product-definition-missing-version.json' => 'product-definition-missing-version.json',
    'invalid/product-definition-component-property.json' => 'product-definition-component-property.json',
    'valid/order-snapshot.json' => 'order-snapshot.json',
    'invalid/order-snapshot-camel-case.json' => 'order-snapshot-camel-case.json',
];

// Standalone expression execution is browser-only. Diagnostic result DTOs are produced by DGP
// Validation and consumed by editorial hosts, not by this backend domain binding.
$notApplicable = [
    'valid/browser-javascript-expression.json' => 'no standalone PHP binding; nested declarations are validated in ProductDefinition and OrderSnapshot',
    'semantic/browser-javascript-expression-execution.json' => 'browser-only trusted JavaScript execution contract',
    'invalid/browser-javascript-expression-callback.json' => 'no standalone PHP binding; nested declarations reject unknown keys',
    'invalid/browser-javascript-expression-empty-body.json' => 'no standalone PHP binding; nested declarations require a body',
    'valid/product-definition-diagnostic.json' => 'DGP Validation output contract, not an SDK backend-domain binding',
    'invalid/product-definition-diagnostic-unknown-code.json' => 'DGP Validation output contract, not an SDK backend-domain binding',
    'valid/product-definition-validation-result.json' => 'DGP Validation output contract, not an SDK backend-domain binding',
    'semantic/product-definition-validation.json' => 'consumed by DGP Validation; SDK service DTOs supply catalog evidence without duplicating the publication engine',
];
$errors = [];

foreach ($fixtures as $specRelative => $localName) {
    $localPath = $root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'Contracts' . DIRECTORY_SEPARATOR . $localName;
    if (!is_file($localPath)) {
        $errors[] = "Missing SDK conformance fixture {$localName}.";
        continue;
    }

    try {
        $localValue = json_decode((string) file_get_contents($localPath), false, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        $errors[] = "Invalid SDK conformance fixture {$localName}: {$error->getMessage()}";
        continue;
    }

    $specPath = $spec . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $specRelative);
    if (!is_file($specPath)) continue;

    try {
        $specValue = json_decode((string) file_get_contents($specPath), false, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        $errors[] = "Invalid Spec fixture {$specRelative}: {$error->getMessage()}";
        continue;
    }
    if ($localValue != $specValue) {
        $errors[] = "SDK fixture {$localName} has drifted from dgp-spec/fixtures/{$specRelative}.";
    }
}

// When the sibling Spec is present, every ratified manifest entry must be explicitly mirrored or
// classified. This makes newly added fixtures fail closed instead of silently escaping SDK review.
$manifestPath = $spec . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'manifest.json';
if (is_file($manifestPath)) {
    try {
        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        foreach ($manifest as $entry) {
            $relative = is_array($entry) ? ($entry['fixture'] ?? null) : null;
            if (!is_string($relative)) {
                $errors[] = 'Spec manifest contains an entry without a fixture path.';
                continue;
            }
            if (!array_key_exists($relative, $fixtures) && !array_key_exists($relative, $notApplicable)) {
                $errors[] = "Unclassified ratified Spec fixture {$relative}.";
            }
        }
    } catch (JsonException $error) {
        $errors[] = "Invalid Spec fixture manifest: {$error->getMessage()}";
    }
}

$composer = json_decode((string) file_get_contents($root . DIRECTORY_SEPARATOR . 'composer.json'), true, flags: JSON_THROW_ON_ERROR);
$dependencies = array_keys(array_merge($composer['require'] ?? [], $composer['require-dev'] ?? []));
foreach (['elqora/dgp-core', 'elqora/dgp-validation', 'elqora/dgp-ordering', 'elqora/dgp-workspace'] as $forbidden) {
    if (in_array($forbidden, $dependencies, true)) {
        $errors[] = "SDK must not depend on consumer package {$forbidden}.";
    }
}

if ($errors !== []) {
    fwrite(STDERR, "DGP contract conformance failed:\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

fwrite(STDOUT, "Every ratified Spec fixture is mirrored or explicitly classified; SDK dependency boundaries are conformant.\n");
