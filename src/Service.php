<?php

namespace think\workflow;

use ReflectionClass;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy;
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
                    $stateMachine = \think\workflow\StateMachine::make($attribute);

                    $definition = $stateMachine->buildDefinition();

                    $marking = new ModelMarkingStore($stateMachine->name);

                    $workflow = new Workflow($definition, $marking, null, get_class($model) . "@" . $stateMachine->name);

                    $registry->addWorkflow($workflow, new InstanceOfSupportStrategy(get_class($model)));

                    foreach ($stateMachine->transitions as $name => $transition) {
                        call_user_func([$model, 'macro'], $name, function ($context = []) use ($stateMachine, $name, $workflow) {
                            $stateMachine->trigger('before', $name, $this);
                            $workflow->apply($this, $name, $context);
                            $stateMachine->trigger('after', $name, $this);
                        });

                        call_user_func([$model, 'macro'], 'can' . Str::studly($name), function () use ($name, $workflow) {
                            return $workflow->can($this, $name);
                        });
                    }
                }
            }
            $this->detected[$className] = true;
        });

        $this->app->event->listen(ModelGenerator::class, function (ModelGenerator $generator) {

            $attributes = $generator->getReflection()->getAttributes(StateMachine::class);

            foreach ($attributes as $attribute) {
                $stateMachine = \think\workflow\StateMachine::make($attribute);

                foreach ($stateMachine->transitions as $name => $transition) {
                    $generator->addMethod($name, 'void', ['array $context = []'], false);
                    $generator->addMethod('can' . Str::studly($name), 'boolean', [], false);
                }
            }
        });
    }
}
