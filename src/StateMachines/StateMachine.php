<?php

namespace JakubSzczesniak\EloquentStateMachineWorkflowPro\StateMachines;

use BackedEnum;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Exceptions\TransitionNotAllowedException;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Models\PendingTransition;
use JakubSzczesniak\EloquentStateMachineWorkflowPro\Models\StateHistory;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

abstract class StateMachine
{
    public $field;
    public $model;

    public function __construct($field, &$model)
    {
        $this->field = $field;

        $this->model = $model;
    }

    public function currentState()
    {
        $field = $this->field;

        return $this->model->$field;
    }

    public function history(): Collection
    {
        return $this->model->stateHistory->where('field', $this->field);
    }

    public function was($state)
    {
        $state = $this->castBinding($state);

        return $this->history()->contains('to', $state);
    }

    public function timesWas($state)
    {
        $state = $this->castBinding($state);

        return $this->history()->where('to', $state)->count();
    }

    public function whenWas($state) : ?Carbon
    {
        $state = $this->castBinding($state);

        $stateHistory = $this->snapshotWhen($state);

        if ($stateHistory === null) {
            return null;
        }

        return $stateHistory->created_at;
    }

    public function snapshotWhen($state) : ?StateHistory
    {
        $state = $this->castBinding($state);

        return $this->history()->where('to', $state)->sortBy('id')->last();
    }

    public function snapshotsWhen($state) : Collection
    {
        $state = $this->castBinding($state);

        return $this->history()->where('to', $state);
    }

    public function canBe($from, $to)
    {
        $from = $this->castBinding($from);
        $to = $this->castBinding($to);

        $availableTransitions = $this->transitions()[$from] ?? [];

        return collect($availableTransitions)->contains($to);
    }

    public function pendingTransitions()
    {
        return $this->model->pendingTransitions()->where('field', $this->field);
    }

    public function hasPendingTransitions()
    {
        return $this->pendingTransitions()->notApplied()->exists();
    }

    /**
     * @param $from
     * @param $to
     * @param array $customProperties
     * @param null|mixed $responsible
     * @throws TransitionNotAllowedException
     * @throws ValidationException
     */
    public function transitionTo($from, $to, $customProperties = [], $responsible = null)
    {
        $from = $this->castBinding($from);
        $to = $this->castBinding($to);

        if ($to === $this->currentState()) {
            return;
        }

        if (!$this->canBe($from, $to)) {
            throw new TransitionNotAllowedException();
        }

        $validator = $this->validatorForTransition($from, $to, $this->model);
        if ($validator !== null && $validator->fails()) {
            throw new ValidationException($validator);
        }

        $beforeTransitionHooks = $this->beforeTransitionHooks()[$from] ?? [];

        collect($beforeTransitionHooks)
            ->each(function ($callable) use ($to) {
                $callable($to, $this->model);
            });

        $field = $this->field;
        $this->model->$field = $to;

        $changedAttributes = $this->model->getChangedAttributes();

        $this->model->save();

        if ($this->recordHistory()) {
            $responsible = $responsible ?? auth()->user();

            $this->model->recordState($field, $from, $to, $customProperties, $responsible, $changedAttributes);
        }

        $afterTransitionHooks = $this->afterTransitionHooks()[$to] ?? [];

        collect($afterTransitionHooks)
            ->each(function ($callable) use ($from) {
                $callable($from, $this->model);
            });

        $this->cancelAllPendingTransitions();
    }

    /**
     * @param $from
     * @param $to
     * @param Carbon $when
     * @param array $customProperties
     * @param null $responsible
     * @return null|PendingTransition
     * @throws TransitionNotAllowedException
     */
    public function postponeTransitionTo($from, $to, Carbon $when, $customProperties = [], $responsible = null) : ?PendingTransition
    {
        $from = $this->castBinding($from);
        $to = $this->castBinding($to);

        if ($to === $this->currentState()) {
            return null;
        }

        if (!$this->canBe($from, $to)) {
            throw new TransitionNotAllowedException();
        }

        $responsible = $responsible ?? auth()->user();

        return $this->model->recordPendingTransition(
            $this->field,
            $from,
            $to,
            $when,
            $customProperties,
            $responsible
        );
    }

    public function cancelAllPendingTransitions()
    {
        $this->pendingTransitions()->delete();
    }

    abstract public function transitions() : array;

    abstract public function defaultState() : ?string;

    abstract public function recordHistory() : bool;

    public function validatorForTransition($from, $to, $model): ?Validator
    {
        return null;
    }

    public function afterTransitionHooks() : array
    {
        return [];
    }

    public function beforeTransitionHooks() : array {
        return [];
    }

    private function castBinding($value)
    {
        if (function_exists('enum_exists') && $value instanceof BackedEnum) {
            return $value->value;
        }

        return $value;
    }
}
