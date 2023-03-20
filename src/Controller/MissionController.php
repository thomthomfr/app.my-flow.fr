<?php

namespace App\Controller;

use App\Entity\FileMessage;
use App\Entity\FileMission;
use App\Entity\Historique;
use App\Entity\InfoMission;
use App\Entity\Message;
use App\Entity\Mission;
use App\Entity\MissionParticipant;
use App\Enum\BillingMethod;
use App\Enum\ProductType;
use App\Enum\Role;
use App\Event\Admin\MissionInitialTimeAfterValidationUpdatedEvent;
use App\Event\Admin\MissionNotificationActivatedEvent;
use App\Event\Chat\MessageSentEvent;
use App\Event\Mission\MissionAcceptedEvent;
use App\Event\Mission\MissionActivatedEvent;
use App\Event\Mission\MissionCancelledEvent;
use App\Event\Mission\MissionDesiredDeliveryUpdatedAfterValidationEvent;
use App\Event\Mission\MissionInitialTimeEvent;
use App\Event\Mission\MissionRefusedEvent;
use App\Event\Mission\MissionArchivedEvent;
use App\Event\Workflow\Step\WorkflowStepEnteredEvent;
use App\Form\AddMissionContactType;
use App\Form\AddMissionSubContractorType;
use App\Form\CampaignCancelType;
use App\Form\ChangeInitialTimeType;
use App\Form\ChangeRealTimeType;
use App\Form\EditDesiredDeliveryType;
use App\Form\EditSubcontractorParticipantType;
use App\Form\FileMissionType;
use App\Form\InfoMissionType;
use App\Form\InitialBriefType;
use App\Form\MessageType;
use App\Form\MissionCancelType;
use App\Form\MissionEditInitialTimeType;
use App\Form\MissionParticipantDelaisType;
use App\Form\MissionParticipantIncomeType;
use App\Form\MissionParticipantType;
use App\Form\MissionQuantityType;
use App\Repository\CampaignRepository;
use App\Repository\HistoriqueRepository;
use App\Repository\InfoMissionRepository;
use App\Repository\MessageRepository;
use App\Repository\MissionParticipantRepository;
use App\Repository\MissionRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use App\Service\CreditService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MissionController extends AbstractController
{
    /**
     * Displays the missions index
     *
     * The admin views all the missions
     * A company user view only his company's missions
     * A subcontractor view only his missions
     *
     * @param CampaignRepository $campaignRepository
     * @param CreditService $creditService
     *
     * @return Response template /mission/index.html.twig
     */
    #[Route('/mission', name: 'mission_index')]
    public function index(Request $request, CampaignRepository $campaignRepository, CreditService $creditService, MissionParticipantRepository $missionParticipantRepository,UserRepository $userRepository,ParameterBagInterface $parameterBagInterface): Response
    {
        /*$user = $userRepository->findByEmail("anthonyclient@yopmail.com");
        $url = "https://dev.my-flow.fr/?tsso=".(hash('sha256', $user[0]->getEmail().$user[0]->getEmail()));
        
        echo "<script>window.open('$url','_blank')</script>"; // Ouvrir une nouvelle fenêtre
        //header("Location: $url"); // Rediriger l'utilisateur vers l'URL spécifiée
        exit; // Terminer le script
       // dd($user);
        die('sdf');*/
        $campaignCommand = [];
        $campaignsPrice = [];
        $totalHtPerMonth = [];
        $creditConso = [];
        $invoiced = [];
        $nombreCampaignCommande=0;

        $formMissionQuantity = $this->createForm(MissionQuantityType::class);
        $formMissionInitialTime = $this->createForm(MissionParticipantType::class);
        $formMissionInitialTimeManually = $this->createForm(MissionParticipantDelaisType::class);
        $formMissionIncomeManually = $this->createForm(MissionParticipantIncomeType::class);
   
        if ($this->isGranted(Role::ROLE_ADMIN->value)) {
            $campaigns = $campaignRepository->orderedByDesiredDelivery();
            $priceCampaign = $missionParticipantRepository->findByInitialTime();
            $estimatedIncome = [];
            $initialTime = [];
            $price = [];
            foreach ($priceCampaign as $value){
                if (!isset($estimatedIncome[$value->getMission()->getId()])){
                    $estimatedIncome[$value->getMission()->getId()] = [];

                }
                $initialTime[$value->getMission()->getId()][] = $value->getInitialTime();
                $price[$value->getMission()->getId()][] = $value->getEstimatedIncome();
            }
        } elseif ($this->isGranted(Role::ROLE_SUBCONTRACTOR->value)) {
            $campaigns = $campaignRepository->findForSubcontractor($this->getUser(),"ROLE_SUBCONTRACTOR");
            $estimatedIncome = $missionParticipantRepository->findBy(['user' => $this->getUser()]);
        }
        elseif ($this->isGranted(Role::ROLE_CLIENT->value) || $this->isGranted(Role::ROLE_CLIENT_ADMIN->value)){
            $campaigns = $campaignRepository->orderedByDesiredDelivery($this->getUser()->getCompany());
            $estimatedIncome = null;
        }
        else {           
            $campaigns = $campaignRepository->findForSubcontractor($this->getUser());
            $estimatedIncome = $missionParticipantRepository->findBy(['user' => $this->getUser()]);
        }
      
        foreach ($campaigns as $campaign) {
            if ($campaign->getState() != 'provisional' &&  $campaign->getState() != 'waiting' ) {
                if (!isset($campaignCommand[$campaign->getCreatedAt()->format('Y-m-t')])) {
                    $campaignCommand[$campaign->getCreatedAt()->format('Y-m-t')] = [];
                }

                $nombreCampaignCommande++;
                $campaignCommand[$campaign->getCreatedAt()->format('Y-m-t')][] = $campaign;
                foreach ($campaign->getMissions() as $mission){
                    if (!isset($campaignCommand[$campaign->getCreatedAt()->format('Y-m-t')])) {
                        $campaignsPrice[$campaign->getCreatedAt()->format('Y-m-t')][] = [];
                        $totalHtPerMonth[$campaign->getCreatedAt()->format('Y-m-t')][] = [];
                        $creditConso[$campaign->getCreatedAt()->format('Y-m-t')][] = [];
                        $invoiced[$campaign->getCreatedAt()->format('Y-m-t')][] = [];
                    }
                    $totalHtPerMonth[$campaign->getCreatedAt()->format('Y-m-t')][] = $mission->getPrice();
                   
                    $invoiced[$campaign->getCreatedAt()->format('Y-m-t')][] = $campaign->getInvoiced();

                    if ($campaign->getCompany()->getContract() == 0) {
                        $creditConso[$campaign->getCreatedAt()->format('Y-m-t')][] = round($campaign->getTotalCost() / $campaign->getCompany()->getCostOfDiscountedCredit());
                    }else{
                        $creditConso[$campaign->getCreatedAt()->format('Y-m-t')][] = null;
                    }
                }
            }
        }
        $company = $this->getUser()->getCompany();
        $creditAvailable = $creditService->CreditAvailable($company);
        $allCredit = 0;
        foreach ($creditAvailable as $credit){
            $allCredit += $credit->getCredit();
        }

        $formCancelCampaign = $this->createForm(CampaignCancelType::class);
        $formCancelMission = $this->createForm(MissionCancelType::class);
        $urlToConnectWp = $parameterBagInterface->get('front_website_url')."?tsso=".hash('sha256', $this->getUser()->getEmail().$this->getUser()->getEmail());

        return $this->renderForm('mission/index.html.twig', [
            'campaigns' => $campaigns,
            'allCredit' => $allCredit,
            'campaignCommand' => $campaignCommand,
            'campaignsPrice' => $campaignsPrice,
            'totalHtPerMonth' => $totalHtPerMonth,
            'creditConso' => $creditConso,
            'invoiced' => $invoiced,
            'formMissionQuantity' => $formMissionQuantity,
            'formMissionInitialTime' => $formMissionInitialTime,
            'formMissionInitialTimeManually' => $formMissionInitialTimeManually,
            'formMissionIncomeManually' => $formMissionIncomeManually,
            'formCancelCampaign' => $formCancelCampaign,
            'formCancelMission' => $formCancelMission,
            'estimatedIncome' => $estimatedIncome,
            'urlToConnectWp' => $urlToConnectWp
        ]);
    }

    #[Route('/missionParticipant/{id}/changeInitialTime', name: 'mission_change_manually_time', methods: ['GET', 'POST'])]
    public function changeManuallyTime(Request $request, MissionParticipant $missionParticipant, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher): JsonResponse
    {
        if ($missionParticipant->getUser()->getBillingMethod() == 2 && $missionParticipant->getMission()->getState() == 'in_progress'){
            $missionParticipant->setInitialTime($request->query->get('delais'));

            $entityManager->persist($missionParticipant);
            $entityManager->flush();

            $event = new MissionInitialTimeAfterValidationUpdatedEvent($mission = $missionParticipant->getMission());
            $dispatcher->dispatch($event, MissionInitialTimeAfterValidationUpdatedEvent::NAME);

            $this->addFlash(
                type: 'success',
                message: 'Le délais client a bien été mis à jour',
            );


            return new JsonResponse(['redirect' => $this->generateUrl('mission_index', [], UrlGeneratorInterface::ABSOLUTE_URL)]);
        }

        $ratio = null;
        if (!empty($missionParticipant->getEstimatedIncome()) && !empty($missionParticipant->getInitialTime())){
            $ratio = $missionParticipant->getEstimatedIncome() / $missionParticipant->getInitialTime();
        }

        $formMissionInitialTimeManually = $this->createForm(MissionParticipantDelaisType::class, $missionParticipant);
        $formMissionInitialTimeManually->handleRequest($request);
        $missionParticipant->setInitialTime($request->query->get('delais'));
        if (empty($ratio)){
            $missionParticipant->setEstimatedIncome($missionParticipant->getInitialTime() * $missionParticipant->getUser()->getResaleRate());
        }else{
            $missionParticipant->setEstimatedIncome($missionParticipant->getInitialTime() * $ratio);
        }

        $entityManager->persist($missionParticipant);
        $entityManager->flush();

        $this->addFlash(
            type: 'success',
            message: 'Le délais client a bien été mis à jour',
        );

        return new JsonResponse(['redirect' => $this->generateUrl('mission_index', [], UrlGeneratorInterface::ABSOLUTE_URL)]);
    }

    #[Route('/missionParticipant/{id}/changeIncome', name: 'mission_change_manually_income', methods: ['GET', 'POST'])]
    public function changeManuallyIncome(Request $request, MissionParticipant $missionParticipant, EntityManagerInterface $entityManager)
    {
        $formMissionIncomeManually = $this->createForm(MissionParticipantIncomeType::class, $missionParticipant);
        $formMissionIncomeManually->handleRequest($request);

        $missionParticipant->setEstimatedIncome($request->query->get('income'));
        $entityManager->persist($missionParticipant);
        $entityManager->flush();

        $this->addFlash(
            type: 'success',
            message: 'Le revenu client a bien été mis à jour',
        );

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }

    #[Route('/mission/{id}/changeQuantity', name: 'mission_change_quantity', methods: ['GET', 'POST'])]
    public function changeQuantity(Request $request, Mission $mission, EntityManagerInterface $entityManager): Response
    {
        $formMissionQuantity = $this->createForm(MissionQuantityType::class, $mission);
        $formMissionQuantity->handleRequest($request);

        $mission->setQuantity($request->query->get('quantity'));
        $entityManager->persist($mission);
        $entityManager->flush();

        $this->addFlash(
            type: 'success',
            message: 'Le partenaire a bien été mis à jour',
        );

        return new JsonResponse(['total' => number_format($mission->getCampaign()->getTotalCost(), 2, '.', ' ')]);
    }

    #[Route('/mission/{id}/changeInitialTime', name: 'mission_change_initial_time', methods: ['GET', 'POST'])]
    public function changeInitialTime(Request $request, MissionParticipant $missionParticipant, EntityManagerInterface $entityManager): RedirectResponse
    {
        $formMissionInitialTime = $this->createForm(MissionParticipantType::class, $missionParticipant);
        $formMissionInitialTime->handleRequest($request);

        $missionParticipant->setInitialTime($formMissionInitialTime->getData()->getInitialTime());
        $missionParticipant->setEstimatedIncome($formMissionInitialTime->getData()->getEstimatedIncome());
        $entityManager->persist($missionParticipant);
        $entityManager->flush();

        $this->addFlash(
            type: 'success',
            message: 'Le temps initial a bien été mis à jour',
        );

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }

    #[Route('/mission/{id}/adminSetTime', name: 'mission_admin_set_initial_time', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminSetTime(Request $request, Mission $mission, EntityManagerInterface $entityManager): RedirectResponse
    {
        $formMissionInitialTime = $this->createForm(MissionParticipantType::class);
        $formMissionInitialTime->handleRequest($request);

        foreach ($mission->getParticipants() as $participant) {
            if ($participant->getRole() === Role::ROLE_SUBCONTRACTOR) {
                $participant->setInitialTime($formMissionInitialTime->getData()->getInitialTime());

                if ($participant->getUser()->getBillingMethod() === BillingMethod::BILL_PRESTATION->value) {
                    foreach ($participant->getUser()->getServices() as $service) {
                        if ($service->getProduct() == $mission->getProduct()) {
                            $participant->setEstimatedIncome($participant->getInitialTime() / 420 * $service->getPrice());
                        }
                    }
                } else {
                    $participant->setEstimatedIncome($participant->getInitialTime() / 420 * $participant->getUser()->getDailyRate());
                }
            }
        }

        $mission->setPrice($formMissionInitialTime->getData()->getEstimatedIncome());
        $mission->setAdminTime($formMissionInitialTime->getData()->getInitialTime());
        $mission->setAdminIncome($formMissionInitialTime->getData()->getEstimatedIncome());

        if ($mission->getCampaign()->getCompany()->getContract() == 0){
            $mission->setPrice($formMissionInitialTime->getData()->getEstimatedIncome() / $mission->getCampaign()->getCompany()->getCostOfDiscountedCredit());
        }else{
            $mission->setPrice($formMissionInitialTime->getData()->getEstimatedIncome());
        }

        $entityManager->flush();

        $this->addFlash(
            type: 'success',
            message: 'Les informations de la mission ont bien été mises à jour',
        );

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }

    /**
     * @param Mission $mission
     * @param FileMission|null $fileMission
     * @param Request $request
     * @param MessageRepository $messageRepository
     * @param InfoMissionRepository $infoMissionRepository
     * @param HistoriqueRepository $historiqueRepository
     * @return Response
     */
    #[Route('/mission/ajouter', name: 'mission_new', methods: ['GET','POST'])]
    #[Route('/mission/{id}', name: 'mission_edit', methods: ['GET','POST'])]
    public function handle(Mission $mission, FileMission $fileMission = null, Request $request, MessageRepository $messageRepository, InfoMissionRepository $infoMissionRepository, HistoriqueRepository $historiqueRepository, EventDispatcherInterface $dispatcher, EntityManagerInterface $entityManager, MissionParticipantRepository $missionParticipantRepository, ServiceRepository $serviceRepository, MissionRepository $missionRepository): Response
    {
        $message = new Message();
        $messages = $messageRepository->findBy(['campaign' => $mission->getCampaign()->getId()], ['createdAt' => 'ASC']);
        $informations = $infoMissionRepository->findBy(['mission' => $mission->getId()], ['createdAt' => 'ASC']);
        $campaign = $mission->getCampaign();

        if ($this->isGranted(Role::ROLE_ADMIN->value)) {
            $priceCampaign = $missionParticipantRepository->findByInitialTime();
            $estimatedIncome = [];
            $initialTime = [];
            $price = [];
            foreach ($priceCampaign as $value){
                if (!isset($estimatedIncome[$value->getMission()->getId()])){
                    $estimatedIncome[$value->getMission()->getId()] = [];

                }
                $initialTime[$value->getMission()->getId()][] = $value->getInitialTime();
                $price[$value->getMission()->getId()][] = $value->getEstimatedIncome();
            }
        } elseif ($this->isGranted(Role::ROLE_SUBCONTRACTOR->value)) {
            $visibleCampaign = ['orderedBy' => $this->getUser()];
            $estimatedIncome = $missionParticipantRepository->findBy(['user' => $this->getUser()]);
        } else {
            $estimatedIncome = null;
        }

        $formMissionInitialTime = $this->createForm(MissionParticipantType::class);
        $formMissionInitialTimeManually = $this->createForm(MissionParticipantDelaisType::class);
        $formMissionIncomeManually = $this->createForm(MissionParticipantIncomeType::class);
        /**
         * Form handle chatbox
         */
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $path = 'file_message_directory';
            $entityManager = $this->getDoctrine()->getManager();
            $files = $form->get('fileMessages')->getData();

            foreach($files as $file){
                $destination = $this->getParameter($path).'/'.$mission->getId().'/message';
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$file->guessExtension();
                $file->move(
                    $destination,
                    $newFilename
                );
                $fileMessage = new FileMessage();
                $fileMessage->setName($newFilename);
                $message->addFileMessage($fileMessage);
            }

            $message->setUser($this->getUser())
                    ->setCampaign($mission->getCampaign());
            $entityManager->persist($message);
            $entityManager->flush();

            $event = new MessageSentEvent($message);
            $dispatcher->dispatch($event, MessageSentEvent::NAME);

            return $this->redirectToRoute('mission_edit', ['id' => $mission->getId()], Response::HTTP_SEE_OTHER);
        }


        /*
         * Form handle information général
         */
        $infoMission = new InfoMission();
        $form2 = $this->createForm(InfoMissionType::class, $infoMission);
        $form2->handleRequest($request);

        if ($form2->isSubmitted() && $form2->isValid()){
            $entityManager = $this->getDoctrine()->getManager();
            $infoMission->setMission($mission)
                        ->setUser($this->getUser());
            $mission->addInfoMission($infoMission);

            $entityManager->persist($infoMission);
            $entityManager->flush();
            return $this->redirectToRoute('mission_edit', ['id' => $mission->getId()], Response::HTTP_SEE_OTHER);
        }

        $actualDesiredDelivery = null;
        if (null !== $mission->getDesiredDelivery()) {
            $actualDesiredDelivery = clone $mission->getDesiredDelivery();
        }

        $formChangeDateDelivery = $this->createForm(EditDesiredDeliveryType::class, $mission);
        $formChangeDateDelivery->handleRequest($request);

        if ($formChangeDateDelivery->isSubmitted() && $formChangeDateDelivery->isValid()){
            if (null !== $actualDesiredDelivery && $actualDesiredDelivery->format('Y-m-d') !== $formChangeDateDelivery->getData()->getDesiredDelivery()->format('Y-m-d')){
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($mission);
                $entityManager->flush();

                if ($mission->getState() != 'provisionnal'){
                    $event = new MissionDesiredDeliveryUpdatedAfterValidationEvent($mission);
                    $dispatcher->dispatch($event, MissionDesiredDeliveryUpdatedAfterValidationEvent::NAME);
                }

                $this->addFlash(
                    type: 'success',
                    message: 'La date de livraison souhaitée à bien été modifiée'
                );
            }

            return $this->redirectToRoute('mission_edit', ['id' => $mission->getId()], Response::HTTP_SEE_OTHER);

        }

        $formInitialTime = $this->createForm(ChangeInitialTimeType::class, $mission);
        $formInitialTime->handleRequest($request);

        if ($formInitialTime->isSubmitted() && $formInitialTime->isValid()){

            $initialTime = $formInitialTime->getData()->getInitialTime();
            $product = $mission->getProduct()->getId();
            $participant = $missionParticipantRepository->findOneBy(['mission' => $mission]);
            $service = $serviceRepository->findOneBy(['user' => $participant->getUser(), 'product' => $product]);
            $calculPrice = $initialTime / 60 * $service->getResale() * $mission->getQuantity();

            $mission->setPrice($calculPrice);
            $entityManager->persist($mission);
            $entityManager->flush();

            $event = new MissionInitialTimeEvent($mission);
            $dispatcher->dispatch($event, MissionInitialTimeEvent::NAME);

            $this->addFlash(
                type: 'success',
                message: 'Le temps initial a bien été ajouté'
            );

            return $this->redirectToRoute('mission_edit', ['id' => $mission->getId()], Response::HTTP_SEE_OTHER);
        }

        $formRealTime = $this->createForm(ChangeRealTimeType::class, $mission);
        $formRealTime->handleRequest($request);

        if ($formRealTime->isSubmitted() && $formRealTime->isValid()){

            $entityManager->persist($mission);
            $entityManager->flush();

            $this->addFlash(
                type: 'success',
                message: 'Le temps réel passé a bien été ajouté'
            );

            return $this->redirectToRoute('mission_edit', ['id' => $mission->getId()], Response::HTTP_SEE_OTHER);
        }

        /**
         * Form handle fichier demande initial
         */
        $form3 = $this->createForm(FileMissionType::class, $fileMission);
        $form3->handleRequest($request);

        if ($form3->isSubmitted() && $form3->isValid()){
            $path = 'file_mission_directory';
            $entityManager = $this->getDoctrine()->getManager();
            $files = $form3->get('fileMissions')->getData();

            foreach($files as $file){
                $destination = $this->getParameter($path).'/'.$mission->getId();
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$file->guessExtension();
                $file->move(
                    $destination,
                    $newFilename
                );

                $fileMission = new FileMission();
                $fileMission->setName($newFilename);
                $mission->addFileMission($fileMission);
            }

            $entityManager->persist($fileMission);
            $entityManager->flush();

            $this->addFlash(
                type: 'success',
                message: 'Le fichier a bien été ajouté à la demande'
            );

            return $this->redirectToRoute('mission_edit', ['id' => $mission->getId()], Response::HTTP_SEE_OTHER);
        }

        $formInitialBrief = $this->createForm(InitialBriefType::class, $mission);
        $formInitialBrief->handleRequest($request);

        if ($formInitialBrief->isSubmitted() && $formInitialBrief->isValid() ){
            $entityManager->persist($mission);

            $this->addFlash(
                type: 'success',
                message: 'Demande initiale modifiée avec succès.'
            );

            $historique = (new Historique())
                ->setUser($this->getUser())
                ->setMission($mission)
                ->setMessage('Modification de la demande initiale par : ' . $this->getUser());

            $entityManager->persist($historique);
            $entityManager->flush();

            return $this->redirectToRoute('mission_edit', ['id' => $mission->getId()], Response::HTTP_SEE_OTHER);
        }

        $formAddMissionContact = $this->createForm(AddMissionContactType::class, null, ['action' => $this->generateUrl('mission_participant_add', ['mission' => $mission->getId()])]);
        $formAddMissionSubContractor = $this->createForm(AddMissionSubContractorType::class, null, ['action' => $this->generateUrl('mission_participant_add', ['mission' => $mission->getId()])]);
        $formEditSubcontractor = $this->createForm(EditSubcontractorParticipantType::class);

        return $this->renderForm('mission/handle.html.twig', [
            'mission' => $mission,
            'form' => $form,
            'form2' => $form2,
            'form3' => $form3,
            'messages' => $messages,
            'informations' => $informations,
            'formAddMissionContact' => $formAddMissionContact,
            'formEditSubcontractor' => $formEditSubcontractor,
            'formChangeDateDelivery' => $formChangeDateDelivery,
            'formAddMissionSubContractor' => $formAddMissionSubContractor,
            'formMissionInitialTime' => $formMissionInitialTime,
            'formMissionInitialTimeManually' => $formMissionInitialTimeManually,
            'formMissionIncomeManually' => $formMissionIncomeManually,
            'formInitialTime' => $formInitialTime,
            'formRealTime' => $formRealTime,
            'formInitialBrief' => $formInitialBrief,
            'campaign' => $campaign,
            'estimatedIncome' => $estimatedIncome,
        ]);
    }

    #[Route('/mission/{id}/refus', name: 'mission_refus', methods: ['GET'])]
    public function refuse(Mission $mission, UserRepository $userRepository, Request $request, MissionParticipantRepository $missionParticipantRepository, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher)
    {
        $intervenant = $userRepository->find($request->query->get('intervenant'));

        if (null === $intervenant) {
            throw new NotFoundHttpException();
        }

        $event = new MissionRefusedEvent($mission, $intervenant);
        $dispatcher->dispatch($event, MissionRefusedEvent::NAME);

        $this->addFlash(
            type: 'success',
            message: 'La mission a bien été refusée',
        );

        return $this->redirectToRoute('mission_index');
    }

    #[Route('/mission/{id}/accepter', name: 'mission_accept', methods: ['GET'])]
    public function accept(Mission $mission, UserRepository $userRepository, Request $request, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher)
    {
        if (empty($request->query->get('intervenant'))){
            $intervenant = $this->getUser();
        }else{
            $intervenant = $userRepository->find($request->query->get('intervenant'));
        }

        if (null === $intervenant) {
            throw new NotFoundHttpException();
        }

        if ($mission->getWorkflow()) {
            $step = $mission->getWorkflow()->getSteps()->first();
            $step->setActive(true);

            if ($step->getManager() == 0) {
                $mission->setStateClient('Déclenchement en attente');
            } else {
                $mission->setStateProvider($step->getName());
            }

            $event = new WorkflowStepEnteredEvent($step);
            $dispatcher->dispatch($event, WorkflowStepEnteredEvent::NAME);
        }

        $campaign = $mission->getCampaign();
        $campaign->setState('in_progress');
        $mission->setState('in_progress');

        $entityManager->flush();

        $event = new MissionAcceptedEvent($mission, $intervenant);
        $dispatcher->dispatch($event, MissionAcceptedEvent::NAME);

        $this->addFlash(
            type: 'success',
            message: 'La mission a bien été acceptée',
        );

        return $this->redirectToRoute('mission_index');
    }

    #[Route('/mission/{id}/{transition<pause|unpause|cancel|archive>}', name: 'mission_transition')]
    public function transition(Mission $mission, string $transition, EventDispatcherInterface $dispatcher, Request $request, Registry $workflows, EntityManagerInterface $entityManager): RedirectResponse
    {
        $workflow = $workflows->get($mission, 'classic');

        if ($workflow->can($mission, $transition)) {
            $workflow->apply($mission, $transition);

            $applyToCampaign = true;
            foreach ($mission->getCampaign()->getMissions() as $otherMission) {
                if ($otherMission->getState() !== $mission->getState()) {
                    $applyToCampaign = false;
                    break;
                }
            }

            if ($applyToCampaign) {
                $campaign = $mission->getCampaign();
                $workflow = $workflows->get($campaign, 'classic');

                if ($workflow->can($campaign, $transition)) {
                    $workflow->apply($campaign, $transition);
                }

                $entityManager->persist($campaign);
            }

            if ($transition === 'cancel') {
                $formCancelMission = $this->createForm(MissionCancelType::class, $mission);
                $formCancelMission->handleRequest($request);

                $event = new MissionCancelledEvent($mission);
                $dispatcher->dispatch($event, MissionCancelledEvent::NAME);
            }
        }

        $entityManager->flush();

        if ($transition === 'archive') {
            $event = new MissionArchivedEvent($mission);
            $dispatcher->dispatch($event, MissionArchivedEvent::NAME);

            $entityManager->persist($mission);
        }

        $message = match ($transition) {
            'archive' => 'archivée',
            'pause' => 'mise en pause',
            'unpause' => 'relancée',
            'cancel' => 'annulée',
            default => 'enregistrée',
        };

        $this->addFlash(
            type: 'success',
            message: 'La mission a bien été '.$message,
        );

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/mission/{id}/activation', name: 'mission_activate', methods: ['GET', 'POST'])]
    public function Activate(Request $request, Mission $mission, EventDispatcherInterface $dispatcher, EntityManagerInterface $entityManager, MissionParticipantRepository $missionParticipantRepository, RouterInterface $router): RedirectResponse
    {
        if (null === $mission->getWorkflow()) {
            $this->addFlash(
                type: 'error',
                message: 'La mission n\'a pas de workflow, merci de contacter l\'administrateur',
            );

            return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
        }

        $activate = true;

        if ($this->isGranted(Role::ROLE_ADMIN->value)){
            foreach ($mission->getParticipants() as $missionParticipation){
                $missionParticipation->setActivated(true);
                $entityManager->persist($missionParticipation);
            }

            $entityManager->flush();
        }elseif($this->isGranted(Role::ROLE_SUBCONTRACTOR->value)){
            if (empty($mission->getPreActivatedAt())){
                $mission->setPreActivatedAt(new \DateTime());
                $entityManager->persist($mission);
            }
            $myParticipation = $missionParticipantRepository->findOneBy(['mission' => $mission, 'user' => $this->getUser()]);
            $myParticipation->setActivated(true);
            $myParticipation->setActivatedAt(new \DateTime());
            $entityManager->persist($myParticipation);
            $entityManager->flush();

            $missionParticipants = $missionParticipantRepository->findBy(['mission' => $mission]);
            foreach ($missionParticipants as $missionParticipant){
                if ($missionParticipant->getRole() === Role::ROLE_SUBCONTRACTOR && !$missionParticipant->isActivated()){
                    $activate = false;
                }
            }
        }

        if ($activate){
            if ($mission->getProduct()->getType() === ProductType::AU_FORFAIT) {
                return $this->redirectToRoute('mission_accept', ['id' => $mission->getId()]);
            }else{
                $mission->setStateProvider(null);
                $mission->setStateClient(null);
                $mission->getCampaign()->setState('waiting_activated');

                $entityManager->flush();

                $client = $mission->getCampaign()->getOrderedBy();
                $event = new MissionActivatedEvent($mission, $client);
                $dispatcher->dispatch($event, MissionActivatedEvent::NAME);
            }

            $event = new MissionNotificationActivatedEvent($mission);
            $dispatcher->dispatch($event, MissionNotificationActivatedEvent::NAME);
        }

        $this->addFlash(
            type: 'success',
            message: 'La mission pourra démarer une fois que le client aura validé l\'évaluation',
        );

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }
}
