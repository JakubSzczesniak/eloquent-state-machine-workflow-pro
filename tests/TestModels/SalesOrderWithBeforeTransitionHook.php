<?php

namespace JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestModels;

use JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestStateMachines\SalesOrders\StatusWithBeforeTransitionHookStateMachine;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Traits\HasStateMachines;
use Illuminate\Database\Eloquent\Model;

class SalesOrderWithBeforeTransitionHook extends Model
{
    use HasStateMachines;

    protected $table = 'sales_orders';

    protected $guarded = [];

    public $stateMachines = [
        'status' => StatusWithBeforeTransitionHookStateMachine::class,
    ];
}
