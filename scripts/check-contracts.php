<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$spec = dirname($root) . DIRECTORY_SEPARATOR . 'dgp-spec';
$fixtures = [
    'valid/service-capability.json' => 'service-capability.json',
    'valid/handler-service.json' => 'handler-service.json',
    'valid/product-definition.json' => 'product-definition.json',
    'invalid/service-capability-null-meta.json' => 'service-capability-null-meta.json',
    'invalid/handler-service-legacy-fields.json' => 'handler-service-legacy-fields.json',
    'invalid/handler-service-capability-key-mismatch.json' => 'handler-service-capability-key-mismatch.json',
    'invalid/product-definition-derived-capabilities.json' => 'product-definition-derived-capabilities.json',
    'invalid/product-definition-component-property.json' => 'product-definition-component-property.json',
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
    if (!is_file($specPath)) {
        continue;
    }

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

fwrite(STDOUT, "DGP Spec fixtures and SDK dependency boundaries are conformant.\n");
