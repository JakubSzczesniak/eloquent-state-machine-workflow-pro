<?php

namespace JakubSzczesniak\LaravelEloquentStateMachines\Tests\TestModels;

use JakubSzczesniak\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\StatusWithAfterTransitionHookStateMachine;
use JakubSzczesniak\LaravelEloquentStateMachines\Traits\HasStateMachines;
use Illuminate\Database\Eloquent\Model;

class SalesOrderWithAfterTransitionHook extends Model
{
    use HasStateMachines;

    protected $table = 'sales_orders';

    protected $guarded = [];

    public $stateMachines = [
        'status' => StatusWithAfterTransitionHookStateMachine::class,
    ];
}
