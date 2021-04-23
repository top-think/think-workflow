<?php

namespace think\workflow\annotation;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class StateMachine
{
    public function __construct(
        public string $name,
        public string|array $places,
        public array $transitions,
        public $initial = null
    )
    {
    }
}
