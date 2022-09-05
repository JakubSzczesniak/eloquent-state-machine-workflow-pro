<?php

namespace JakubSzczesniak\LaravelEloquentStateMachines\Tests\TestModels;

use JakubSzczesniak\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\FulfillmentStateMachine;
use JakubSzczesniak\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders\StatusStateMachine;
use JakubSzczesniak\LaravelEloquentStateMachines\Traits\HasStateMachines;
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
