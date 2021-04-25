<?php

namespace think\workflow;

use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use think\Model;

class ModelMarkingStore implements MarkingStoreInterface
{
    private $property;

    public function __construct(string $property = 'status')
    {
        $this->property = $property;
    }

    /**
     * @param Model $subject
     * @inheritDoc
     */
    public function getMarking(object $subject)
    {
        $marking = $subject->getAttr($this->property);

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

        $subject->setAttr($this->property, $marking);

        $subject->save($context);
    }
}
