<?php

namespace Elqora\Dgp\Tests\Compliance;

use PHPUnit\Framework\TestCase;
use Elqora\Dgp\Configuration\Dgp;
use Elqora\Dgp\Errors\DgpConfigurationException;
use Elqora\Dgp\Runtime\References\HandlerReference;
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
use Elqora\Dgp\Tests\Fixtures\Repository\MockRuntimeRepository;
use Elqora\Dgp\Tests\Fixtures\Repository\MockHandlerRuntimeRepository;

class RepositoryComplianceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset registered repository
        $ref = new \ReflectionClass(Dgp::class);
        $prop = $ref->getProperty('runtimeRepository');
        $prop->setAccessible(true);
        $prop->setValue(null);
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
}
