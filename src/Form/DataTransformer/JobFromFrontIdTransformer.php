<?php

namespace App\Form\DataTransformer;

use App\Entity\Job;
use App\Entity\WorkflowStep;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class JobFromFrontIdTransformer implements DataTransformerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ){}

    /**
     * Transforms a Job into a string (id)
     *
     * @param mixed $job
     *
     * @return string the job's id
     */
    public function transform($job): string
    {
        if (null === $job) {
            return '';
        }

        return $job->getId();
    }

    /**
     * Transforms a string (frontId) into a Job object
     *
     * @param string $frontId
     *
     * @throws TransformationFailedException if a job is not found
     *
     * @return \App\Entity\Job
     */
    public function reverseTransform($frontId)
    {
        if (!$frontId) {
            return null;
        }

        $job = $this->entityManager
            ->getRepository(Job::class)
            ->findOneBy(['id' => $frontId]);

        if (null === $job) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf(
                'A Job with number "%s" does not exist!',
                $job
            ));
        }

        return $job;
    }
}
