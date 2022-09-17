<?php

namespace JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\Feature;

use JakubSzczesniak\EloquentStateMachineWorkflowPro\Exceptions\TransitionNotAllowedException;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Jobs\PendingTransitionExecutor;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Jobs\PendingTransitionsDispatcher;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Models\PendingTransition;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestJobs\StartSalesOrderFulfillmentJob;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestCase;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestModels\SalesOrder;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestStateMachines\SalesOrders\FulfillmentStateMachine;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestStateMachines\SalesOrders\StatusStateMachine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Validation\ValidationException;
use Queue;

class PendingTransitionsDispatcherTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    /** @test */
    public function should_dispatch_pending_transition()
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        $pendingTransition =
            $salesOrder->status()->postponeTransitionTo('approved', Carbon::now()->subSecond());

        $this->assertTrue($salesOrder->status()->hasPendingTransitions());

        //Act
        PendingTransitionsDispatcher::dispatchNow();

        //Assert
        $salesOrder->refresh();

        $this->assertFalse($salesOrder->status()->hasPendingTransitions());

        Queue::assertPushed(PendingTransitionExecutor::class, function ($job) use ($pendingTransition) {
            $this->assertEquals($pendingTransition->id, $job->pendingTransition->id);
            return true;
        });
    }

    /** @test */
    public function should_not_dispatch_future_pending_transitions()
    {
        //Arrange
        $salesOrder = factory(SalesOrder::class)->create();

        $salesOrder->status()->postponeTransitionTo('approved', Carbon::tomorrow());

        $this->assertTrue($salesOrder->status()->hasPendingTransitions());

        //Act
        PendingTransitionsDispatcher::dispatchNow();

        //Assert
        $salesOrder->refresh();

        $this->assertTrue($salesOrder->status()->hasPendingTransitions());

        Queue::assertNothingPushed();
    }
}
