<?php

namespace JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestModels;

use JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestStateMachines\SalesOrders\FulfillmentStateMachine;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestStateMachines\SalesOrders\StatusStateMachine;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Traits\HasStateMachines;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasStateMachines;

    protected $guarded = [];

    public $stateMachines = [
        'status' => StatusStateMachine::class,
        'fulfillment' => FulfillmentStateMachine::class,
    ];
}
