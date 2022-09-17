<?php


namespace JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestStateMachines\SalesOrders;


use JakubSzczesniak\EloquentStateMachineWorkflowPro\StateMachines\StateMachine;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Tests\TestJobs\AfterTransitionJob;

class StatusWithAfterTransitionHookStateMachine extends StateMachine
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

    public function afterTransitionHooks(): array
    {
        return [
            'approved' => [
                function($from, $model) {
                    $model->total = 200;
                    $model->save();
                },
                function($from, $model) {
                    $model->notes = 'after';
                    $model->save();
                },
                function ($from, $model) {
                    AfterTransitionJob::dispatch();
                },
            ]
        ];
    }
}
