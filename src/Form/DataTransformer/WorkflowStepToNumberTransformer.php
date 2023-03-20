<?php

namespace App\Form\DataTransformer;

use App\Entity\WorkflowStep;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class WorkflowStepToNumberTransformer implements DataTransformerInterface
{

    public function __construct(
        private EntityManagerInterface $entityManager,
    ){}

    /**
     * Transforms a WorkflowStep into a string (id)
     *
     * @param mixed $step
     *
     * @return string the step's id
     */
    public function transform($step): string
    {
        if (null === $step) {
            return '';
        }

        return $step->getId();
    }

    /**
     * Transforms a string (id) into a WorkflowStep object
     *
     * @param string $stepId
     *
     * @throws TransformationFailedException if a workflow step is not found
     *
     * @return \App\Entity\WorkflowStep
     */
    public function reverseTransform($stepId)
    {
        if (!$stepId) {
            return null;
        }

        $step = $this->entityManager
            ->getRepository(WorkflowStep::class)
            ->find($stepId);

        if (null === $step) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf(
                'A WorkflowStep with number "%s" does not exist!',
                $stepId
            ));
        }

        return $step;
    }
}
