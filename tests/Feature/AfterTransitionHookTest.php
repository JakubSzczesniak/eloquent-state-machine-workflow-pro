<?php

namespace JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\Feature;

use JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestCase;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestJobs\AfterTransitionJob;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestModels\SalesOrderWithAfterTransitionHook;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestModels\SalesOrderWithBeforeTransitionHook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Queue;

class AfterTransitionHookTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /** @test */
    public function should_call_after_transition_hooks()
    {
        //Arrange
        Queue::fake();

        $salesOrder = SalesOrderWithAfterTransitionHook::create([
            'total' => 100,
            'notes' => 'before',
        ]);

        //Act
        $salesOrder->status()->transitionTo('approved');

        //Assert
        $salesOrder->refresh();

        $this->assertEquals(200, $salesOrder->total);
        $this->assertEquals('after', $salesOrder->notes);

        Queue::assertPushed(AfterTransitionJob::class);
    }

    /** @test */
    public function should_not_call_after_transition_hooks_if_not_defined()
    {
        //Arrange
        Queue::fake();

        $salesOrder = SalesOrderWithAfterTransitionHook::create([
            'status' => 'approved'
        ]);

        $this->assertNull($salesOrder->total);
        $this->assertNull($salesOrder->notes);

        //Act
        $salesOrder->status()->transitionTo('processed');

        //Assert
        $salesOrder->refresh();

        $this->assertNull($salesOrder->total);
        $this->assertNull($salesOrder->notes);

        Queue::assertNotPushed(AfterTransitionJob::class);
    }
}
