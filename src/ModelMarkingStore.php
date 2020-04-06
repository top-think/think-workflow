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

    protected function getPlace($marking)
    {
        if (empty($this->map)) {
            return $marking;
        }
        return $this->map[$marking];
    }

    /**
     * @param Model $subject
     * @inheritDoc
     */
    public function getMarking(object $subject)
    {
        $marking = $this->getPlace($subject->getAttr($this->property));

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

        $subject->setAttr($this->property, $this->getPlace($marking));

        $subject->save($context);
    }
}
