<?php

namespace think\workflow;

use Doctrine\Common\Annotations\Reader;
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
    protected $detect = [];

    public function boot(Reader $reader)
    {
        /** @var Registry $registry */
        $registry = $this->app->make(Registry::class);

        if ($this->app->bound(Reader::class)) {

            /** @var Reader $reader */
            $reader = $this->app->make(Reader::class);

            Model::maker(function (Model $model) use ($registry, $reader) {

                $className = get_class($model);
                if (!isset($this->detect[$className])) {
                    $annotations = $reader->getClassAnnotations(new ReflectionClass($model));

                    foreach ($annotations as $annotation) {
                        if ($annotation instanceof StateMachine) {
                            $builder = new DefinitionBuilder();
                            $map     = null;
                            if (isset($annotation->places[0])) {
                                $places = $annotation->places;
                            } else {
                                $places = array_keys($annotation->places);
                                $map    = $annotation->places;
                            }
                            $builder->addPlaces($places);
                            foreach ($annotation->transitions as $transition) {
                                foreach ((array) $transition->from as $from) {
                                    foreach ((array) $transition->to as $to) {
                                        $builder->addTransition(new Transition($transition->value, $from, $to));
                                    }
                                }
                            }
                            $builder->setInitialPlaces($annotation->initial);
                            $definition   = $builder->build();
                            $marking      = new ModelMarkingStore($annotation->value, $map);
                            $stateMachine = new Workflow($definition, $marking, null, get_class($model) . "@" . $annotation->value);

                            $registry->addWorkflow($stateMachine, new InstanceOfSupportStrategy(get_class($model)));

                            foreach ($annotation->transitions as $transition) {
                                call_user_func([$model, 'macro'], $transition->value, function ($context = []) use ($transition, $stateMachine) {
                                    $stateMachine->apply($this, $transition->value, $context);
                                });

                                call_user_func([$model, 'macro'], 'can' . Str::studly($transition->value), function () use ($transition, $stateMachine) {
                                    return $stateMachine->can($this, $transition->value);
                                });
                            }
                        }
                    }
                    $this->detect[$className] = true;
                }
            });

            $this->app->event->listen(ModelGenerator::class, function (ModelGenerator $generator) use ($reader) {

                $annotations = $reader->getClassAnnotations($generator->getReflection());

                foreach ($annotations as $annotation) {
                    if ($annotation instanceof StateMachine) {
                        foreach ($annotation->transitions as $transition) {
                            $generator->addMethod($transition->value, 'void', ['array $context = []'], false);
                            $generator->addMethod('can' . Str::studly($transition->value), 'boolean', [], false);
                        }
                    }
                }
            });
        }
    }
}
