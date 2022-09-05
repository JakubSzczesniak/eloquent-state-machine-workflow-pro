<?php


namespace JakubSzczesniak\LaravelEloquentStateMachines\Tests\TestStateMachines\SalesOrders;


use JakubSzczesniak\LaravelEloquentStateMachines\StateMachines\StateMachine;
use JakubSzczesniak\LaravelEloquentStateMachines\Tests\TestJobs\BeforeTransitionJob;

class StatusWithBeforeTransitionHookStateMachine extends StateMachine
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

    public function beforeTransitionHooks(): array
    {
        return [
            'pending' => [
                function($to, $model) {
                    $model->total = 100;
                },
                function($to, $model) {
                    $model->notes = 'Notes updated';
                },
                function ($to, $model) {
                    BeforeTransitionJob::dispatch();
                }
            ]
        ];
    }
}
