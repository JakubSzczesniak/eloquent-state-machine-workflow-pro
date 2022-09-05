<?php

namespace JakubSzczesniak\LaravelEloquentStateMachines\Tests\TestModels;

use JakubSzczesniak\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\StatusWithBeforeTransitionHookStateMachine;
use JakubSzczesniak\LaravelEloquentStateMachines\Traits\HasStateMachines;
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
