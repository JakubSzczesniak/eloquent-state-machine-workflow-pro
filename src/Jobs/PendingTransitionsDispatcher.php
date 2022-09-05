<?php


namespace JakubSzczesniak\LaravelEloquentStateMachines\Jobs;


use JakubSzczesniak\LaravelEloquentStateMachines\Models\PendingTransition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PendingTransitionsDispatcher implements ShouldQueue
{
    use InteractsWithQueue, Queueable, Dispatchable, SerializesModels;

    public function handle()
    {
        PendingTransition::with(['model'])
            ->notApplied()
            ->onScheduleOrOverdue()
            ->get()
            ->each(function (PendingTransition $pendingTransition) {
                PendingTransitionExecutor::dispatch($pendingTransition);

                $pendingTransition->applied_at = now();
                $pendingTransition->save();
            });
    }
}
