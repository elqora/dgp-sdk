<?php

namespace Elqora\Dgp\Tests\Compliance;

use PHPUnit\Framework\TestCase;
use Elqora\Chart\Charts\Chart;
use Elqora\Chart\Data\TabularData;
use Elqora\Chart\Enums\ChartType;
use Elqora\Chart\Enums\ValueType;
use Elqora\Chart\Series\Series;
use Elqora\Dgp\Audits\AuditLevel;
use Elqora\Dgp\Audits\AuditQuery;
use Elqora\Dgp\Audits\AuditRecord;
use Elqora\Dgp\Configuration\Dgp;
use Elqora\Dgp\Catalog\Services\HandlerService;
use Elqora\Dgp\Errors\DgpConfigurationException;
use Elqora\Dgp\Manifest\Capability;
use Elqora\Dgp\Insights\Analysis;
use Elqora\Dgp\Insights\Leaderboard;
use Elqora\Dgp\Insights\LeaderboardEntry;
use Elqora\Dgp\Insights\Scoreboard;
use Elqora\Dgp\Insights\ScoreboardItem;
use Elqora\Dgp\Runtime\References\HandlerReference;
use Elqora\Dgp\Runtime\References\DeliveryReference;
use Elqora\Dgp\Runtime\References\PlanReference;
use Elqora\Dgp\Runtime\References\StartResultReference;
use Elqora\Dgp\Runtime\Plan;
use Elqora\Dgp\Runtime\PlanStatus;
use Elqora\Dgp\Runtime\StartResult;
use Elqora\Dgp\Runtime\StartResultStatus;
use Elqora\Dgp\Runtime\Queries\PlanQuery;
use Elqora\Dgp\Runtime\Queries\StartResultQuery;
use Elqora\Dgp\Runtime\Queries\DeliveryQuery;
use Elqora\Dgp\Deliveries\InitializationDelivery;
use Elqora\Dgp\Deliveries\DeliveryProgress;
use Elqora\Dgp\Deliveries\DeliveryProgressSegment;
use Elqora\Dgp\Deliveries\DeliveryStage;
use Elqora\Dgp\Deliveries\DeliveryStatus;
use Elqora\Dgp\Actions\Contracts\NextAction;
use Elqora\Dgp\Actions\ActionButton;
use Elqora\Dgp\Actions\RedirectAction;
use Elqora\Dgp\Progress\DeliveryProgressRecord;
use Elqora\Dgp\Progress\ProgressSource;
use Elqora\Dgp\Progress\ProgressTimelineQuery;
use Elqora\Dgp\Support\Hydrator;
use Elqora\Dgp\Tests\Fixtures\Repository\MockRuntimeRepository;
use Elqora\Dgp\Tests\Fixtures\Repository\MockServicesRepository;
use Elqora\Dgp\Tests\Fixtures\Repository\MockHandlerServicesRepository;
use Elqora\Dgp\Tests\Fixtures\Repository\MockDeliveriesRepository;
use Elqora\Dgp\Tests\Fixtures\Repository\MockDeliveryProgressRepository;
use Elqora\Dgp\Tests\Fixtures\Repository\MockAuditRepository;
use Elqora\Dgp\Tests\Fixtures\Repository\MockInsightsRepository;
use Elqora\Dgp\Tests\Fixtures\Repository\MockHandlerInsightsRepository;
use InvalidArgumentException;

class RepositoryComplianceTest extends TestCase
{
    private function chartFixture(string $key = 'delivery.throughput'): Chart
    {
        return new Chart(
            key: $key,
            type: ChartType::LINE,
            title: 'Delivery throughput',
            data: new TabularData(
                categoryField: 'time',
                rows: [
                    ['time' => '10:00', 'delivered' => 10],
                    ['time' => '10:30', 'delivered' => 25],
                ],
                series: [
                    new Series('delivered', 'Delivered', 'delivered', ValueType::INTEGER),
                ],
            ),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Reset registered repositories
        $ref = new \ReflectionClass(Dgp::class);
        foreach (['runtimeRepository', 'servicesRepository', 'deliveriesRepository', 'insightsRepository', 'deliveryProgressRepository', 'auditRepository'] as $property) {
            $prop = $ref->getProperty($property);
            $prop->setAccessible(true);
            $prop->setValue(null, null);
        }
    }

    public function testNotRegisteredThrowsCustomConfigurationException(): void
    {
        $this->expectException(DgpConfigurationException::class);
        $this->expectExceptionMessage('No DGP runtime repository has been registered.');

        Dgp::runtimeRepository(HandlerReference::fromId(1));
    }

    public function testResolveFailsWithOriginalErrorCode(): void
    {
        Dgp::registerRuntimeRepository(new MockRuntimeRepository());

        // forHandler returns fail on 'unknown-handler'
        $this->expectException(DgpConfigurationException::class);
        $this->expectExceptionMessage('Unknown handler reference provided.');

        Dgp::runtimeRepository(HandlerReference::fromId('unknown-handler'));
    }

    public function testRuntimeRepositoryIsReadOnlyAndHandlerScoped(): void
    {
        $runtimeRepository = new MockRuntimeRepository();
        $handler = HandlerReference::fromKey('jap');
        $otherHandler = HandlerReference::fromKey('smm');
        Dgp::registerRuntimeRepository($runtimeRepository);

        $japRepo = Dgp::runtimeRepository($handler);
        $smmRepo = Dgp::runtimeRepository($otherHandler);

        $plan = new Plan(null, 'main-plan', ['step' => 1]);
        $saveRes = $runtimeRepository->seedPlan($handler, 123, $plan);
        $this->assertTrue($saveRes->isSuccess());
        $savedJapPlan = $saveRes->value();
        $this->assertNotNull($savedJapPlan);

        $findRes = $smmRepo->findPlan(123, new PlanReference(id: $savedJapPlan->id));
        $this->assertTrue($findRes->isSuccess());
        $this->assertNull($findRes->value());

        $findResOtherOrder = $japRepo->findPlan(999, new PlanReference(id: $savedJapPlan->id));
        $this->assertTrue($findResOtherOrder->isSuccess());
        $this->assertNull($findResOtherOrder->value());

        $this->assertFalse(method_exists($japRepo, 'savePlan'));
        $this->assertFalse(method_exists($japRepo, 'saveStartResult'));
        $this->assertFalse(method_exists($japRepo, 'saveDeliveries'));
    }

    public function testRuntimeRepositoryReadsHostPersistedGraph(): void
    {
        $runtimeRepository = new MockRuntimeRepository();
        $handler = HandlerReference::fromKey('jap');
        Dgp::registerRuntimeRepository($runtimeRepository);
        $repo = Dgp::runtimeRepository($handler);

        $del = new InitializationDelivery(null, 'init-del-1', DeliveryStatus::PENDING, 'Verify account');
        $plan = new Plan(null, 'auth-flow', ['auth' => 'oauth'], [$del]);

        $res = $runtimeRepository->seedPlan($handler, 123, $plan);
        $this->assertTrue($res->isSuccess());
        $persisted = $res->value();
        $this->assertNotNull($persisted);
        $this->assertNull($plan->id);
        $this->assertNull($plan->deliveries[0]->id);

        $this->assertNotNull($persisted->id);
        $this->assertEquals(1, $persisted->revision);
        $this->assertCount(1, $persisted->deliveries);
        $this->assertNotNull($persisted->deliveries[0]->id);
        $this->assertEquals($persisted->id, $persisted->deliveries[0]->planId);
        $this->assertNull($persisted->deliveries[0]->startId);

        $fetched = $repo->deliveriesForPlan(123, new PlanReference(id: $persisted->id))->value();
        $this->assertCount(1, $fetched);
        $this->assertEquals('init-del-1', $fetched[0]->key);
    }

    public function testOrderRuntimeViewSelections(): void
    {
        $runtimeRepository = new MockRuntimeRepository();
        $handler = HandlerReference::fromKey('jap');
        Dgp::registerRuntimeRepository($runtimeRepository);
        $repo = Dgp::runtimeRepository($handler);

        $plan1 = $runtimeRepository->seedPlan($handler, 123, new Plan(null, 'plan-1', []))->value();
        $this->assertNotNull($plan1);
        $plan2 = $runtimeRepository->seedPlan($handler, 123, new Plan(null, 'plan-2', []))->value();
        $this->assertNotNull($plan2);

        $this->assertNotNull($plan1->id);
        /** @var string|int $p1Id */
        $p1Id = $plan1->id;

        $startResult = $runtimeRepository->seedStartResult($handler, $p1Id, new StartResult(null, 'start-1', [], [], null, [], $p1Id))->value();
        $this->assertNotNull($startResult);

        $runtimeRepository->setCurrentPlan($handler, 123, $plan2->id);
        $runtimeRepository->setCurrentStartResult($handler, 123, $startResult->id);

        $view = $repo->runtime(123)->value();
        $this->assertNotNull($view);

        $this->assertEquals(123, $view->orderId);
        $this->assertCount(2, $view->plans);
        $this->assertCount(1, $view->startResults);
        $this->assertNotNull($view->currentPlan);
        $this->assertEquals($plan2->id, $view->currentPlan->id);
        $this->assertNotNull($view->currentStartResult);
        $this->assertEquals($startResult->id, $view->currentStartResult->id);
    }

    public function testRepositoryPreservesDeliveryNextAction(): void
    {
        $runtimeRepository = new MockRuntimeRepository();
        $handler = HandlerReference::fromKey('jap');
        Dgp::registerRuntimeRepository($runtimeRepository);
        $repo = Dgp::runtimeRepository($handler);

        $redirectAction = new RedirectAction(
            url: 'https://gateway.example/download',
            external: true,
            label: 'Download File'
        );

        // 1. Save plan with a delivery carrying a next action
        $plan = new Plan(
            id: null,
            key: 'plan-1',
            state: [],
            deliveries: [
                new InitializationDelivery(
                    id: null,
                    key: 'init-del',
                    status: DeliveryStatus::PROCESSING,
                    label: 'Init Doc',
                    progress: null,
                    planId: null,
                    startId: null,
                    nextAction: $redirectAction,
                    buttons: [
                        new ActionButton(value: 'approve', label: 'Approve'),
                    ]
                )
            ],
            buttons: [
                new ActionButton(value: 'cancel', label: 'Cancel'),
            ]
        );

        $savedPlan = $runtimeRepository->seedPlan($handler, 123, $plan)->value();
        $this->assertNotNull($savedPlan);
        $this->assertCount(1, $savedPlan->buttons);
        $this->assertEquals('cancel', $savedPlan->buttons[0]->value);
        $this->assertCount(1, $savedPlan->deliveries);
        $this->assertCount(1, $savedPlan->deliveries[0]->buttons);
        $this->assertEquals('approve', $savedPlan->deliveries[0]->buttons[0]->value);
        $this->assertNotNull($savedPlan->deliveries[0]->nextAction);
        $action1 = $savedPlan->deliveries[0]->nextAction;
        $this->assertInstanceOf(RedirectAction::class, $action1);
        $this->assertEquals('https://gateway.example/download', $action1->url);

        // 2. Fetch deliveries from repo
        $fetched = $repo->deliveriesForPlan(123, new PlanReference(id: $savedPlan->id))->value();
        $this->assertCount(1, $fetched);
        $this->assertCount(1, $fetched[0]->buttons);
        $this->assertEquals('approve', $fetched[0]->buttons[0]->value);
        $this->assertNotNull($fetched[0]->nextAction);
        $action2 = $fetched[0]->nextAction;
        $this->assertInstanceOf(RedirectAction::class, $action2);
        $this->assertEquals('https://gateway.example/download', $action2->url);

        $deliveryUpdate = new InitializationDelivery(
            id: $fetched[0]->id,
            key: 'init-del',
            status: DeliveryStatus::COMPLETED,
            label: 'Init Doc Finished',
            progress: null,
            planId: $savedPlan->id,
            startId: null,
            nextAction: null, // Clear nextAction
            buttons: $fetched[0]->buttons
        );

        $updatedList = $runtimeRepository->seedDeliveries($handler, 123, [$deliveryUpdate])->value();
        $this->assertCount(1, $updatedList);
        $this->assertNull($updatedList[0]->nextAction);
        $this->assertCount(1, $updatedList[0]->buttons);

        // Fetch again to verify cleared in repo store
        $fetchedUpdated = $repo->deliveriesForPlan(123, new PlanReference(id: $savedPlan->id))->value();
        $this->assertNull($fetchedUpdated[0]->nextAction);
        $this->assertCount(1, $fetchedUpdated[0]->buttons);
    }

    public function testRuntimeRepositoryDoesNotExposeServiceStorageOrInsightHooks(): void
    {
        Dgp::registerRuntimeRepository(new MockRuntimeRepository());
        $repo = Dgp::runtimeRepository(HandlerReference::fromKey('jap'));

        $this->assertFalse(method_exists($repo, 'enable'));
        $this->assertFalse(method_exists($repo, 'upsertService'));
        $this->assertFalse(method_exists($repo, 'upsertServices'));
        $this->assertFalse(method_exists($repo, 'updateAnalyses'));
        $this->assertFalse(method_exists($repo, 'updateScoreboard'));
        $this->assertFalse(method_exists($repo, 'updateLeaderboard'));
    }

    public function testServiceStateUpdatesRequireReasonAndRemainHandlerScoped(): void
    {
        $store = (new MockRuntimeRepository())->store;
        $servicesRepo = new MockServicesRepository($store);
        $servicesRepo->seedServices(HandlerReference::fromKey('jap'), [
            new HandlerService(101, 'Premium delivery', category: 'delivery', rate: 12.5, min: 100, max: 10000, capabilities: ['refill'], meta: ['region' => 'global']),
            new HandlerService(102, 'Standard delivery', category: 'delivery', rate: 8.0, min: 50, max: 5000, meta: ['region' => 'global']),
            new HandlerService(103, 'Risky delivery', category: 'delivery', rate: 4.0, min: 10, max: 1000, meta: ['region' => 'local']),
        ]);
        Dgp::registerServicesRepository($servicesRepo);
        /** @var MockHandlerServicesRepository $japRepo */
        $japRepo = Dgp::servicesRepository(HandlerReference::fromKey('jap'));
        /** @var MockHandlerServicesRepository $smmRepo */
        $smmRepo = Dgp::servicesRepository(HandlerReference::fromKey('smm'));

        $this->assertFalse(method_exists($japRepo, 'upsertService'));
        $this->assertFalse(method_exists($japRepo, 'upsertServices'));
        $this->assertEquals('Premium delivery', $japRepo->findService(101)->value()?->name);
        $this->assertEquals(100, $japRepo->findService(101)->value()?->min);
        $this->assertTrue($japRepo->findService(101)->value()?->capabilities->enabled('refill') ?? false);
        $this->assertCount(2, $japRepo->services(new \Elqora\Dgp\Catalog\Services\ServiceQuery(filters: ['region' => 'global']))->value());

        $this->assertTrue($japRepo->enable(101, 'Provider confirmed availability.')->isSuccess());
        $this->assertTrue($japRepo->disable(102, 'Upstream maintenance.')->isSuccess());
        $this->assertTrue($japRepo->lock(103, 'Investigating inconsistent delivery.')->isSuccess());
        $this->assertTrue($japRepo->unlock(103, 'Delivery metrics recovered.')->isSuccess());

        $state = $japRepo->serviceState(103);
        $this->assertNotNull($state);
        $this->assertEquals('enabled', $state['state']);
        $this->assertEquals('Delivery metrics recovered.', $state['reason']);
        $this->assertNull($smmRepo->serviceState(103));

        // Verify projected state on findService
        $service103 = $japRepo->findService(103)->value();
        $this->assertNotNull($service103);
        $this->assertEquals(\Elqora\Dgp\Catalog\Services\HandlerServiceState::ENABLED, $service103->state);
        $this->assertEquals('Delivery metrics recovered.', $service103->stateReason);

        $service102 = $japRepo->findService(102)->value();
        $this->assertNotNull($service102);
        $this->assertEquals(\Elqora\Dgp\Catalog\Services\HandlerServiceState::DISABLED, $service102->state);
        $this->assertEquals('Upstream maintenance.', $service102->stateReason);

        $emptyReason = $japRepo->disable(104, '   ');
        $this->assertTrue($emptyReason->isFailure());
        $error = $emptyReason->error();
        $this->assertNotNull($error);
        $this->assertEquals('service_state_reason_required', $error->code);
    }

    public function testServiceQueryStateFiltering(): void
    {
        $store = (new MockRuntimeRepository())->store;
        $servicesRepo = new MockServicesRepository($store);
        $servicesRepo->seedServices(HandlerReference::fromKey('jap'), [
            new HandlerService(101, 'Premium delivery', category: 'delivery'),
            new HandlerService(102, 'Standard delivery', category: 'delivery'),
            new HandlerService(103, 'Risky delivery', category: 'delivery'),
        ]);
        Dgp::registerServicesRepository($servicesRepo);
        $japRepo = Dgp::servicesRepository(HandlerReference::fromKey('jap'));

        // Set some states
        $japRepo->disable(102, 'Maintenance');
        $japRepo->lock(103, 'Investigation');

        // By default, query filters to only ENABLED services
        $enabledServices = $japRepo->services(new \Elqora\Dgp\Catalog\Services\ServiceQuery())->value();
        $this->assertCount(1, $enabledServices);
        $this->assertEquals(101, $enabledServices[0]->id);

        // Include unavailable returns all services
        $allServices = $japRepo->services(new \Elqora\Dgp\Catalog\Services\ServiceQuery(includeUnavailable: true))->value();
        $this->assertCount(3, $allServices);

        // Filter specifically by states (LOCKED, DISABLED)
        $filteredServices = $japRepo->services(new \Elqora\Dgp\Catalog\Services\ServiceQuery(
            states: [\Elqora\Dgp\Catalog\Services\HandlerServiceState::LOCKED, \Elqora\Dgp\Catalog\Services\HandlerServiceState::DISABLED]
        ))->value();
        $this->assertCount(2, $filteredServices);
        $ids = array_map(fn ($s) => $s->id, $filteredServices);
        $this->assertContains(102, $ids);
        $this->assertContains(103, $ids);
    }

    public function testBulkServiceLookup(): void
    {
        $store = (new MockRuntimeRepository())->store;
        $servicesRepo = new MockServicesRepository($store);
        $servicesRepo->seedServices(HandlerReference::fromKey('jap'), [
            new HandlerService(101, 'Premium delivery', category: 'delivery'),
            new HandlerService(102, 'Standard delivery', category: 'delivery'),
            new HandlerService(103, 'Risky delivery', category: 'delivery'),
        ]);
        Dgp::registerServicesRepository($servicesRepo);
        $japRepo = Dgp::servicesRepository(HandlerReference::fromKey('jap'));

        // 1. Successful lookup preserving order and resolving duplicates
        $lookupResult = $japRepo->findServices([103, 101, 103, 102])->value();
        $this->assertNotNull($lookupResult);
        $this->assertInstanceOf(\Elqora\Dgp\Catalog\Services\HandlerServiceLookupResult::class, $lookupResult);
        
        $this->assertCount(3, $lookupResult->services);
        $this->assertEquals(103, $lookupResult->services[0]->id);
        $this->assertEquals(101, $lookupResult->services[1]->id);
        $this->assertEquals(102, $lookupResult->services[2]->id);
        $this->assertEmpty($lookupResult->missingIds);

        // 2. Lookup with missing IDs
        $lookupResultWithMissing = $japRepo->findServices([101, 999, 102, 888])->value();
        $this->assertCount(2, $lookupResultWithMissing->services);
        $this->assertEquals(101, $lookupResultWithMissing->services[0]->id);
        $this->assertEquals(102, $lookupResultWithMissing->services[1]->id);
        $this->assertEquals([999, 888], $lookupResultWithMissing->missingIds);

        // 3. Lookup with empty input
        $emptyLookup = $japRepo->findServices([])->value();
        $this->assertEmpty($emptyLookup->services);
        $this->assertEmpty($emptyLookup->missingIds);
    }

    public function testInsightUpdatesStoreHandlerScopedSnapshots(): void
    {
        $store = (new MockRuntimeRepository())->store;
        Dgp::registerInsightsRepository(new MockInsightsRepository($store));
        /** @var MockHandlerInsightsRepository $japRepo */
        $japRepo = Dgp::insightsRepository(HandlerReference::fromKey('jap'));
        /** @var MockHandlerInsightsRepository $smmRepo */
        $smmRepo = Dgp::insightsRepository(HandlerReference::fromKey('smm'));

        $analyses = [
            new Analysis('delivery.throughput', $this->chartFixture()),
            new Analysis('delivery.latency', $this->chartFixture('delivery.latency')),
        ];
        $scoreboard = new Scoreboard([
            new ScoreboardItem('delivery.success-rate', 98.5, 'Success rate', unit: '%'),
            new ScoreboardItem('delivery.average-time', 42, 'Average time', unit: 'seconds'),
        ]);
        $leaderboard = new Leaderboard([
            new LeaderboardEntry(101, 1, 99.2, 'Premium delivery'),
            new LeaderboardEntry(102, 2, 96.1, 'Standard delivery'),
        ]);

        $this->assertTrue($japRepo->updateAnalyses($analyses)->isSuccess());
        $this->assertTrue($japRepo->updateScoreboard($scoreboard)->isSuccess());
        $this->assertTrue($japRepo->updateLeaderboard($leaderboard)->isSuccess());

        $this->assertCount(2, $japRepo->analysesSnapshot());
        $this->assertEquals('delivery.latency', $japRepo->analysesSnapshot()[1]->analysisKey);
        $this->assertSame($scoreboard, $japRepo->scoreboardSnapshot());
        $this->assertSame($leaderboard, $japRepo->leaderboardSnapshot());

        $this->assertCount(0, $smmRepo->analysesSnapshot());
        $this->assertNull($smmRepo->scoreboardSnapshot());
        $this->assertNull($smmRepo->leaderboardSnapshot());

        $replacement = new Scoreboard([
            new ScoreboardItem('delivery.success-rate', 99.1, 'Success rate', unit: '%'),
        ]);
        $this->assertTrue($japRepo->updateScoreboard($replacement)->isSuccess());
        $this->assertSame($replacement, $japRepo->scoreboardSnapshot());
        $this->assertCount(1, $japRepo->scoreboardSnapshot()->items);
    }

    public function testInsightAnalysisUpdatesRejectDuplicateKeys(): void
    {
        $store = (new MockRuntimeRepository())->store;
        Dgp::registerInsightsRepository(new MockInsightsRepository($store));
        /** @var MockHandlerInsightsRepository $repo */
        $repo = Dgp::insightsRepository(HandlerReference::fromKey('jap'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Analysis key values must be unique.');

        $repo->updateAnalyses([
            new Analysis('delivery.throughput', $this->chartFixture()),
            new Analysis('delivery.throughput', $this->chartFixture()),
        ]);
    }

    public function testDeliveriesRepositoryReadsPersistedHandlerDeliveries(): void
    {
        $runtimeRepository = new MockRuntimeRepository();
        $handler = HandlerReference::fromKey('jap');
        Dgp::registerRuntimeRepository($runtimeRepository);
        Dgp::registerDeliveriesRepository(new MockDeliveriesRepository($runtimeRepository->store));

        Dgp::runtimeRepository($handler);
        $deliveries = Dgp::deliveriesRepository(HandlerReference::fromKey('jap'));
        $otherDeliveries = Dgp::deliveriesRepository(HandlerReference::fromKey('smm'));

        $plan = $runtimeRepository->seedPlan(
            $handler,
            123,
            new Plan(
                null,
                'plan-1',
                [],
                [
                    new InitializationDelivery(
                        id: null,
                        key: 'init-del',
                        status: DeliveryStatus::PROCESSING,
                        label: 'Init Doc',
                    ),
                ]
            )
        )->value();
        $this->assertNotNull($plan);
        $this->assertCount(1, $plan->deliveries);

        $delivery = $plan->deliveries[0];
        $this->assertNotNull($delivery->id);

        $foundById = $deliveries->findDelivery(new DeliveryReference(id: $delivery->id))->value();
        $this->assertNotNull($foundById);
        $this->assertEquals('init-del', $foundById->key);

        $foundByKey = $deliveries->findDelivery(new DeliveryReference(key: 'init-del'))->value();
        $this->assertNotNull($foundByKey);
        $this->assertEquals($delivery->id, $foundByKey->id);

        $this->assertCount(1, $deliveries->deliveries(new DeliveryQuery(status: DeliveryStatus::PROCESSING))->value());
        $this->assertCount(1, $deliveries->deliveries(new DeliveryQuery(active: true))->value());
        $this->assertCount(0, $deliveries->deliveries(new DeliveryQuery(status: DeliveryStatus::COMPLETED))->value());
        $this->assertCount(0, $otherDeliveries->deliveries()->value());
    }

    public function testProgressRecordAndQuerySerializationRoundTrips(): void
    {
        $record = new DeliveryProgressRecord(
            id: null,
            orderId: 123,
            delivery: new DeliveryReference(id: 456, key: 'fulfill-1'),
            stage: DeliveryStage::FULFILLMENT,
            progress: new DeliveryProgress(
                current: 25,
                target: 100,
                percent: 25,
                unit: 'items',
                segments: [
                    new DeliveryProgressSegment(
                        key: 'provider-import',
                        progress: new DeliveryProgress(current: 10, target: 40, percent: 25, unit: 'items'),
                        label: 'Provider import',
                        status: 'processing',
                        sequence: 1
                    ),
                ]
            ),
            recordedAt: '2026-07-10T10:15:00Z',
            source: ProgressSource::SYNCHRONIZATION,
            meta: ['trace' => 'sync-1']
        );

        $serialized = $record->toArray();
        $this->assertNull($serialized['id']);
        $this->assertEquals(123, $serialized['order_id']);
        $this->assertEquals(['id' => 456, 'key' => 'fulfill-1'], $serialized['delivery']);
        $this->assertEquals('fulfillment', $serialized['stage']);
        $this->assertEquals('synchronization', $serialized['source']);
        $this->assertEquals('2026-07-10T10:15:00Z', $serialized['recorded_at']);
        $this->assertEquals(25.0, $serialized['progress']['percent']);
        $this->assertEquals('provider-import', $serialized['progress']['segments'][0]['key']);

        $hydrated = Hydrator::hydrate(DeliveryProgressRecord::class, $serialized);
        $this->assertTrue(Hydrator::compare($record, $hydrated));
        $this->assertEquals(456, $hydrated->delivery->id);
        $this->assertEquals('fulfill-1', $hydrated->delivery->key);

        $query = new ProgressTimelineQuery();
        $this->assertTrue($query->ascending);
        $this->assertTrue(Hydrator::compare($query, Hydrator::hydrate(ProgressTimelineQuery::class, $query->toArray())));

        $reference = new DeliveryReference(key: 'init-1');
        $this->assertTrue(Hydrator::compare($reference, Hydrator::hydrate(DeliveryReference::class, $reference->toArray())));
    }

    public function testDeliveryProgressRepositoryRegistrationResolutionAndRecording(): void
    {
        $store = (new MockRuntimeRepository())->store;
        Dgp::registerDeliveryProgressRepository(new MockDeliveryProgressRepository($store));

        $result = Dgp::resolveDeliveryProgressRepository(HandlerReference::fromKey('jap'));
        $this->assertTrue($result->isSuccess());

        $repo = Dgp::deliveryProgressRepository(HandlerReference::fromKey('jap'));
        $otherRepo = Dgp::deliveryProgressRepository(HandlerReference::fromKey('smm'));

        $first = $repo->record(new DeliveryProgressRecord(
            id: null,
            orderId: 123,
            delivery: new DeliveryReference(id: 10, key: 'init-del'),
            stage: DeliveryStage::INITIALIZATION,
            progress: new DeliveryProgress(percent: 10),
            recordedAt: '2026-07-10T10:00:00Z',
            source: ProgressSource::HANDLER
        ))->value();
        $this->assertNotNull($first);
        $this->assertNotNull($first->id);

        $repo->record(new DeliveryProgressRecord(
            id: null,
            orderId: 123,
            delivery: new DeliveryReference(id: 10, key: 'init-del'),
            stage: DeliveryStage::INITIALIZATION,
            progress: new DeliveryProgress(percent: 50),
            recordedAt: '2026-07-10T10:05:00Z',
            source: ProgressSource::WEBHOOK
        ));

        $repo->record(new DeliveryProgressRecord(
            id: null,
            orderId: 999,
            delivery: new DeliveryReference(key: 'other-del'),
            stage: DeliveryStage::FULFILLMENT,
            progress: new DeliveryProgress(percent: 90),
            recordedAt: '2026-07-10T10:10:00Z',
            source: ProgressSource::MANUAL
        ));

        $timeline = $repo->timeline(new DeliveryReference(id: 10))->value();
        $this->assertCount(2, $timeline);
        $this->assertEquals(10.0, $timeline[0]->progress->percent);
        $this->assertEquals(50.0, $timeline[1]->progress->percent);

        $webhookTimeline = $repo->timeline(
            new DeliveryReference(key: 'init-del'),
            new ProgressTimelineQuery(source: ProgressSource::WEBHOOK)
        )->value();
        $this->assertCount(1, $webhookTimeline);
        $this->assertSame(ProgressSource::WEBHOOK, $webhookTimeline[0]->source);

        $limitedDescending = $repo->timelineForOrder(
            123,
            new ProgressTimelineQuery(limit: 1, ascending: false)
        )->value();
        $this->assertCount(1, $limitedDescending);
        $this->assertEquals(50.0, $limitedDescending[0]->progress->percent);

        $this->assertCount(0, $otherRepo->timelineForOrder(123)->value());
    }

    public function testDeliveryProgressRepositoryMissingAndFailurePaths(): void
    {
        $missing = Dgp::resolveDeliveryProgressRepository(HandlerReference::fromKey('jap'));
        $this->assertTrue($missing->isFailure());
        $this->assertEquals('delivery_progress_repository_not_registered', $missing->error()?->code);

        try {
            Dgp::deliveryProgressRepository(HandlerReference::fromKey('jap'));
            $this->fail('Expected missing delivery progress repository exception.');
        } catch (DgpConfigurationException $exception) {
            $this->assertEquals('delivery_progress_repository_not_registered', $exception->errorCode);
        }

        $store = (new MockRuntimeRepository())->store;
        Dgp::registerDeliveryProgressRepository(new MockDeliveryProgressRepository($store));

        try {
            Dgp::deliveryProgressRepository(HandlerReference::fromId('unknown-handler'));
            $this->fail('Expected delivery progress repository resolution exception.');
        } catch (DgpConfigurationException $exception) {
            $this->assertEquals('unknown_handler', $exception->errorCode);
        }
    }

    public function testAuditRecordQueryAndCapabilitySerializationRoundTrips(): void
    {
        $record = new AuditRecord(
            id: null,
            key: 'provider.submission_failed',
            level: AuditLevel::ERROR,
            message: 'Provider rejected the submitted order.',
            occurredAt: '2026-07-15T08:30:00Z',
            orderId: 123,
            delivery: new DeliveryReference(id: 456, key: 'fulfill-1'),
            category: 'provider',
            code: 'provider_rejected',
            context: ['provider_order_id' => 'P-100'],
            meta: ['redacted' => true],
        );

        $serialized = $record->toArray();
        $this->assertEquals('error', AuditLevel::ERROR->value);
        $this->assertEquals('audits', Capability::AUDITS->value);
        $this->assertNull($serialized['id']);
        $this->assertEquals('provider.submission_failed', $serialized['key']);
        $this->assertEquals('error', $serialized['level']);
        $this->assertEquals('Provider rejected the submitted order.', $serialized['message']);
        $this->assertEquals('2026-07-15T08:30:00Z', $serialized['occurred_at']);
        $this->assertEquals(123, $serialized['order_id']);
        $this->assertEquals(['id' => 456, 'key' => 'fulfill-1'], $serialized['delivery']);
        $this->assertEquals('provider_rejected', $serialized['code']);

        $hydrated = Hydrator::hydrate(AuditRecord::class, $serialized);
        $this->assertTrue(Hydrator::compare($record, $hydrated));
        $this->assertSame(AuditLevel::ERROR, $hydrated->level);
        $this->assertEquals(456, $hydrated->delivery?->id);
        $this->assertEquals('fulfill-1', $hydrated->delivery?->key);

        $query = new AuditQuery(
            level: AuditLevel::WARNING,
            category: 'provider',
            code: 'unknown_status',
            orderId: 123,
            delivery: new DeliveryReference(key: 'fulfill-1'),
            from: '2026-07-15T08:00:00Z',
            to: '2026-07-15T09:00:00Z',
            limit: 10,
            cursor: 'cursor-1',
            meta: ['include_context' => true],
        );

        $querySerialized = $query->toArray();
        $this->assertFalse((new AuditQuery())->ascending);
        $this->assertEquals('warning', $querySerialized['level']);
        $this->assertEquals(['id' => null, 'key' => 'fulfill-1'], $querySerialized['delivery']);
        $this->assertFalse($querySerialized['ascending']);
        $this->assertTrue(Hydrator::compare($query, Hydrator::hydrate(AuditQuery::class, $querySerialized)));
    }

    public function testAuditRecordValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Audit record key must not be empty.');

        new AuditRecord(
            id: null,
            key: ' ',
            level: AuditLevel::ERROR,
            message: 'Provider rejected the submitted order.',
            occurredAt: '2026-07-15T08:30:00Z',
        );
    }

    public function testAuditRecordRejectsUnstableKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Audit record key must be a stable identifier.');

        new AuditRecord(
            id: null,
            key: 'provider failed',
            level: AuditLevel::ERROR,
            message: 'Provider rejected the submitted order.',
            occurredAt: '2026-07-15T08:30:00Z',
        );
    }

    public function testAuditRecordRejectsEmptyMessage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Audit record message must not be empty.');

        new AuditRecord(
            id: null,
            key: 'provider.submission_failed',
            level: AuditLevel::ERROR,
            message: ' ',
            occurredAt: '2026-07-15T08:30:00Z',
        );
    }

    public function testAuditRecordRejectsEmptyOccurredAt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Audit record occurredAt must not be empty.');

        new AuditRecord(
            id: null,
            key: 'provider.submission_failed',
            level: AuditLevel::ERROR,
            message: 'Provider rejected the submitted order.',
            occurredAt: ' ',
        );
    }

    public function testAuditRepositoryRegistrationResolutionRecordingAndFiltering(): void
    {
        $store = (new MockRuntimeRepository())->store;
        Dgp::registerAuditRepository(new MockAuditRepository($store));

        $result = Dgp::resolveAuditRepository(HandlerReference::fromKey('jap'));
        $this->assertTrue($result->isSuccess());

        $repo = Dgp::auditRepository(HandlerReference::fromKey('jap'));
        $otherRepo = Dgp::auditRepository(HandlerReference::fromKey('smm'));

        $first = $repo->record(new AuditRecord(
            id: null,
            key: 'provider.submission_failed',
            level: AuditLevel::ERROR,
            message: 'Provider rejected the submitted order.',
            occurredAt: '2026-07-15T08:00:00Z',
            orderId: 123,
            delivery: new DeliveryReference(id: 10, key: 'fulfill-1'),
            category: 'provider',
            code: 'provider_rejected',
            context: ['provider_order_id' => 'P-100'],
        ))->value();
        $this->assertNotNull($first->id);

        $repo->record(new AuditRecord(
            id: null,
            key: 'provider.unknown_status',
            level: AuditLevel::WARNING,
            message: 'The handler received an unsupported provider status.',
            occurredAt: '2026-07-15T08:05:00Z',
            orderId: 123,
            delivery: new DeliveryReference(id: 10, key: 'fulfill-1'),
            category: 'provider',
            code: 'unknown_status',
        ));

        $repo->record(new AuditRecord(
            id: null,
            key: 'fallback.exhausted',
            level: AuditLevel::CRITICAL,
            message: 'All fallback services were exhausted.',
            occurredAt: '2026-07-15T08:10:00Z',
            orderId: 999,
            delivery: new DeliveryReference(key: 'fallback-1'),
            category: 'fallback',
            code: 'exhausted',
        ));

        $records = $repo->records()->value();
        $this->assertCount(3, $records);
        $this->assertEquals('fallback.exhausted', $records[0]->key);
        $this->assertEquals('provider.submission_failed', $records[2]->key);

        $ascending = $repo->records(new AuditQuery(ascending: true))->value();
        $this->assertEquals('provider.submission_failed', $ascending[0]->key);

        $limited = $repo->records(new AuditQuery(limit: 1))->value();
        $this->assertCount(1, $limited);
        $this->assertEquals('fallback.exhausted', $limited[0]->key);

        $orderRecords = $repo->recordsForOrder(123)->value();
        $this->assertCount(2, $orderRecords);

        $deliveryRecords = $repo->records(new AuditQuery(delivery: new DeliveryReference(key: 'fulfill-1')))->value();
        $this->assertCount(2, $deliveryRecords);

        $warningRecords = $repo->records(new AuditQuery(level: AuditLevel::WARNING))->value();
        $this->assertCount(1, $warningRecords);
        $this->assertEquals('provider.unknown_status', $warningRecords[0]->key);

        $providerRecords = $repo->records(new AuditQuery(category: 'provider'))->value();
        $this->assertCount(2, $providerRecords);

        $codeRecords = $repo->records(new AuditQuery(code: 'provider_rejected'))->value();
        $this->assertCount(1, $codeRecords);
        $this->assertEquals('provider.submission_failed', $codeRecords[0]->key);

        $dateRecords = $repo->records(new AuditQuery(
            from: '2026-07-15T08:01:00Z',
            to: '2026-07-15T08:09:00Z',
        ))->value();
        $this->assertCount(1, $dateRecords);
        $this->assertEquals('provider.unknown_status', $dateRecords[0]->key);

        $this->assertCount(0, $otherRepo->records()->value());
    }

    public function testAuditRepositoryMissingAndFailurePaths(): void
    {
        $missing = Dgp::resolveAuditRepository(HandlerReference::fromKey('jap'));
        $this->assertTrue($missing->isFailure());
        $this->assertEquals('audit_repository_not_registered', $missing->error()?->code);

        try {
            Dgp::auditRepository(HandlerReference::fromKey('jap'));
            $this->fail('Expected missing audit repository exception.');
        } catch (DgpConfigurationException $exception) {
            $this->assertEquals('audit_repository_not_registered', $exception->errorCode);
        }

        $store = (new MockRuntimeRepository())->store;
        Dgp::registerAuditRepository(new MockAuditRepository($store));

        try {
            Dgp::auditRepository(HandlerReference::fromId('unknown-handler'));
            $this->fail('Expected audit repository resolution exception.');
        } catch (DgpConfigurationException $exception) {
            $this->assertEquals('unknown_handler', $exception->errorCode);
        }
    }

    public function testRepositoryStatusUpdatesAndOptimisticConcurrency(): void
    {
        $runtimeRepo = new MockRuntimeRepository();
        Dgp::registerRuntimeRepository($runtimeRepo);
        /** @var \Elqora\Dgp\Tests\Fixtures\Repository\MockHandlerRuntimeRepository $repo */
        $repo = Dgp::runtimeRepository(HandlerReference::fromKey('jap'));

        // 1. Seed plan & check initial status
        $plan = new Plan(null, 'main-plan', [], [], null, [], 0);
        $savedPlan = $runtimeRepo->seedPlan(HandlerReference::fromKey('jap'), 123, $plan)->value();
        $this->assertNotNull($savedPlan);
        /** @var Plan $savedPlan */
        $this->assertEquals(PlanStatus::ACTIVE, $savedPlan->status);
        $this->assertEquals(1, $savedPlan->revision);

        $planId = $savedPlan->id;
        $planKey = $savedPlan->key;
        $this->assertNotNull($planId);

        // 2. Update status and verify incremented revision
        $updateRes = $repo->updatePlanStatus($planId, PlanStatus::CANCELLED);
        $this->assertTrue($updateRes->isSuccess());

        $fetchedPlan = $repo->findPlan(123, new PlanReference(id: $planId))->value();
        $this->assertNotNull($fetchedPlan);
        $this->assertEquals(PlanStatus::CANCELLED, $fetchedPlan->status);
        $this->assertEquals(2, $fetchedPlan->revision);

        // 3. revision conflict checking on Plan status update
        $conflictRes = $repo->updatePlanStatus(
            $planId,
            PlanStatus::COMPLETED,
            new \Elqora\Dgp\Runtime\RuntimeWriteOptions(expectedRevision: 1)
        );
        $this->assertTrue($conflictRes->isFailure());
        $this->assertEquals('runtime_revision_conflict', $conflictRes->error()?->code);

        // Verify status remains CANCELLED
        $fetchedPlan2 = $repo->findPlan(123, new PlanReference(id: $planId))->value();
        $this->assertNotNull($fetchedPlan2);
        /** @var Plan $fetchedPlan2 */
        $this->assertEquals(PlanStatus::CANCELLED, $fetchedPlan2->status);

        // 4. Seed startResult & check initial status
        $startResult = new StartResult(null, 'start-1', [], [], null, [], $planId, $planKey, 0);
        $savedStart = $runtimeRepo->seedStartResult(HandlerReference::fromKey('jap'), $planId, $startResult)->value();
        $this->assertNotNull($savedStart);
        $this->assertEquals(StartResultStatus::RUNNING, $savedStart->status);
        $this->assertEquals(1, $savedStart->revision);

        $startId = $savedStart->id;
        $this->assertNotNull($startId);

        // 5. Update startResult status & verify incremented revision
        $updateStartRes = $repo->updateStartResultStatus($startId, StartResultStatus::COMPLETED);
        $this->assertTrue($updateStartRes->isSuccess());

        $fetchedStart = $repo->findStartResult(123, new StartResultReference(id: $startId))->value();
        $this->assertNotNull($fetchedStart);
        /** @var StartResult $fetchedStart */
        $this->assertEquals(StartResultStatus::COMPLETED, $fetchedStart->status);
        $this->assertEquals(2, $fetchedStart->revision);

        // 6. revision conflict checking on StartResult status update
        $conflictStartRes = $repo->updateStartResultStatus(
            $startId,
            StartResultStatus::FAILED,
            new \Elqora\Dgp\Runtime\RuntimeWriteOptions(expectedRevision: 1)
        );
        $this->assertTrue($conflictStartRes->isFailure());
        $this->assertEquals('runtime_revision_conflict', $conflictStartRes->error()?->code);
    }

    public function testAppendOnlyDeliveriesAndProgressSegmentOperations(): void
    {
        $handler = HandlerReference::fromKey('jap');
        $runtimeRepository = new MockRuntimeRepository();
        Dgp::registerRuntimeRepository($runtimeRepository);
        Dgp::registerDeliveriesRepository(new MockDeliveriesRepository($runtimeRepository->store));
        Dgp::registerDeliveryProgressRepository(new MockDeliveryProgressRepository($runtimeRepository->store));

        // 1. Seed order and plan to support deliveries parent relationship
        $plan = new Plan(
            id: null,
            key: 'test-plan-keys',
            state: [],
            deliveries: []
        );
        $savedPlan = $runtimeRepository->seedPlan($handler, 123, $plan)->value();
        $this->assertNotNull($savedPlan);
        $this->assertNotNull($savedPlan->id);

        $deliveries = Dgp::deliveriesRepository($handler);
        $this->assertNotNull($deliveries);

        // 2. Add single delivery
        $newDel = new InitializationDelivery(
            id: null,
            key: 'init-probe-1',
            status: DeliveryStatus::PENDING,
            label: 'Probe account validation',
            progress: null,
            planId: $savedPlan->id
        );
        
        $addedRes = $deliveries->addDelivery($newDel);
        $this->assertTrue($addedRes->isSuccess());
        /** @var InitializationDelivery $addedDel */
        $addedDel = $addedRes->value();
        $this->assertNotNull($addedDel->id);
        $this->assertEquals('init-probe-1', $addedDel->key);
        $this->assertTrue($addedDel->isPublic);

        // Find delivery by ID
        $found = $deliveries->findDelivery(new DeliveryReference(id: $addedDel->id))->value();
        $this->assertNotNull($found);
        $this->assertEquals('init-probe-1', $found->key);

        // 3. Add segment to delivery
        $segment = new DeliveryProgressSegment(
            key: 'probe-probe',
            progress: 0.5,
            label: 'Initial probe',
            status: 'processing',
            sequence: 1,
            isPublic: false
        );

        $addSegRes = $deliveries->addSegment(new DeliveryReference(id: $addedDel->id), $segment);
        $this->assertTrue($addSegRes->isSuccess());
        $this->assertEquals('probe-probe', $addSegRes->value()->key);
        $this->assertFalse($addSegRes->value()->isPublic);

        // Find delivery and assert segment is added
        $foundWithSeg = $deliveries->findDelivery(new DeliveryReference(id: $addedDel->id))->value();
        $this->assertNotNull($foundWithSeg);
        $this->assertNotNull($foundWithSeg->progress);
        $this->assertCount(1, $foundWithSeg->progress->segments);
        $this->assertFalse($foundWithSeg->progress->segments[0]->isPublic);

        // 4. Update delivery status & visibility
        $updateStatusRes = $deliveries->updateDeliveryStatus(new DeliveryReference(id: $addedDel->id), DeliveryStatus::FAILED);
        $this->assertTrue($updateStatusRes->isSuccess());

        $updateVisRes = $deliveries->updateDeliveryVisibility(new DeliveryReference(id: $addedDel->id), false);
        $this->assertTrue($updateVisRes->isSuccess());

        $foundUpdated = $deliveries->findDelivery(new DeliveryReference(id: $addedDel->id))->value();
        $this->assertNotNull($foundUpdated);
        /** @var \Elqora\Dgp\Deliveries\Delivery $foundUpdated */
        $this->assertEquals(DeliveryStatus::FAILED, $foundUpdated->status);
        $this->assertFalse($foundUpdated->isPublic);

        // 5. Update segment status & visibility
        $upSegStatusRes = $deliveries->updateSegmentStatus(new DeliveryReference(id: $addedDel->id), 'probe-probe', 'failed');
        $this->assertTrue($upSegStatusRes->isSuccess());

        $upSegVisRes = $deliveries->updateSegmentVisibility(new DeliveryReference(id: $addedDel->id), 'probe-probe', true);
        $this->assertTrue($upSegVisRes->isSuccess());

        $foundUpdatedSeg = $deliveries->findDelivery(new DeliveryReference(id: $addedDel->id))->value();
        $this->assertNotNull($foundUpdatedSeg);
        /** @var \Elqora\Dgp\Deliveries\Delivery $foundUpdatedSeg */
        $this->assertNotNull($foundUpdatedSeg->progress);
        $this->assertEquals('failed', $foundUpdatedSeg->progress->segments[0]->status);
        $this->assertTrue($foundUpdatedSeg->progress->segments[0]->isPublic);

        // 6. Record progress for delivery segment
        $progressHandler = Dgp::deliveryProgressRepository($handler);
        $this->assertNotNull($progressHandler);

        $record = new DeliveryProgressRecord(
            id: null,
            orderId: 123,
            delivery: new DeliveryReference(id: $addedDel->id),
            stage: DeliveryStage::INITIALIZATION,
            progress: new DeliveryProgress(current: 50, target: 100, percent: 50),
            recordedAt: '2026-07-15T11:00:00Z',
            source: ProgressSource::SYNCHRONIZATION
        );

        $recRes = $progressHandler->recordSegmentProgress(new DeliveryReference(id: $addedDel->id), 'probe-probe', $record);
        $this->assertTrue($recRes->isSuccess());
        $this->assertEquals('probe-probe', $recRes->value()->segmentKey);

        // Query timeline for segment
        $timelineRes = $progressHandler->timeline(
            new DeliveryReference(id: $addedDel->id),
            new ProgressTimelineQuery(segmentKey: 'probe-probe')
        );
        $this->assertTrue($timelineRes->isSuccess());
        $this->assertCount(1, $timelineRes->value());
        $this->assertEquals('probe-probe', $timelineRes->value()[0]->segmentKey);

        // Query timeline for non-existent segment
        $timelineRes2 = $progressHandler->timeline(
            new DeliveryReference(id: $addedDel->id),
            new ProgressTimelineQuery(segmentKey: 'non-existent')
        );
        $this->assertCount(0, $timelineRes2->value());
    }
}
