# DGP SDK

DGP SDK is a framework-neutral protocol and core toolkit for digital-service handlers. A handler exposes services that a host can initialize, charge, start, fulfill, update, act on in bulk, and manage through stable contracts.

The package is intentionally framework-neutral. It defines protocol shapes, DTOs, contracts, enums, validators, hydrators, and deterministic helper APIs. It does not prescribe a database, ORM, queue, transaction model, routing layer, or rendering layer.

## Installation

```bash
composer require elqora/dgp-sdk
```

## Optional shadcn/ui Registry

This repository also contains a custom [shadcn/ui registry](https://ui.shadcn.com/docs/registry) with optional React and Tailwind components for presenting DGP runtime state. It includes client-facing plan and delivery views, admin/operator views, status badges, action controls, segmented progress, and shared TypeScript DTO types.

The registry is a host-rendering convenience layer, not part of the Composer package or the framework-neutral PHP runtime. Hosts remain responsible for choosing, installing, adapting, and rendering these components.

- `registry.json` declares the `dgp-sdk` registry item and its dependencies.
- `registry/dgp-sdk` contains the registry source files.
- `playground` provides the local interactive preview.

The `dgp-sdk` registry item currently installs:

- **Shared components:** `StatusBadge`, `ActionButtonGroup`, and `SegmentBar`.
- **Client components:** `ClientDeliveryCard`, `ClientPlanCard`, and `ClientPlanViewer`.
- **Admin components:** `AdminDeliveryCard`, `AdminPlanViewer`, and `AdminManagementViewer`.
- **Types:** shared TypeScript DTOs for manifests, services, runtime plans, deliveries, progress, actions, charges, management, insights, audits, assets, balance, and health.

The source tree also contains `ChargeIndicator`, `OrderChargeStateViewer`, `ClientChargeCard`, and `AdminChargeCard`. These charge components are exported by the source barrel but are not currently declared as files in `registry.json`, so they are not yet part of the installable registry item.

To run the registry playground locally:

```bash
npm install
npm run dev
```

Validate or build the frontend assets with:

```bash
npm run typecheck
npm run build
```

The repository does not currently publish a stable hosted registry endpoint. Consumers should use the registry source directly until an installation URL is documented.

## Responsibility Split

The SDK defines:

- Contracts
- DTOs
- Enums
- Endpoint path helpers
- Validators
- Hydrators
- Protocol shapes

The host implements:

- Persistence
- Routing
- Storage
- Payment records
- Broadcasting
- Rendering

The handler implements:

- Service catalog
- Balance reporting
- Initialization
- Start
- Synchronization
- Cancellation
- Actions
- Charges
- Management

## Ecosystem

- [DGP Spec](https://github.com/elqora/dgp-spec) owns canonical wire contracts.
- [DGP Core](https://github.com/elqora/dgp-core) interprets product definitions.
- [DGP Validation](https://github.com/elqora/dgp-validation) validates definitions for ingestion and publication.
- [DGP Ordering](https://github.com/elqora/dgp-ordering) constructs customer order snapshots.
- [DGP Ordering Form Palette](https://github.com/elqora/dgp-ordering-form-palette) provides an optional frontend Form Palette integration.
- [DGP Workspace](https://github.com/elqora/dgp-workspace) supplies headless editorial session infrastructure.
- [DGP Studio](https://github.com/elqora/dgp-studio) provides visual authoring, testing, and publication UX.

## Driver Contract

Handlers implement `DgpDriverContract`, which aggregates the mandatory protocol contracts:

```text
DgpDriverContract
  ManifestContract
  ConfigSchemaContract
  HealthContract
  BalanceContract
  ServicesContract
  RuntimeContract
  OrderDeliveriesContract
  OrderManagementContract
  GenericActionContract
  BulkActionContract
```

Optional capabilities such as service schema catalogs, webhooks, UI contributions, private assets, and insights are modeled as separate contracts or capability declarations.

## Host-Provided Ports

The SDK may define repositories and stores as host-provided ports. The SDK defines the interface; the host provides the implementation.

Examples:

- `RuntimeRepositoryContract` resolves a handler-scoped runtime port.
- `HandlerRuntimeRepositoryContract` reads plans, start results, deliveries, and runtime views.
- `ServicesRepositoryContract` resolves handler-scoped service catalog/state access.
- `DeliveriesRepositoryContract` exposes persisted delivery lookups.
- `DeliveryProgressRepositoryContract` records and reads historical delivery progress observations.
- `AuditRepositoryContract` records and reads meaningful operational and domain evidence.
- `InsightsRepositoryContract` exposes insight snapshot updates.

Handlers return normalized runtime state. The host persists plans, start results, and deliveries automatically, then exposes persisted state through host-provided ports. The SDK still owns no storage backend. A host can implement ports with SQL, files, queues, remote APIs, memory, or any other storage model.

## Configuration

DGP uses `elqora/config-kit` for structured configuration. Handlers can declare config fields, validate inputs, expose safe public config, and redact sensitive values.

```php
use Elqora\ConfigKit\Schema\ConfigField;
use Elqora\ConfigKit\Schema\ConfigSchema;
use Elqora\ConfigKit\Support\ConfigBag;
use Elqora\ConfigKit\Support\ConfigValidationResult;

public function configSchema(): ?ConfigSchema
{
    return new ConfigSchema([
        new ConfigField(name: 'base_url', label: 'Base URL', required: true),
        new ConfigField(name: 'api_key', label: 'API Key', required: true, secret: true),
    ]);
}

public function validateConfig(?ConfigBag $config = null): ConfigValidationResult
{
    if ($config === null || !$config->filledOption('base_url') || !$config->secret('api_key')) {
        $result = new ConfigValidationResult(false);
        $result->addError('base_url', 'Base URL and API key are required.');

        return $result;
    }

    return ConfigValidationResult::ok();
}
```

## Order Snapshots

`OrderSnapshot` is the semantic description of what the customer ordered. Host identity such as `orderId` and execution context such as `RuntimeContext` are useful runtime metadata, but the snapshot remains the resolved order description.
Hydration validates the complete nested DGP v1 wire shape, JSON compatibility,
finite numeric values, quantity-source discriminators, and utility evidence
before constructing the DTO. String and integer service identities are
preserved losslessly, including numeric-looking string IDs.

```php
use Elqora\Dgp\Snapshots\OrderSnapshot;

$snapshot = OrderSnapshot::fromArray([
    'version' => '1',
    'mode' => 'prod',
    'built_at' => '2026-07-07T10:30:00.000Z',
    'product_id' => 'instagram-likes',
    'definition_schema_version' => '1',
    'selection' => ['filter_id' => 'instagram', 'trigger_ids' => [], 'fields' => []],
    'inputs' => ['form' => ['quantity' => 1000], 'selections' => []],
    'quantity' => 1000,
    'quantity_source' => [
        'kind' => 'host_default',
        'node_id' => null,
        'rule' => null,
        'defaulted_from_host' => true,
    ],
    'min' => 100,
    'max' => 10000,
    'service_ids' => [101, 103],
    'service_ids_by_node' => ['option:quality-ultra' => [103]],
    'fallbacks' => null,
    'utilities' => [],
    'meta' => [],
]);

$quantity = $snapshot->quantity();
$services = $snapshot->servicesForNode('option:quality-ultra');
```

## Runtime Lifecycle

Initialization receives the host order identity, the resolved snapshot, and optional runtime context. The returned `Plan` describes the execution structure, deliveries, next actions, and is initialized with a `PlanStatus` (e.g. `draft`, `active`, `completed`, `failed`, `cancelled`, `abandoned`).

```php
use Elqora\Dgp\Runtime\InitializeRequest;
use Elqora\Dgp\Runtime\RuntimeContext;
use Elqora\Dgp\Runtime\PlanStatus;

$initialize = new InitializeRequest(
    orderId: 12345,
    snapshot: $snapshot,
    runtimeContext: new RuntimeContext(
        context: ['requestedBy' => 'customer_admin'],
        meta: ['traceId' => 'tr-9988'],
    ),
);

$plan = $handler->initialize($initialize)->value();
echo $plan->status->value; // 'active'
```

After initialization, the host persists the plan and its initialization deliveries. Preparation receives that persisted, hydrated `Plan` object, including persisted delivery IDs, and can update those same initialization deliveries before fulfillment starts. The passed plan is an input snapshot; handlers may still use host repositories for freshness checks, locks, claims, or retry protection where the host implementation supports them.

```php
use Elqora\Dgp\Runtime\PrepareRequest;
use Elqora\Dgp\Runtime\PreparationStatus;

$persistedPlan = $runtimeStore->savePlan($plan);

$prepare = new PrepareRequest(
    orderId: $persistedPlan->orderId,
    plan: $persistedPlan,
    context: new RuntimeContext(context: ['ip' => '127.0.0.1']),
);

$preparation = $handler->prepare($prepare)->value();
echo $preparation->status->value; // 'running'
```

Starting fulfillment carries the host order identity plus a reference to the persisted plan. The returned `StartResult` specifies the initial progress and carrying a `StartResultStatus` (e.g. `pending`, `running`, `completed`, `failed`, `cancelled`, `abandoned`).

```php
use Elqora\Dgp\Runtime\References\PlanReference;
use Elqora\Dgp\Runtime\RuntimeContext;
use Elqora\Dgp\Runtime\StartRequest;
use Elqora\Dgp\Runtime\StartResultStatus;

$start = new StartRequest(
    orderId: 12345,
    plan: new PlanReference(id: $persistedPlan->id),
    context: new RuntimeContext(context: ['ip' => '127.0.0.1']),
);

$startResult = $handler->start($start)->value();
echo $startResult->status->value; // 'running'
```

## Runtime State Through Host Ports

Hosts can register handler-scoped ports through `Dgp`. The repository allows looking up plans, start results, and delivery histories, and updating plan/start result statuses.

```php
use Elqora\Dgp\Configuration\Dgp;
use Elqora\Dgp\Runtime\References\HandlerReference;
use Elqora\Dgp\Runtime\References\PlanReference;
use Elqora\Dgp\Runtime\PlanStatus;

Dgp::registerRuntimeRepository($runtimeRepository);

$runtime = Dgp::runtimeRepository(HandlerReference::fromKey('smm-test'));
$plan = $runtime->findPlan(12345, new PlanReference(key: 'plan-payment'))->value();
$view = $runtime->runtime(12345)->value();

// Update status directly via the repository
$runtime->updatePlanStatus($plan->id, PlanStatus::CANCELLED);
```

The SDK defines these interfaces only. Host implementations decide how returned state is stored, indexed, locked, versioned, authorized, and rendered.

## Host Endpoints

`Dgp::endpointPrefix()` sets the host base prefix. `Dgp::endpoint()` resolves typed built-in endpoint paths, while `Dgp::path()` remains available for custom extension paths.

```php
use Elqora\Dgp\Configuration\Dgp;
use Elqora\Dgp\Endpoints\HostEndpointType;

Dgp::endpointPrefix('/dgp');

$deliveryAction = Dgp::endpoint('smm-test', HostEndpointType::DELIVERY_ACTION);
$genericAction = Dgp::endpoint('smm-test', HostEndpointType::GENERIC_ACTION);
$chargePayment = Dgp::endpoint('smm-test', HostEndpointType::CHARGE_PAYMENT);
$privateAsset = Dgp::endpoint('smm-test', HostEndpointType::PRIVATE_ASSET, 'invoice.pdf');

echo $deliveryAction->path; // /dgp/smm-test/delivery/action
echo $genericAction->path;  // /dgp/smm-test/generic/action
echo $chargePayment->path;  // /dgp/smm-test/charge/payment
echo $privateAsset->path;   // /dgp/smm-test/assets/invoice.pdf

$custom = Dgp::path('smm-test', 'custom/action');
```

## Interactions

Handlers guide user or host next steps through `elqora/interactions` DTOs. Runtime DTOs keep the `nextAction` property and serialize it as `next_action`, but the payload is now an interaction.

```php
use Elqora\Dgp\Actions\ActionButton;
use Elqora\Dgp\Runtime\Plan;
use Elqora\Interactions\Interactions\Redirect;

$plan = new Plan(
    id: null,
    key: 'plan-payment',
    state: ['pending_payment' => true],
    buttons: [
        new ActionButton(
            value: 'approve',
            label: 'Approve',
        ),
        new ActionButton(
            value: 'action',
            label: 'Complete Invoice Payment',
            nextAction: new Redirect(
                url: 'https://gateway.example/pay/invoice-88',
                metadata: ['label' => 'Complete Invoice Payment'],
            ),
        ),
    ],
    nextAction: new Redirect(
        url: 'https://gateway.example/pay/invoice-88',
        metadata: ['label' => 'Complete Invoice Payment'],
    ),
);
```

Supported interaction types come from `elqora/interactions`:

- `Redirect`
- `Instructions`
- `QrCode`
- `Script`
- `Mount`
- `Component`

Buttons are independent controls exposed on runtime DTOs separately from `nextAction`.

```php
use Elqora\Dgp\Actions\ActionButton;
use Elqora\Dgp\Actions\ActionButtonKind;
use Elqora\Dgp\Actions\ActionButtonStyle;

$buttons = [
    new ActionButton(
        value: 'cancel',
        label: 'Cancel',
        style: ActionButtonStyle::DANGER,
    ),
    new ActionButton(
        value: 'refresh',
        kind: ActionButtonKind::ICON,
        icon: 'refresh-cw',
        tooltip: 'Refresh',
    ),
];
```

Use the reserved value `action` when a button should execute its attached interaction instead of submitting a handler-defined button value.

```php
use Elqora\Dgp\Actions\ActionButton;
use Elqora\Interactions\Interactions\Redirect;

$button = new ActionButton(
    value: 'action',
    label: 'Open payment',
    nextAction: new Redirect(
        url: 'https://gateway.example/pay/invoice-88',
    ),
);
```

Inline client behavior is represented as an interaction script.

```php
use Elqora\Interactions\DTOs\Execution\HandlerExecution;
use Elqora\Interactions\DTOs\ScriptDefinition;
use Elqora\Interactions\Enums\Presentation;
use Elqora\Interactions\Interactions\Script;

$inline = new Script(
    scripts: [new ScriptDefinition(src: 'https://cdn.example.test/checkout.js', key: 'checkout')],
    execute: new HandlerExecution('checkout-widget', 'mount'),
    presentation: Presentation::Inline,
);
```

Runtime DTOs that expose `nextAction` also expose top-level buttons.

```php
use Elqora\Dgp\Runtime\StartResult;

$start = new StartResult(
    id: null,
    key: 'start-payment',
    state: [],
    buttons: [
        new ActionButton(
            value: 'retry',
            label: 'Retry',
        ),
    ],
);
```

## Generic And Bulk Actions

Generic actions represent arbitrary host-defined or handler-defined action values over arbitrary targets. They can be used for delivery actions, charge actions, plan actions, order actions, management actions, custom host actions, and bulk-like custom actions.

```php
use Elqora\Dgp\Actions\ActionTarget;
use Elqora\Dgp\Actions\ActionTargetType;
use Elqora\Dgp\Actions\GenericActionRequest;

$generic = new GenericActionRequest(
    handlerKey: 'smm-test',
    actionValue: 'retry_selected',
    targets: [
        new ActionTarget(ActionTargetType::ORDER, 12345),
        new ActionTarget(ActionTargetType::CHARGE, 789, key: 'deposit'),
        new ActionTarget('provider.custom_target', 'target-1'),
    ],
);

$handler->handleGenericAction($generic);
```

Explicit bulk methods model standard bulk operations separately.

```php
use Elqora\Dgp\Actions\ActionTarget;
use Elqora\Dgp\Actions\ActionTargetType;
use Elqora\Dgp\Bulk\CancelBulkRequest;
use Elqora\Dgp\Bulk\RefreshBulkRequest;
use Elqora\Dgp\Bulk\RetryBulkRequest;
use Elqora\Dgp\Bulk\StartBulkRequest;

$targets = [
    new ActionTarget(ActionTargetType::ORDER, 12345),
    new ActionTarget(ActionTargetType::PLAN, 456),
];

$handler->startBulk(new StartBulkRequest('smm-test', $targets));
$handler->cancelBulk(new CancelBulkRequest('smm-test', $targets));
$handler->retryBulk(new RetryBulkRequest('smm-test', $targets));
$handler->refreshBulk(new RefreshBulkRequest('smm-test', $targets));
```

## Deliveries

Deliveries expose rendering fields directly instead of hiding them in `meta`: `kind`, `name`, `isPublic`, and `note`. Progress is represented by `DeliveryProgress`; scalar progress values are accepted for convenience and hydrated into a progress DTO.

```php
use Elqora\Dgp\Deliveries\DeliveryProgress;
use Elqora\Dgp\Deliveries\DeliveryProgressSegment;
use Elqora\Dgp\Deliveries\DeliveryStatus;
use Elqora\Dgp\Deliveries\InitializationDelivery;
use Elqora\Dgp\Actions\ActionButton;
use Elqora\Dgp\Actions\ActionButtonStyle;

$delivery = new InitializationDelivery(
    id: null,
    key: 'admin-review',
    status: DeliveryStatus::PROCESSING,
    label: 'Review',
    progress: new DeliveryProgress(current: 25, target: 100, percent: 25, unit: 'items'),
    kind: 'admin_review',
    name: 'Admin Review',
    isPublic: false,
    note: 'Internal preparation',
);
```

Segmented progress can expose a one-level breakdown while keeping the parent `DeliveryProgress` authoritative as the aggregate.

```php
$progress = new DeliveryProgress(
    current: 50,
    target: 100,
    percent: 50,
    unit: 'items',
    segments: [
        new DeliveryProgressSegment(
            key: 'provider-import',
            progress: new DeliveryProgress(current: 20, target: 40, percent: 50, unit: 'items'),
            label: 'Provider import',
            status: 'processing',
            sequence: 1,
            buttons: [
                new ActionButton(
                    value: 'stop_segment',
                    label: 'Stop',
                    style: ActionButtonStyle::DANGER,
                    meta: ['segment_key' => 'provider-import'],
                ),
            ],
        ),
    ],
);
```

Segment buttons are user-selectable commands for addressable progress segments. They do not replace delivery-level interactions; submit them as generic actions with the parent delivery target and the segment target.

```php
use Elqora\Dgp\Actions\ActionTarget;
use Elqora\Dgp\Actions\ActionTargetType;
use Elqora\Dgp\Actions\GenericActionRequest;

$request = new GenericActionRequest(
    handlerKey: 'smm-test',
    actionValue: 'stop_segment',
    targets: [
        new ActionTarget(ActionTargetType::FULFILLMENT_DELIVERY, $deliveryId, key: $deliveryKey),
        new ActionTarget(ActionTargetType::SEGMENT, 'provider-import', key: 'provider-import'),
    ],
);
```

`Delivery.progress` is the current progress state. `DeliveryProgressRepository` stores and exposes historical progress observations. Progress records may be written asynchronously through `record()` by handlers, workers, synchronizers, webhooks, actions, host processes, or manual operations. The host implements the repository and owns storage.

```php
use Elqora\Dgp\Configuration\Dgp;
use Elqora\Dgp\Deliveries\DeliveryProgress;
use Elqora\Dgp\Deliveries\DeliveryStage;
use Elqora\Dgp\Progress\DeliveryProgressRecord;
use Elqora\Dgp\Progress\ProgressSource;
use Elqora\Dgp\Runtime\References\DeliveryReference;
use Elqora\Dgp\Runtime\References\HandlerReference;

Dgp::registerDeliveryProgressRepository($progressRepository);

$progress = Dgp::deliveryProgressRepository(HandlerReference::fromKey('smm-test'));
$progress->record(new DeliveryProgressRecord(
    id: null,
    orderId: 12345,
    delivery: new DeliveryReference(key: 'admin-review'),
    stage: DeliveryStage::INITIALIZATION,
    progress: new DeliveryProgress(current: 50, target: 100, percent: 50, unit: 'items'),
    recordedAt: '2026-07-10T10:15:00Z',
    source: ProgressSource::SYNCHRONIZATION,
));
```

## Audits

The audit repository is a host-provided, handler-scoped persistence port. Handlers may record meaningful operational or domain occurrences that the host may need to inspect later, such as provider rejection, exhausted fallback services, invalid webhook signatures, rejected refill requests, unsupported provider statuses, insufficient provider balance, synchronization inconsistencies, or manual administrator intervention.

Audits preserve evidence for later review. They are separate from debug logs, events, progress timelines, and insights:

- `Result` informs the immediate caller.
- Events notify listeners that something happened.
- Progress records delivery progress changes.
- Insights aggregate stored data into analysis.
- Audits preserve meaningful evidence for later inspection.

The host controls audit storage, retention, redaction, authorization, indexing, and presentation. The SDK does not automatically audit every `Result::failure()` or every event; the handler decides which occurrences are important enough to preserve. Avoid audit records for routine execution noise such as entering a method, request started, or loop iteration completed.

```php
use Elqora\Dgp\Audits\AuditLevel;
use Elqora\Dgp\Audits\AuditRecord;
use Elqora\Dgp\Configuration\Dgp;
use Elqora\Dgp\Runtime\References\DeliveryReference;
use Elqora\Dgp\Runtime\References\HandlerReference;

Dgp::registerAuditRepository($auditRepository);

$audits = Dgp::auditRepository(HandlerReference::fromKey('smm-test'));
$audits->record(new AuditRecord(
    id: null,
    key: 'provider.submission_failed',
    level: AuditLevel::ERROR,
    message: 'Provider rejected the submitted order.',
    occurredAt: '2026-07-15T08:30:00Z',
    orderId: 12345,
    delivery: new DeliveryReference(key: 'fulfillment'),
    category: 'provider',
    code: 'provider_rejected',
    context: ['provider_status' => 'rejected'],
));
```

## Events

Events support built-in `EventType` values and custom string values for extensions.

```php
use Elqora\Dgp\Events\DgpEvent;
use Elqora\Dgp\Events\EventType;

$event = new DgpEvent(
    id: 'event-1',
    type: EventType::INITIALIZED,
    handlerKey: 'smm-test',
    orderId: 12345,
);

$custom = new DgpEvent(
    id: 'event-2',
    type: 'provider.custom_event',
    handlerKey: 'smm-test',
    orderId: 12345,
);
```

## Charges And Payments

`Charge` carries the money item, current payment projections, and immutable payment history. Aggregate values such as `paidAmount` and `balanceDue` are useful for presentation and filtering, but hosts should keep `ChargePayment` records for audit trails, partial payment inspection, refunds, and accounting reconciliation.

```php
use Elqora\Dgp\Charges\Charge;
use Elqora\Dgp\Charges\ChargePaymentNotification;
use Elqora\Dgp\Charges\ChargePayment;
use Elqora\Dgp\Charges\ChargePaymentStatus;
use Elqora\Dgp\Charges\ChargeStatus;
use Elqora\Dgp\Charges\ChargeTarget;
use Elqora\Dgp\Charges\ChargeTargetType;
use Elqora\Dgp\Money\Amount;
use Elqora\Dgp\Money\Currency;
use Elqora\Dgp\Money\Money;

$payment = new ChargePayment(
    key: 'payment-1',
    amount: new Money(new Amount('25.00'), new Currency('USD')),
    status: ChargePaymentStatus::PAID,
    paidAt: '2026-07-09T10:00:00Z',
    method: 'wallet',
    reference: 'txn-123',
);

$charge = new Charge(
    id: null,
    key: 'deposit',
    target: new ChargeTarget(ChargeTargetType::PLAN, key: 'plan-payment'),
    label: 'Deposit',
    amount: new Money(new Amount('100.00'), new Currency('USD')),
    status: ChargeStatus::PARTIALLY_PAID,
    paidAmount: new Money(new Amount('25.00'), new Currency('USD')),
    balanceDue: new Money(new Amount('75.00'), new Currency('USD')),
    payments: [$payment],
);
```

Charges are owned by workflow targets, not by deliveries alone. Built-in target types cover plans, segments, and deliveries, and custom string types can represent future workflow objects without changing the `Charge` contract.

```php
$planCharge = new Charge(
    id: null,
    key: 'setup-fee',
    target: new ChargeTarget(ChargeTargetType::PLAN, key: 'plan-payment'),
    label: 'Setup Fee',
    amount: new Money(new Amount('100.00'), new Currency('USD')),
    status: ChargeStatus::PENDING,
);

$segmentCharge = new Charge(
    id: null,
    key: 'approval-fee',
    target: new ChargeTarget(
        type: ChargeTargetType::SEGMENT,
        key: 'approval',
        parent: new ChargeTarget(ChargeTargetType::DELIVERY, key: 'fulfillment'),
    ),
    label: 'Approval Fee',
    amount: new Money(new Amount('25.00'), new Currency('USD')),
    status: ChargeStatus::PENDING,
);

$futureCharge = new Charge(
    id: null,
    key: 'checkpoint-fee',
    target: new ChargeTarget('workflow.custom_checkpoint', key: 'checkpoint-1'),
    label: 'Checkpoint Fee',
    amount: new Money(new Amount('15.00'), new Currency('USD')),
    status: ChargeStatus::PENDING,
);
```

The charge lifecycle is bidirectional:

1. The handler creates or updates a `Charge` through `ChargeUpdateHookContract`.
2. The host persists and presents the charge to the client.
3. The client pays through host-owned checkout or wallet flows.
4. The host records the immutable `ChargePayment`.
5. The host notifies the handler with `ChargePaymentNotificationContract`.
6. The handler progresses its workflow and may emit updated charges, deliveries, plans, next actions, or management state.

The handler owns pricing logic, charge requirements, and workflow progression. The host owns payment gateways, client interaction, payment collection, and payment persistence. `ChargeStateResolverContract` remains available for reconciliation and recovery, but normal workflow progression should use payment notifications instead of polling.

`ChargeTarget` identifies what the charge belongs to. `ChargePaymentNotification` still identifies the order, charge, and payment, and may include `chargeTarget` when the host wants to echo the ownership context back to the handler.

```php
$notification = new ChargePaymentNotification(
    orderId: 12345,
    chargeKey: 'deposit',
    paymentKey: 'payment-1',
    amount: new Money(new Amount('25.00'), new Currency('USD')),
    status: ChargePaymentStatus::PAID,
    occurredAt: '2026-07-09T10:00:01Z',
    paidAt: '2026-07-09T10:00:00Z',
    resultingChargeStatus: ChargeStatus::PARTIALLY_PAID,
    chargeTarget: new ChargeTarget(ChargeTargetType::PLAN, key: 'plan-payment'),
    meta: ['gateway' => 'internal'],
);
```

## Product Definitions

Product-definition catalogs are optional. Every definition has one canonical shape regardless of whether it comes from a handler, Studio, a host, or an import. A handler declaring the `product_definition_catalog` capability implements `ProductDefinitionCatalogContract` and returns root ProductDefinition objects directly.

The PHP DTO uses idiomatic constructor names and emits the canonical snake-case v1 wire keys. `meta` remains one opaque object on the wire even when `ServiceMeta` internally combines provider and host-derived values. Product fields do not have a `component` property; rendering descriptors belong to host integrations rather than `ProductDefinition`.

```php
use Elqora\Dgp\Catalog\Schemas\Contracts\ProductDefinitionCatalogContract;
use Elqora\Dgp\Catalog\Schemas\ProductDefinition;
use Elqora\Dgp\Catalog\Schemas\ProductDefinitionQuery;
use Elqora\Dgp\Errors\Result;

final class ProductCatalog implements ProductDefinitionCatalogContract
{
    public function definitions(ProductDefinitionQuery $query): Result
    {
        return Result::success([
            new ProductDefinition(
                id: 'manual-task-1',
                name: 'Manual Custom Task',
                filters: [['id' => 'tag:manual', 'label' => 'Manual Task']],
                fields: [['id' => 'field:desc', 'type' => 'text', 'label' => 'Instructions']],
                schemaVersion: '1',
            ),
        ]);
    }
}
```

## Insights

Insights, charts, UI manifest support, and private assets are product additions isolated from the mandatory `DgpDriverContract` except where a handler explicitly declares or implements the related capability.

The current package depends on `elqora/chart` because `Analysis` stores an `Elqora\Chart\Charts\Chart` directly. If a smaller core package becomes important, insights/chart support is the main candidate for extraction into an optional package or adapter layer.

```php
use Elqora\Chart\Charts\Charts;
use Elqora\Chart\Enums\ValueType;
use Elqora\Chart\Series\Series;
use Elqora\Dgp\Insights\Analysis;

$chart = Charts::line(
    key: 'delivery.throughput',
    title: 'Delivery throughput',
    category: 'time',
    rows: [
        ['time' => '10:00', 'delivered' => 10],
        ['time' => '10:30', 'delivered' => 25],
    ],
    series: [
        new Series('delivered', 'Delivered', 'delivered', ValueType::INTEGER),
    ],
);

$analysis = new Analysis('delivery.throughput', $chart);
```

## Hooks & Event Ports

The DGP SDK specifies interfaces for asynchronous update hooks, payment notifications, and event dispatching. Charge updates flow from handler to host; payment notifications flow from host to handler after the host records payment state changes.

### 1. Charge Update Hook
Used by handlers/drivers to push charge requirements and charge state changes to the host platform. This remains the handler-to-host synchronization path for charge creation, amount changes, status changes, next actions, and metadata:
```php
use Elqora\Dgp\Charges\Contracts\ChargeUpdateHookContract;
use Elqora\Dgp\Charges\ChargeUpdateRequest;
use Elqora\Dgp\Errors\Result;

class MyChargeUpdateHook implements ChargeUpdateHookContract
{
    public function update(ChargeUpdateRequest $request): Result
    {
        // Host-side charge persistence and presentation logic...
        return Result::success(null);
    }
}
```

### 2. Charge Payment Notification
Used by hosts to notify handlers that a payment state change has been recorded. This is an opt-in handler contract and does not replace `ChargeUpdateHookContract`:
```php
use Elqora\Dgp\Charges\Contracts\ChargePaymentNotificationContract;
use Elqora\Dgp\Charges\ChargePaymentNotification;
use Elqora\Dgp\Errors\Result;

class MyHandlerPaymentNotifications implements ChargePaymentNotificationContract
{
    public function notifyPayment(ChargePaymentNotification $notification): Result
    {
        // Handler-side workflow progression after host-recorded payment...
        return Result::success(null);
    }
}
```

### 3. Charge State Resolver
Used for reconciliation and recovery when the host or handler needs to resolve current charge and payment state. It should not be the normal mechanism for discovering that payment happened:
```php
use Elqora\Dgp\Charges\Contracts\ChargeStateResolverContract;
use Elqora\Dgp\Charges\ChargeStateRequest;
use Elqora\Dgp\Errors\Result;

class MyChargeStateResolver implements ChargeStateResolverContract
{
    public function resolve(ChargeStateRequest $request): Result
    {
        // Resolve current charge and payment state for recovery...
        return Result::success(null);
    }
}
```

### 4. Event Hook
A general, neutral channel for dispatching event telemetry (such as status updates or logs) from the driver to the host:
```php
use Elqora\Dgp\Events\Contracts\EventHookContract;
use Elqora\Dgp\Events\DgpEvent;

class MyEventHook implements EventHookContract
{
    public function dispatch(DgpEvent $event): void
    {
        // Emit or log the event...
    }
}
```

### 5. Webhook Receiver Contract
Unlike the outbound hooks above, `WebhookContract` is an inbound port implemented by the handler to parse and verify incoming HTTP notifications sent by the external provider:
```php
use Elqora\Dgp\Events\Contracts\WebhookContract;
use Elqora\Dgp\Events\WebhookRequest;
use Elqora\Dgp\Errors\Result;

class MyWebhookHandler implements WebhookContract
{
    public function handleWebhook(WebhookRequest $request): Result
    {
        // 1. Verify signatures in $request->headers
        // 2. Decode $request->body
        // 3. Return normalized DgpEvent payload
        return Result::success($normalizedEvent);
    }
}
```

## Testing

```bash
composer test
composer analyse
composer check:contracts
composer check
```

`composer check` is the completion command. It runs the PHPUnit suite, PHPStan, Spec-fixture and dependency-boundary checks, and strict Composer validation. The committed contract fixtures are copied from `dgp-spec` v1 and are compared with a sibling Spec checkout when available.

To run a specific file:

```bash
vendor/bin/phpunit tests/Compliance/RepositoryComplianceTest.php
```
