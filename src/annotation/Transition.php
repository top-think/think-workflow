<?php

namespace think\workflow\annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Class Transition
 * @package think\workflow\annotation
 * @Annotation
 * @Annotation\Target({"ANNOTATION"})
 */
final class Transition extends Annotation
{

    /**
     * @var string|string[]
     */
    public $from;

    /**
     * @var string|string[]
     */
    public $to;
}
