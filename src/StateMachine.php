<?php

namespace think\workflow;

use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\Transition;
use think\helper\Str;

class StateMachine
{
    public $name;
    public $places      = [];
    public $transitions = [];
    public $initial     = null;

    public function trigger($name, $transition, $model)
    {
        if (method_exists($this, $name)) {
            $this->{$name}($transition, $model);
        }

        $method = $name . Str::studly($transition);

        if (method_exists($this, $method)) {
            $this->{$method}($model);
        }
    }

    public function buildDefinition()
    {
        $builder = new DefinitionBuilder();

        $builder->addPlaces($this->places);

        foreach ($this->transitions as $name => $transition) {
            foreach ((array) $transition[0] as $from) {
                foreach ((array) $transition[1] as $to) {
                    $builder->addTransition(new Transition($name, $from, $to));
                }
            }
        }

        $builder->setInitialPlaces($this->initial);

        return $builder->build();
    }

    public static function makeWithAnnotation(annotation\StateMachine $annotation)
    {
        $stateMachine = new self();

        $stateMachine->name        = $annotation->name;
        $stateMachine->places      = $annotation->places;
        $stateMachine->transitions = $annotation->transitions;
        $stateMachine->initial     = $annotation->initial;

        return $stateMachine;
    }
}
