# DGP SDK

DGP (Digital Product) SDK is a framework-neutral protocol and core toolkit for digital product implementations. It enables developers to implement structured, sandboxed plugins (handlers) that interact with host platforms through stable, strongly-typed contracts.

DGP is a framework-neutral core with minimal external dependencies, integrating `elqora/config-kit` for configuration management.

---

## 📦 Installation

Add the package to your `composer.json` file:

```bash
composer require elqora/dgp-sdk
```

---

## 🚀 Architectural Overview

Handlers (drivers) implement the root `DgpDriverContract` which aggregates semantic sub-contracts.

```
DgpDriverContract
├── ManifestContract              # Driver description and capabilities
├── ConfigSchemaContract          # Config schema, validation, & log redaction
├── HealthContract                # Operational health checks
├── BalanceContract               # Provider account balance monitoring
├── ServicesContract             # Service definition and price queries
├── RuntimeContract               # Order lifecycle execution (initialize, start, etc.)
├── OrderDeliveriesContract       # Progress and delivery tracking
└── OrderManagementContract       # Post-order actions and management views
```

---

## 🛠️ Configuration & Schemas

DGP utilizes `elqora/config-kit` for structured configuration. Implementing handlers declare fields, validate inputs, project safe public properties, and redact logs.

### Example Configuration Schema in SMM Handler:

```php
use Elqora\ConfigKit\Schema\ConfigSchema;
use Elqora\ConfigKit\Schema\UiConfigSchema;
use Elqora\ConfigKit\Schema\ConfigField;
use Elqora\ConfigKit\Support\ConfigBag;
use Elqora\ConfigKit\Support\ConfigValidationResult;
use Elqora\Dgp\Contracts\DgpDriverContract;

class SmmTestHandler implements DgpDriverContract
{
    public function configSchema(): ?ConfigSchema
    {
        return new ConfigSchema([
            new ConfigField(name: 'base_url', label: 'Base URL', required: true),
            new ConfigField(name: 'api_key', label: 'API Key', required: true, secret: true),
            new ConfigField(name: 'timeout', label: 'Timeout', type: 'number', required: false, default: 30),
            new ConfigField(name: 'sandbox_user', label: 'Sandbox User', required: false, sandbox: true),
        ]);
    }

    public function uiConfigSchema(): ?UiConfigSchema
    {
        return $this->configSchema()?->toUiConfigSchema();
    }

    public function validateConfig(?ConfigBag $config = null): ConfigValidationResult
    {
        if ($config === null) {
            $res = new ConfigValidationResult(false);
            $res->addError('base_url', 'Base URL is required.');
            $res->addError('api_key', 'API Key is required.');
            return $res;
        }

        $res = new ConfigValidationResult(true);
        if (!$config->filledOption('base_url')) {
            $res->addError('base_url', 'Base URL is required.');
        }
        if (!$config->secret('api_key')) {
            $res->addError('api_key', 'API Key is required.');
        }
        if ($config->isSandbox() && !$config->filledOption('sandbox_user')) {
            $res->addError('sandbox_user', 'Sandbox User is required in sandbox mode.');
        }
        return $res;
    }

    public function publicConfig(?ConfigBag $config = null): array
    {
        // Excludes secrets, exposing options safely to dashboard pages
        return $config?->toPublicArray() ?? [];
    }

    public function redactForLogs(mixed $payload): mixed
    {
        if (!is_array($payload)) {
            return $payload;
        }

        $redacted = $payload;
        foreach (['api_key', 'token', 'password', 'secret'] as $key) {
            if (array_key_exists($key, $redacted)) {
                $redacted[$key] = '[REDACTED]';
            }
        }

        // Handle nested arrays
        foreach ($redacted as $k => $v) {
            if (is_array($v)) {
                $redacted[$k] = $this->redactForLogs($v);
            }
        }
        return $redacted;
    }
    
    // ... rest of DgpDriverContract methods
}
```

---

## 🛒 Order Snapshots & Fallback Resolution

An `OrderSnapshot` describes what was selected in the frontend checkout. The handler uses this snapshot to execute provisioning.

### Fallback Service Lookup:

```php
use Elqora\Dgp\Snapshots\OrderSnapshot;

// A snapshot payload maps fallback relations
$snapshot = OrderSnapshot::fromArray([
    'version' => '1',
    'mode' => 'prod',
    'builtAt' => '2026-07-07T10:30:00.000Z',
    'selection' => ['tag' => 'tag:instagram-likes', 'buttons' => [], 'fields' => []],
    'inputs' => ['form' => [], 'selections' => []],
    'quantity' => 1000,
    'quantitySource' => ['kind' => 'fixed'],
    'min' => 100,
    'max' => 10000,
    'services' => [101, 103],
    'serviceMap' => [],
    'utilities' => [],
    'fallbacks' => [
        'global' => [
            103 => [106, 108],
        ],
        'nodes' => [
            103 => [
                'option:ultra' => [104, 106],
            ]
        ]
    ]
]);

// Resolve fallback replacement IDs with node precedence:
$fallbacks = $snapshot->fallbacksFor(103, 'option:ultra');
// Returns: [104, 106, 108] (Node-specific 104 & 106 first, then global 108, deduplicated)
```

---

## ⏳ Order Lifecycle & Persistence

Order fulfillment transitions from **Initialization** to **Fulfillment** through the repository.

```
  Host (InitializeRequest)
         │
         ▼
  Handler::initialize() ──► Returns Plan (id: null)
         │
         ▼
  Repository::savePlan(orderId, plan) ──► Persists Plan with Host ID
         │
         ▼
  Handler::start(StartRequest) ──► Returns StartResult (id: null)
         │
         ▼
  Repository::saveStartResult(planId, startResult) ──► Persists StartResult with Host ID
```

### 1. Initialize Order
```php
use Elqora\Dgp\Configuration\Dgp;
use Elqora\Dgp\Runtime\InitializeRequest;
use Elqora\Dgp\Runtime\RuntimeContext;
use Elqora\Dgp\Runtime\References\HandlerReference;

// Resolve the repository scoped to this handler:
$repository = Dgp::runtimeRepository(HandlerReference::fromKey('smm-test'));

$request = new InitializeRequest(
    orderId: 12345, // Host-managed order ID
    snapshot: $orderSnapshot,
    runtimeContext: new RuntimeContext(
        context: ['requestedBy' => 'customer_admin'],
        meta: ['traceId' => 'tr-9988']
    )
);

$result = $handler->initialize($request);
if ($result->isSuccess()) {
    $plan = $result->value(); // Plan DTO
    
    // Save Plan through Scoped Host Repository
    $persistedPlan = $repository->savePlan(12345, $plan)->value();
}
```

### 2. Start Fulfillment
```php
use Elqora\Dgp\Runtime\StartRequest;
use Elqora\Dgp\Runtime\RuntimeContext;

// Start Request references the persisted Plan ID
$startRequest = new StartRequest(
    planId: $persistedPlan->id,
    runtimeContext: new RuntimeContext(context: ['ip' => '127.0.0.1'])
);

$result = $handler->start($startRequest);
if ($result->isSuccess()) {
    $startResult = $result->value(); // StartResult DTO
    
    // Save StartResult through the repository under the parent plan ID
    $persistedStart = $repository->saveStartResult($persistedPlan->id, $startResult)->value();
}
```

---

## 🚥 Next Actions

Handlers guide the host platform or user next steps through strongly-typed next actions.

```php
use Elqora\Dgp\Actions\RedirectAction;
use Elqora\Dgp\Runtime\Plan;

// Define a next action redirecting the customer to complete payment
$nextAction = new RedirectAction(
    url: 'https://gateway.example/pay/invoice-88',
    external: true,
    label: 'Complete Invoice Payment'
);

// Attach it to a Plan or StartResult DTO
$plan = new Plan(
    id: null,
    key: 'plan-payment',
    state: ['pending_payment' => true],
    deliveries: [],
    nextAction: $nextAction
);
```

Available next action types include:
- `RedirectAction` (`redirect`): External URL or page redirects.
- `ButtonAction` (`button`): UI button triggers.
- `FieldsAction` (`fields`): Dynamic form input submission requests.
- `PopupAction` (`popup`): Modal overlays with custom UI entry points.
- `PopoverAction` (`popover`): Embedded target popover view triggers.
- `InlineAction` (`inline`): Embedded in-page block components.
- `InstructionsAction` (`instructions`): Static or dynamic user-facing guide/text blocks.
- `QrCodeAction` (`qr_code`): QR code payment or scanner prompts.
- `TextAction` (`text`): Custom descriptive or notification text block.
- `CustomAction` (`custom`): Generic action fallback for developer-defined extensions.

---

## 🧪 Compliance Checking & Testing

Verify that custom drivers conform to the specifications by running the automated testing suite:

```bash
# Run unit & compliance tests
vendor/bin/phpunit

# Perform static analysis
vendor/bin/phpstan analyse
```
