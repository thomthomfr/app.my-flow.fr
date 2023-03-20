<?php

namespace App\Controller;

use App\Entity\Product;
use App\Enum\ProductType;
use App\Repository\JobRepository;
use App\Repository\ProductRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    /**
     * Endpoint to synchronize the products with the front
     * The front sends all the products to this endpoint, then this method create or update the new/existings products,
     * and deletes the products that no longer exists in front
     *
     * @param Request $request
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @param ServiceRepository $serviceRepository
     * @param JobRepository $jobRepository
     *
     * @return JsonResponse
     */
    #[Route('/api/produits', name: 'post_products', methods: ['POST'])]
    public function index(Request $request, ProductRepository $productRepository, EntityManagerInterface $entityManager, ServiceRepository $serviceRepository, JobRepository $jobRepository): JsonResponse
    {
        // mark all the products deleted, and then we will mark only the jobs that still exists in front
        $productRepository->markAllDeleted();

        $frontProducts = $request->request->all();
        foreach ($frontProducts as $frontProduct) {
            $product = $productRepository->findOneBy(['frontId' => $frontProduct['id']]);

            if (null === $product) {
                $product = new Product();
            } else {
                $product->getJobs()->clear();
            }

            $product->setFrontId($frontProduct['id']);
            $product->setName($frontProduct['name']);
            $product->setDeleted(false);
            $product->setPrice($frontProduct['price']);
            $product->setType(empty($frontProduct['type']) || $frontProduct['type'] === 'Au forfait' ? ProductType::AU_FORFAIT : ProductType::A_EVALUER);

            if (!empty($frontProduct['jobs'])) {
                foreach ($frontProduct['jobs'] as $job) {
                    $job = $jobRepository->findOneBy(['id' => $job]);

                    if (null !== $job) {
                        $product->addJob($job);
                    }
                }
            }

            $entityManager->persist($product);
        }

        $entityManager->flush();

        // Then we delete the services linked to a deleted product
        $serviceRepository->deleteAllMarked();

        return new JsonResponse();
    }
}
