<?php


namespace JakubSzczesniak\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders;


use JakubSzczesniak\LaravelEloquentStateMachines\StateMachines\StateMachine;

class StatusStateMachine extends StateMachine
{
    public function recordHistory(): bool
    {
        return true;
    }

    public function transitions(): array
    {
        return [
            'pending' => ['approved'],
            'approved' => ['processed'],
        ];
    }

    public function defaultState(): ?string
    {
        return 'pending';
    }
}
