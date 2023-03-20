<?php

namespace App\Components;

use App\Entity\Campaign;
use App\Form\ListMissionFormType;
use App\Repository\MissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent('mission_prototype')]
class MissionPrototypeComponent extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public Campaign $campaign;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ListMissionFormType::class, $this->campaign);
    }

    #[LiveAction]
    public function addItem(): void
    {
        $this->formValues['missions'][] = [];
    }


    #[LiveAction]
    public function removeItem(#[LiveArg] string $index, EntityManagerInterface $entityManager, MissionRepository $missionRepository, Request $request)
    {
        $mission = $missionRepository->findOneBy(['id' => $index]);

        if (null !== $mission) {
            $entityManager->remove($mission);
            $entityManager->flush();
        }

        $this->addFlash('success', 'La mission a bien été supprimée');

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }
}
