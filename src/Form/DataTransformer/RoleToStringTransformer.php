<?php

namespace App\Form\DataTransformer;

use App\Entity\WorkflowStep;
use App\Enum\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class RoleToStringTransformer implements DataTransformerInterface
{
    /**
     * Transforms a Role into a string
     *
     * @param mixed $role
     *
     * @return string the role's value
     */
    public function transform($role): string
    {
        if (null === $role) {
            return '';
        }

        return $role->value;
    }

    /**
     * Transforms a string (value) into a Role enum
     *
     * @param string $roleValue
     *
     * @throws TransformationFailedException if a role is not found
     *
     * @return \App\Enum\Role
     */
    public function reverseTransform($roleValue)
    {
        if (!$roleValue) {
            return null;
        }

        $role = Role::tryFrom($roleValue);

        if (null === $role) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf(
                'A Role with value "%s" does not exist!',
                $roleValue
            ));
        }

        return $role;
    }
}
