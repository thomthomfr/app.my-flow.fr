<?php

namespace App\Form\DataTransformer;

use App\Entity\Product;
use App\Entity\WorkflowStep;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ProductFromFrontIdTransformer implements DataTransformerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ){}

    /**
     * Transforms a Product into a string (id)
     *
     * @param mixed $product
     *
     * @return string the step's id
     */
    public function transform($product): string
    {
        if (null === $product) {
            return '';
        }

        return $product->getId();
    }

    /**
     * Transforms a string (frontId) into a Product object
     *
     * @param string $frontId
     *
     * @throws TransformationFailedException if a workflow step is not found
     *
     * @return \App\Entity\Product
     */
    public function reverseTransform($frontId)
    {
        if (!$frontId) {
            return null;
        }

        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy(['frontId' => $frontId]);

        if (null === $product) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf(
                'A Product with number "%s" does not exist!',
                $product
            ));
        }

        return $product;
    }
}
