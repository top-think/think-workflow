<?php

namespace think\workflow;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use think\Model;
use think\workflow\annotation\StateMachine;

class Service extends \think\Service
{
    public function boot(Reader $reader)
    {
        /** @var Registry $registry */
        $registry = $this->app->make(Registry::class);

        if ($this->app->bound(Reader::class)) {

            /** @var Reader $reader */
            $reader = $this->app->make(Reader::class);

            Model::maker(function (Model $model) use ($reader) {

                $annotations = $reader->getClassAnnotations(new \ReflectionClass($model));

                foreach ($annotations as $annotation) {
                    if ($annotation instanceof StateMachine) {
                        $builder = new DefinitionBuilder();
                        $builder->addPlaces($annotation->places);
                        foreach ($annotation->transitions as $transition) {
                            $builder->addTransition(new Transition($transition->value, $transition->from, $transition->to));
                        }
                        $builder->setInitialPlaces($annotation->initial);
                        $definition   = $builder->build();
                        $marking      = new ModelMarkingStore($annotation->value);
                        $stateMachine = new Workflow($definition, $marking);

                        foreach ($annotation->transitions as $transition) {
                            call_user_func([$model, 'macro'], $transition->value, function ($context = []) use ($transition, $stateMachine) {
                                $stateMachine->apply($this, $transition->value, $context);
                            });
                        }
                    }
                }
            });
        }
    }
}
