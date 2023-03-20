<?php

namespace App\Form\DataTransformer;

use App\Entity\User;
use App\Entity\WorkflowStep;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class UserToEmailTransformer implements DataTransformerInterface
{

    public function __construct(
        private EntityManagerInterface $entityManager,
    ){}

    /**
     * Transforms a User into a string (email)
     *
     * @param mixed $user
     *
     * @return string the user's email
     */
    public function transform($user): string
    {
        if (null === $user) {
            return '';
        }

        return $user->getEmail();
    }

    /**
     * Transforms a string (email) into a User object
     *
     * @param string $email
     *
     * @throws TransformationFailedException if a user is not found
     *
     * @return \App\Entity\User
     */
    public function reverseTransform($email)
    {
        if (!$email) {
            return null;
        }

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if (null === $user) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf(
                'A user with email "%s" does not exist!',
                $user
            ));
        }

        return $user;
    }
}
