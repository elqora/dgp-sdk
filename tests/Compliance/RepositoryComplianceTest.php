<?php

namespace Elqora\Dgp\Tests\Compliance;

use PHPUnit\Framework\TestCase;
use Elqora\Chart\Charts\Chart;
use Elqora\Chart\Data\TabularData;
use Elqora\Chart\Enums\ChartType;
use Elqora\Chart\Enums\ValueType;
use Elqora\Chart\Series\Series;
use Elqora\Dgp\Configuration\Dgp;
use Elqora\Dgp\Catalog\Services\HandlerService;
use Elqora\Dgp\Errors\DgpConfigurationException;
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
use Elqora\Dgp\Runtime\StartResult;
use Elqora\Dgp\Runtime\RuntimeWriteOptions;
use Elqora\Dgp\Runtime\Queries\PlanQuery;
use Elqora\Dgp\Runtime\Queries\StartResultQuery;
use Elqora\Dgp\Runtime\Queries\DeliveryQuery;
use Elqora\Dgp\Deliveries\InitializationDelivery;
use Elqora\Dgp\Deliveries\FulfillmentDelivery;
use Elqora\Dgp\Deliveries\DeliveryStatus;
use Elqora\Dgp\Actions\Contracts\NextAction;
use Elqora\Dgp\Actions\RedirectAction;
use Elqora\Dgp\Tests\Fixtures\Repository\MockRuntimeRepository;
use Elqora\Dgp\Tests\Fixtures\Repository\MockHandlerRuntimeRepository;
use Elqora\Dgp\Tests\Fixtures\Repository\MockServicesRepository;
use Elqora\Dgp\Tests\Fixtures\Repository\MockHandlerServicesRepository;
use Elqora\Dgp\Tests\Fixtures\Repository\MockDeliveriesRepository;
use Elqora\Dgp\Tests\Fixtures\Repository\MockInsightsRepository;
use Elqora\Dgp\Tests\Fixtures\Repository\MockHandlerInsightsRepository;

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
        foreach (['runtimeRepository', 'servicesRepository', 'deliveriesRepository', 'insightsRepository'] as $property) {
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

    public function testImmutableResolutionScopingAndIsolation(): void
    {
        Dgp::registerRuntimeRepository(new MockRuntimeRepository());

        $japRepo = Dgp::runtimeRepository(HandlerReference::fromKey('jap'));
        $smmRepo = Dgp::runtimeRepository(HandlerReference::fromKey('smm'));

        // Save plan in jap handler
        $plan = new Plan(null, 'main-plan', ['step' => 1]);
        $saveRes = $japRepo->savePlan(123, $plan);
        $this->assertTrue($saveRes->isSuccess());
        $savedJapPlan = $saveRes->value();

        // 1. Isolation: smmRepo cannot find the jap plan (returns success with null, avoiding leakage)
        $findRes = $smmRepo->findPlan(123, new PlanReference(id: $savedJapPlan->id));
        $this->assertTrue($findRes->isSuccess());
        $this->assertNull($findRes->value());

        // 2. Mismatched order: japRepo cannot find the plan under another order
        $findResOtherOrder = $japRepo->findPlan(999, new PlanReference(id: $savedJapPlan->id));
        $this->assertTrue($findResOtherOrder->isSuccess());
        $this->assertNull($findResOtherOrder->value());

        // 3. Mutation isolation: smmRepo cannot append deliveries to jap's plan
        $del = new InitializationDelivery(null, 'del-1', DeliveryStatus::PENDING, 'Desc', null, $savedJapPlan->id);
        $mutationRes = $smmRepo->saveDeliveries(123, [$del]);
        $this->assertTrue($mutationRes->isFailure());
        $error = $mutationRes->error();
        $this->assertNotNull($error);
        $this->assertEquals('parent_not_found', $error->code);
    }

    public function testGraphSavesReconstructDTOsWithPersistedIDs(): void
    {
        Dgp::registerRuntimeRepository(new MockRuntimeRepository());
        $repo = Dgp::runtimeRepository(HandlerReference::fromKey('jap'));

        // Save a plan with a nested initialization delivery
        $del = new InitializationDelivery(null, 'init-del-1', DeliveryStatus::PENDING, 'Verify account');
        $plan = new Plan(null, 'auth-flow', ['auth' => 'oauth'], [$del]);

        $res = $repo->savePlan(123, $plan);
        $this->assertTrue($res->isSuccess());
        $persisted = $res->value();

        $this->assertNotNull($persisted);
        // Verify newly reconstructed DTO graph (input remains unmodified)
        $this->assertNull($plan->id);
        $this->assertNull($plan->deliveries[0]->id);

        $this->assertNotNull($persisted->id);
        $this->assertEquals(1, $persisted->revision);
        $this->assertCount(1, $persisted->deliveries);
        $this->assertNotNull($persisted->deliveries[0]->id);
        $this->assertEquals($persisted->id, $persisted->deliveries[0]->planId);
        $this->assertNull($persisted->deliveries[0]->startId);
    }

    public function testStartResultInvariantsAndResolution(): void
    {
        Dgp::registerRuntimeRepository(new MockRuntimeRepository());
        $repo = Dgp::runtimeRepository(HandlerReference::fromKey('jap'));

        // 1. Throws parent_plan_not_found if referenced parent plan does not exist
        $start = new StartResult(null, 'start-1', ['run' => true]);
        $res = $repo->saveStartResult(123, $start);
        $this->assertTrue($res->isFailure());
        $error = $res->error();
        $this->assertNotNull($error);
        $this->assertEquals('parent_plan_not_found', $error->code);

        // 2. Throws parent_plan_not_found if planId is missing from repo lookup
        $startWithPlan = new StartResult(null, 'start-1', ['run' => true], [], null, [], 'missing-plan-id');
        $res2 = $repo->saveStartResult(9999, $startWithPlan);
        $this->assertTrue($res2->isFailure());
        $error2 = $res2->error();
        $this->assertNotNull($error2);
        $this->assertEquals('parent_plan_not_found', $error2->code);

        // Save a valid plan first
        $plan = new Plan(null, 'auth-flow', ['step' => 'init']);
        $planSaved = $repo->savePlan(123, $plan)->value();
        $this->assertNotNull($planSaved);
        $this->assertNotNull($planSaved->id);
        /** @var string|int $savedPlanId */
        $savedPlanId = $planSaved->id;

        // 3. Succeeds if plan is correct and resolves relationship
        $startValid = new StartResult(null, 'start-1', ['run' => true], [], null, [], $savedPlanId);
        $res3 = $repo->saveStartResult($savedPlanId, $startValid);
        $this->assertTrue($res3->isSuccess());
        $persistedStart = $res3->value();
        $this->assertNotNull($persistedStart);
        $this->assertEquals($savedPlanId, $persistedStart->planId);
        $this->assertEquals($planSaved->key, $persistedStart->planKey);

        // 4. Throws parent_plan_reference_mismatch if planId and planKey resolve inconsistently
        $startInconsistent = new StartResult(null, 'start-2', ['run' => true], [], null, [], $savedPlanId, 'mismatched-key');
        $res4 = $repo->saveStartResult($savedPlanId, $startInconsistent);
        $this->assertTrue($res4->isFailure());
        $error4 = $res4->error();
        $this->assertNotNull($error4);
        $this->assertEquals('parent_plan_reference_mismatch', $error4->code);
    }

    public function testExpectedRevisionOnPlanAndStartResultSaves(): void
    {
        Dgp::registerRuntimeRepository(new MockRuntimeRepository());
        $repo = Dgp::runtimeRepository(HandlerReference::fromKey('jap'));

        $plan = new Plan(null, 'main-plan', []);
        $saved = $repo->savePlan(123, $plan)->value();
        $this->assertNotNull($saved);
        $this->assertEquals(1, $saved->revision);

        // Save with mismatched expectedRevision fails
        $optionsFail = new RuntimeWriteOptions(expectedRevision: 99);
        $resFail = $repo->savePlan(123, $saved, $optionsFail);
        $this->assertTrue($resFail->isFailure());
        $error = $resFail->error();
        $this->assertNotNull($error);
        $this->assertEquals('runtime_revision_conflict', $error->code);

        // Save with correct expectedRevision succeeds and increments revision
        $optionsSuccess = new RuntimeWriteOptions(expectedRevision: 1);
        $resSuccess = $repo->savePlan(123, $saved, $optionsSuccess);
        $this->assertTrue($resSuccess->isSuccess());
        $val = $resSuccess->value();
        $this->assertNotNull($val);
        $this->assertEquals(2, $val->revision);
    }

    public function testSaveDeliveriesRejectsExpectedRevision(): void
    {
        Dgp::registerRuntimeRepository(new MockRuntimeRepository());
        $repo = Dgp::runtimeRepository(HandlerReference::fromKey('jap'));

        $plan = $repo->savePlan(123, new Plan(null, 'main-plan', []))->value();
        $this->assertNotNull($plan);
        $del = new InitializationDelivery(null, 'init-del-1', DeliveryStatus::PENDING, 'Verify', null, $plan->id);

        // expectedRevision provided to saveDeliveries fails and does not mutate
        $options = new RuntimeWriteOptions(expectedRevision: 1);
        $res = $repo->saveDeliveries(123, [$del], $options);

        $this->assertTrue($res->isFailure());
        $error = $res->error();
        $this->assertNotNull($error);
        $this->assertEquals('delivery_revision_not_supported', $error->code);

        // Verify delivery was not persisted
        $listRes = $repo->deliveriesForPlan(123, new PlanReference(id: $plan->id));
        $this->assertCount(0, $listRes->value());
    }

    public function testIdempotencyMatchingScopes(): void
    {
        Dgp::registerRuntimeRepository(new MockRuntimeRepository());
        $repo = Dgp::runtimeRepository(HandlerReference::fromKey('jap'));

        $plan = new Plan(null, 'main-plan', []);
        $options = new RuntimeWriteOptions(operationKey: 'unique-op-key-1');

        // First save executes successfully
        $res1 = $repo->savePlan(123, $plan, $options);
        $this->assertTrue($res1->isSuccess());
        $saved1 = $res1->value();
        $this->assertNotNull($saved1);

        // Second duplicate save returns the identical cached result (reconstructed DTO with populated ID/revision)
        $res2 = $repo->savePlan(123, $plan, $options);
        $this->assertTrue($res2->isSuccess());
        $val2 = $res2->value();
        $this->assertNotNull($val2);
        $this->assertEquals($saved1->id, $val2->id);
        $this->assertEquals($saved1->revision, $val2->revision);

        // Duplicate write using updateId
        $optionsUpdateId = new RuntimeWriteOptions(updateId: 'update-id-123');
        $res3 = $repo->savePlan(123, $plan, $optionsUpdateId);
        $this->assertTrue($res3->isSuccess());
        $saved3 = $res3->value();
        $this->assertNotNull($saved3);

        $res4 = $repo->savePlan(123, $plan, $optionsUpdateId);
        $this->assertTrue($res4->isSuccess());
        $val4 = $res4->value();
        $this->assertNotNull($val4);
        $this->assertEquals($saved3->id, $val4->id);
    }

    public function testOrderRuntimeViewSelections(): void
    {
        Dgp::registerRuntimeRepository(new MockRuntimeRepository());
        /** @var MockHandlerRuntimeRepository $repo */
        $repo = Dgp::runtimeRepository(HandlerReference::fromKey('jap'));

        $plan1 = $repo->savePlan(123, new Plan(null, 'plan-1', []))->value();
        $this->assertNotNull($plan1);
        $plan2 = $repo->savePlan(123, new Plan(null, 'plan-2', []))->value();
        $this->assertNotNull($plan2);

        $this->assertNotNull($plan1->id);
        /** @var string|int $p1Id */
        $p1Id = $plan1->id;

        $startResult = $repo->saveStartResult($p1Id, new StartResult(null, 'start-1', [], [], null, [], $p1Id))->value();
        $this->assertNotNull($startResult);

        // Configure current refs explicitly in mock store
        $repo->setCurrentPlan(123, $plan2->id);
        $repo->setCurrentStartResult(123, $startResult->id);

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
        Dgp::registerRuntimeRepository(new MockRuntimeRepository());
        /** @var MockHandlerRuntimeRepository $repo */
        $repo = Dgp::runtimeRepository(HandlerReference::fromKey('jap'));

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
                    nextAction: $redirectAction
                )
            ]
        );

        $savedPlan = $repo->savePlan(123, $plan)->value();
        $this->assertNotNull($savedPlan);
        $this->assertCount(1, $savedPlan->deliveries);
        $this->assertNotNull($savedPlan->deliveries[0]->nextAction);
        $action1 = $savedPlan->deliveries[0]->nextAction;
        $this->assertInstanceOf(RedirectAction::class, $action1);
        $this->assertEquals('https://gateway.example/download', $action1->url);

        // 2. Fetch deliveries from repo
        $fetched = $repo->deliveriesForPlan(123, new PlanReference(id: $savedPlan->id))->value();
        $this->assertCount(1, $fetched);
        $this->assertNotNull($fetched[0]->nextAction);
        $action2 = $fetched[0]->nextAction;
        $this->assertInstanceOf(RedirectAction::class, $action2);
        $this->assertEquals('https://gateway.example/download', $action2->url);

        // 3. Save deliveries directly via saveDeliveries()
        $deliveryUpdate = new InitializationDelivery(
            id: $fetched[0]->id,
            key: 'init-del',
            status: DeliveryStatus::COMPLETED,
            label: 'Init Doc Finished',
            progress: null,
            planId: $savedPlan->id,
            startId: null,
            nextAction: null // Clear nextAction
        );

        $updatedList = $repo->saveDeliveries(123, [$deliveryUpdate])->value();
        $this->assertCount(1, $updatedList);
        $this->assertNull($updatedList[0]->nextAction);

        // Fetch again to verify cleared in repo store
        $fetchedUpdated = $repo->deliveriesForPlan(123, new PlanReference(id: $savedPlan->id))->value();
        $this->assertNull($fetchedUpdated[0]->nextAction);
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
        $this->assertEquals('unlocked', $state['state']);
        $this->assertEquals('Delivery metrics recovered.', $state['reason']);
        $this->assertNull($smmRepo->serviceState(103));

        $emptyReason = $japRepo->disable(104, '   ');
        $this->assertTrue($emptyReason->isFailure());
        $error = $emptyReason->error();
        $this->assertNotNull($error);
        $this->assertEquals('service_state_reason_required', $error->code);
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
        Dgp::registerRuntimeRepository($runtimeRepository);
        Dgp::registerDeliveriesRepository(new MockDeliveriesRepository($runtimeRepository->store));

        $runtime = Dgp::runtimeRepository(HandlerReference::fromKey('jap'));
        $deliveries = Dgp::deliveriesRepository(HandlerReference::fromKey('jap'));
        $otherDeliveries = Dgp::deliveriesRepository(HandlerReference::fromKey('smm'));

        $plan = $runtime->savePlan(
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
}
