<?php

namespace think\workflow;

use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use think\Model;

class ModelMarkingStore implements MarkingStoreInterface
{
    private $property;
    private $map;

    public function __construct(string $property = 'status', ?array $map = null)
    {
        $this->property = $property;
        $this->map      = $map;
    }

    protected function convert($value, $flip = true)
    {
        if (empty($this->map)) {
            return $value;
        }
        if ($flip) {
            return array_flip($this->map)[$value];
        }
        return $this->map[$value];
    }

    /**
     * @param Model $subject
     * @inheritDoc
     */
    public function getMarking(object $subject)
    {
        $marking = $this->convert($subject->getAttr($this->property));

        if (!$marking) {
            return new Marking();
        }

        $marking = [(string) $marking => 1];

        return new Marking($marking);
    }

    /**
     * @param Model $subject
     * @inheritDoc
     */
    public function setMarking(object $subject, Marking $marking, array $context = [])
    {
        $marking = $marking->getPlaces();

        $marking = key($marking);

        $subject->setAttr($this->property, $this->convert($marking, false));

        $subject->save($context);
    }
}
