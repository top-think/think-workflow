<?php

namespace think\workflow\annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Class StateMachine
 * @package think\workflow\annotation
 * @Annotation
 * @Annotation\Target({"CLASS"})
 */
final class StateMachine extends Annotation
{
    /**
     * @var string[]
     */
    public $places;

    /**
     * @var Transition[]
     */
    public $transitions;

    public $initial;
}
