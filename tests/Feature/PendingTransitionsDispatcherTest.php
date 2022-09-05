<?php

namespace JakubSzczesniak\LaravelEloquentStateMachines\Tests\Feature;

use JakubSzczesniak\LaravelEloquentStateMachines\Exceptions\TransitionNotAllowedException;
use JakubSzczesniak\LaravelEloquentStateMachines\Jobs\PendingTransitionExecutor;
use JakubSzczesniak\LaravelEloquentStateMachines\Jobs\PendingTransitionsDispatcher;
use JakubSzczesniak\LaravelEloquentStateMachines\Models\PendingTransition;
use JakubSzczesniak\LaravelEloquentStateMachines\Tests\TestJobs\StartSalesOrderFulfillmentJob;
use JakubSzczesniak\LaravelEloquentStateMachines\Tests\TestCase;
use JakubSzczesniak\LaravelEloquentStateMachines\Tests\TestModels\SalesOrder;
use JakubSzczesniak\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\FulfillmentStateMachine;
use JakubSzczesniak\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\StatusStateMachine;
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
