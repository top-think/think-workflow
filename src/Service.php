<?php

namespace think\workflow;

use ReflectionClass;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use think\helper\Str;
use think\ide\ModelGenerator;
use think\Model;
use think\workflow\annotation\StateMachine;

class Service extends \think\Service
{
    protected $detected = [];

    public function boot()
    {
        /** @var Registry $registry */
        $registry = $this->app->make(Registry::class);

        Model::maker(function (Model $model) use ($registry) {
            $className = get_class($model);
            if (!isset($this->detected[$className])) {
                $attributes = (new ReflectionClass($model))->getAttributes(StateMachine::class);

                foreach ($attributes as $attribute) {

                    $builder = new DefinitionBuilder();
                    $map     = null;

                    /** @var StateMachine $annotation */
                    $annotation = $attribute->newInstance();

                    if (isset($annotation->places[0])) {
                        $places = $annotation->places;
                    } else {
                        $places = array_keys($annotation->places);
                        $map    = $annotation->places;
                    }
                    $builder->addPlaces($places);
                    foreach ($annotation->transitions as $name => $transition) {
                        foreach ((array) $transition[0] as $from) {
                            foreach ((array) $transition[1] as $to) {
                                $builder->addTransition(new Transition($name, $from, $to));
                            }
                        }
                    }
                    $builder->setInitialPlaces($annotation->initial);
                    $definition   = $builder->build();
                    $marking      = new ModelMarkingStore($annotation->name, $map);
                    $stateMachine = new Workflow($definition, $marking, null, get_class($model) . "@" . $annotation->name);

                    $registry->addWorkflow($stateMachine, new InstanceOfSupportStrategy(get_class($model)));

                    foreach ($annotation->transitions as $name => $transition) {
                        call_user_func([$model, 'macro'], $name, function ($context = []) use ($name, $stateMachine) {
                            $stateMachine->apply($this, $name, $context);
                        });

                        call_user_func([$model, 'macro'], 'can' . Str::studly($name), function () use ($name, $stateMachine) {
                            return $stateMachine->can($this, $name);
                        });
                    }
                }
            }
            $this->detected[$className] = true;
        });

        $this->app->event->listen(ModelGenerator::class, function (ModelGenerator $generator) {

            $attributes = $generator->getReflection()->getAttributes(StateMachine::class);

            foreach ($attributes as $attribute) {
                $annotation = $attribute->newInstance();

                foreach ($annotation->transitions as $name => $transition) {
                    $generator->addMethod($name, 'void', ['array $context = []'], false);
                    $generator->addMethod('can' . Str::studly($name), 'boolean', [], false);
                }
            }
        });
    }
}
