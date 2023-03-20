<?php

namespace App\Controller;

use App\Entity\Mission;
use App\Entity\Campaign;
use App\Entity\Company;
use App\Entity\FileMission;
use App\Entity\MissionParticipant;
use App\Entity\User;
use App\Enum\ProductType;
use App\Enum\Role;
use App\Event\Campaign\CampaignCancelledEvent;
use App\Event\Campaign\CampaignCreatedEvent;
use App\Event\Campaign\CampaignEvaluationEvent;
use App\Event\Campaign\CampaignModifiedEvent;
use App\Event\Campaign\CampaignValidatedEvent;
use App\Event\Campaign\CampaignWaitingEvent;
use App\Event\ClientUpdatedEvent;
use App\Event\Mission\MissionDesiredDeliveryUpdatedAfterValidationEvent;
use App\Event\Mission\MissionDesiredDeliveryUpdatedBeforeValidationEvent;
use App\Event\Mission\MissionWithoutSubContractorCheckedEvent;
use App\Event\Mission\MissionWithoutWorkflowEvent;
use App\Event\Workflow\Step\WorkflowFirstStepEnteredEvent;
use App\Event\Workflow\Step\WorkflowStepEnteredEvent;
use App\Form\CampaignCancelType;
use App\Form\CampaignType;
use App\Form\ListMissionFormType;
use App\Repository\MissionParticipantRepository;
use App\Repository\MissionRepository;
use App\Repository\UserRepository;
use App\Repository\WorkflowRepository;
use App\Service\MissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Workflow\Registry;
use App\Event\Mission\MissionSendEvent;
use App\Enum\AdminMail;
use App\Repository\ProductRepository;
use App\Event\Campaign\CampaignNotFinishedEvent;
use App\Event\Campaign\DevisCreatedNotFinished;
use App\Repository\CompanyRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CampaignController extends AbstractController
{
    /**
     * @param CompanyRepository $companyRepository
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/api/discount', name: 'company_discount', methods: ['POST'])]
    public function discount(CompanyRepository $companyRepository,Request $request): JsonResponse
    {
        $id = $request->request->get('id');
        $company = $companyRepository->findByFrontId((int)$id);
        return new JsonResponse((int)$company[0]->getCustomerDiscount());
    }

    /**
     * @param CompanyRepository $companyRepository
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/api/imgCompany', name: 'company_img', methods: ['GET'])]
    public function imgCompany(CompanyRepository $companyRepository,Request $request,ParameterBagInterface $parameter): JsonResponse
    {
        
        $idCompany = $request->get('companyId');
        $urlDir = $parameter->get('dir_logo_company');

        if (empty($idCompany) ) {
            $response = new JsonResponse(['failed']);
        }
        $company = $companyRepository->findById($idCompany);

        if (is_null($company[0]->getLogoName())) {
            $response = new JsonResponse(['failed']);
        }else{
            $response = new JsonResponse([$urlDir.$company[0]->getLogoName()]);
        }
        $response->setEncodingOptions($response->getEncodingOptions() | JSON_UNESCAPED_UNICODE);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        return $response;
    }

    /**
     * Display the view/handle how to edit a campaign
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Json
     */
    #[Route('/api/preCommande', name: 'pre_commande', methods: ['POST'])]
    public function preCommande(Request $request,EntityManagerInterface $entityManager,UserRepository $userRepository,ProductRepository $productRepository,EventDispatcherInterface $dispatcher){
        
        $campaignData = $request->request->get('campaign');
        $missionData = $request->request->get('missions');
        $type = $request->request->get('type');
        $user = $userRepository->findOneBy(['email' => $campaignData['orderedBy']]);
        $admins = AdminMail::cases();
        $campaign = new Campaign();

        $campaign->setName($campaignData['name'])
                ->setBrief($campaignData['brief']);

        $entityManager->persist($campaign);
        $campaign->setCompany($user->getCompany())
                ->setOrderedBy($user);
        
        foreach ($missionData as $missions) {
            if (isset($missions['product'])) {
                $mission  = new Mission();
                $product  = $productRepository->findByFrontId($missions['product']);
               
                if (!empty($product)) {
                    $mission->setProduct($product[0])
                            ->setPrice(floatval($missions['price']))
                            ->setQuantity($missions['quantity'])
                    ;
                }
                $campaign->addMission($mission);
            }
        }
        
        if ($type === "devis") {
            $event = new DevisCreatedNotFinished($campaign,$user);
            $dispatcher->dispatch($event, DevisCreatedNotFinished::NAME);
        }else{
            $event = new CampaignNotFinishedEvent($campaign,$mission,$user);
            $dispatcher->dispatch($event, CampaignNotFinishedEvent::NAME);
        }
        $entityManager->remove($campaign);
        $entityManager->flush();
        
        return new JsonResponse([]);
    }

    #[Route('/api/campaigns', name: 'api_campaigns_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer, WorkflowRepository $workflowRepository, UserRepository $userRepository, EventDispatcherInterface $dispatcher, UserPasswordHasherInterface $passwordHasher, MissionService $missionService, MailerInterface $mailer,MissionRepository $missionRepository): JsonResponse
    {
        $campaign = new Campaign();
        $form = $this->createForm(CampaignType::class, $campaign);
        $form->handleRequest($request);
        $newUserCreateId = [];
        $launchJustOne = true;
        
        if ($form->isSubmitted() && $form->isValid()) {
            $campaign->setCompany($campaign->getOrderedBy()->getCompany());

            $discountValueForOneCompany= $campaign->getCompany()->getCustomerDiscount()/100;

            $attachments = explode('|', $form->get('attachments')->getData());
            $participants = explode('|', $form->get('participants')->getData());

            $state = 'in_progress';
            foreach ($campaign->getMissions() as $mission) {
                 
                if ($mission->getProduct()->getType() === ProductType::A_EVALUER) {
                    $state = 'waiting';
                }
            }
            
            if ($campaign->getMissions()->count() === 0) {
                $state = 'provisional';
                //send mail new user in demande de devis
                foreach ($participants as $participant) {
                    if (!empty($participant)) {
                        [$email, $role] = explode(',', $participant);

                        $user = $userRepository->findOneBy(['email' => $email]);
                        
                        if (null === $user) {
                            $user = (new User())
                                ->setCompany($campaign->getOrderedBy()->getCompany())
                                ->setEmail($email)
                                ->setRoles([$role])
                                ->setEnabled(false);

                            $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
                            $entityManager->persist($user);
                            $entityManager->flush();
                            $event = new ClientUpdatedEvent($user, true);
                            $newUserCreateId[] = $user->getId();
                            $dispatcher->dispatch($event, ClientUpdatedEvent::NAME);
                        }
                    }
                    
                }
            }

            $campaign->setState($state);
            

            foreach ($campaign->getMissions() as $mission) {
     
                if (empty($mission->getReference())) {
                    $mission->setReference($missionService->generateReference());
                }


                if ($state === 'waiting' && $mission->getProduct()->getType() === ProductType::AU_FORFAIT) {
                    $mission->setState('provisional');
                } else {
                    $mission->setState($state);
                }

                if ($mission->getState() === 'waiting') {
                    $mission->setStateProvider('A évaluer');
                } else {
                    $mission->setStateProvider('A activer');
                }
                
                // recalculate the price of a mission from a product price not from a price of a mission
                $getPriceFromProduct = $mission->getProduct()->getPrice();
                $getPriceFromMission = $mission->getPrice();
                $mission->setPrice($getPriceFromProduct*$discountValueForOneCompany);

                $workflow = $workflowRepository->findOneBy(['template' => true, 'product' => $mission->getProduct(), 'company' => $campaign->getCompany(), 'active' => true]);

                if (null === $workflow) {
                    $workflow = $workflowRepository->findOneBy(['template' => true, 'product' => $mission->getProduct(), 'active' => true]);
                    if (null === $workflow) {
                        $mission->setWorkflow(null);
                        $event = new MissionWithoutWorkflowEvent($mission);
                        /*$dispatcher->dispatch($event, MissionWithoutWorkflowEvent::NAME);*/
                        $this->onMissionWithoutWorkflow($event,$userRepository,$mailer);

                    }
                    
                }

                if (null !== $workflow) {
                    $missionWorkflow = clone $workflow;
                    $missionWorkflow->setTemplate(false);

                    $mission->setWorkflow($missionWorkflow);
                    $missionWorkflow->setMission($mission);
                }

                $filesystem = new Filesystem();
             
                foreach ($attachments as $attachment) {
                    if (!empty($attachment)) {
                        $destination = $this->getParameter('file_mission_directory').'/'.$mission->getId();

                        if (!$filesystem->exists($destination)) {
                            $filesystem->mkdir($destination);
                        }

                        $originalFilename = basename($attachment);
                        file_put_contents($destination.'/'.$originalFilename, file_get_contents($attachment));

                        $fileMission = new FileMission();
                        $fileMission->setName($originalFilename);
                        $mission->addFileMission($fileMission);
                    }
                }
                
         
                foreach ($participants as $participant) {
                    if (!empty($participant)) {
                        [$email, $role] = explode(',', $participant);

                        $user = $userRepository->findOneBy(['email' => $email]);
                        
                        if (null === $user) {
                            $user = (new User())
                                ->setCompany($campaign->getOrderedBy()->getCompany())
                                ->setEmail($email)
                                ->setRoles([$role])
                                ->setEnabled(false);

                            $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
                            $entityManager->persist($user);
                            $entityManager->flush();
                            $event = new ClientUpdatedEvent($user, true);
                            $newUserCreateId[] = $user->getId();
                            $dispatcher->dispatch($event, ClientUpdatedEvent::NAME);
                            
                        }else{
                            
                            if ( !in_array($user->getId(), $newUserCreateId) && true === $launchJustOne) {
                                
                                $event = new MissionSendEvent($mission,$user,$role);
                                $dispatcher->dispatch($event,MissionSendEvent::NAME);
                            }
                            
                        }
                        
                        $participant = (new MissionParticipant())
                            ->setUser($user)
                            ->setMission($mission)
                            ->setRole(Role::tryFrom($role));
                        
                        $mission->addParticipant($participant);
                    }
                    
                }
                $launchJustOne = false;
                
            }

            try {
                
                $entityManager->persist($campaign);
                $entityManager->flush();
                
                $event = new CampaignCreatedEvent($campaign);
                $dispatcher->dispatch($event, CampaignCreatedEvent::NAME);

                return new JsonResponse(
                    json_decode($serializer->serialize($campaign, 'json', [AbstractNormalizer::GROUPS => ['campaign']])),
                    Response::HTTP_CREATED
                );
            } catch (\Exception $e) {
                $admins = AdminMail::cases();
         
                foreach (array_unique($newUserCreateId) as $id) {
                    $user = $userRepository->findOneBy(['id' => $id]);
                    $entityManager->remove($user);
                    $entityManager->flush();
                }
                foreach ($admins as $admin) {
                    $notification = (new NotificationEmail())
                        ->from(new Address('no-reply@my-flow.fr', 'myFlow'))
                        ->to(new Address($admin->value))
                        ->subject('Une erreur de commande est survenue')
                        ->content('<p>Bonjour,</p><p>Une erreur est survenue lors d\'une commande le '.date('d/m/Y à H:i').'. Voici l\'erreur en question : '.$e->getMessage().'</p>')
                        ->markAsPublic()
                    ;

                    try {
                        $mailer->send($notification);
                    } catch (\Exception $e) { /* TODO: logger ou afficher une alerte que l'email n'a pas été envoyé */ }
                }
            }
        }

        $admins = AdminMail::cases();

        $company = null;
        if (null !== $campaign->getCompany()) {
            $company = ' passée par la société '.$campaign->getCompany()->getName();
        }

        $name = null;
        if (!empty($campaign->getName())) {
            $name = ' concernant la campagne '.$campaign->getName();
        }

        foreach ($admins as $admin) {
            $notification = (new NotificationEmail())
                ->from(new Address('no-reply@my-flow.fr', 'myFlow'))
                ->to(new Address($admin->value))
                ->subject('Une erreur de commande est survenue')
                ->content('<p>Bonjour,</p><p>Une ou plusieurs erreurs sont survenues lors d\'une commande '.$company .$name.' le '.date('d/m/Y à H:i').'</p>')
                ->markAsPublic()
            ;

            try {
                $mailer->send($notification);
            } catch (\Exception $e) { /* TODO: logger ou afficher une alerte que l'email n'a pas été envoyé */ }
        }

        return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Display the view/handle how to edit a campaign
     *
     * @param Campaign $campaign - the campaign to edit
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     *
     * @return Response template /mission/handle_campaign.html.twig
     */
    #[Route('/campagnes/{id}', name: 'campaign_edit')]
    public function edit(Campaign $campaign, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CampaignType::class, $campaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', 'La campagne a bien été modifiée');
            $entityManager->flush();

            return $this->redirectToRoute('mission_index');
        }

        return $this->renderForm('campaign/edit.html.twig', [
            'form' => $form,
            'campaign' => $campaign,
        ]);
    }

    /**
     * If applicable, apply the workflow transition $transition on a campaign
     * and on all its missions.
     *
     * Only validate, pause, unpause, cancel and archived transitions are allowed
     *
     * @param Campaign $campaign - the campaign on whom to apply the transition
     * @param string $transition - the transition to apply
     * @param Registry $workflows
     * @param EntityManagerInterface $entityManager
     * @param EventDispatcherInterface $dispatcher
     *
     * @return Response Redirect to the mission's index
     */
    #[Route('/campagne/{id}/{transition<validate|pause|unpause|cancel|archive|validation_by_client|activated>}', name: 'campaign_transition')]
    public function transition(Campaign $campaign, string $transition, Registry $workflows, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher, Request $request): Response
    {
        $workflow = $workflows->get($campaign, 'classic');

        if ($workflow->can($campaign, $transition)){
            $workflow->apply($campaign, $transition);

            if ($transition === 'pause') {
                $event = new CampaignWaitingEvent($campaign, true);
                $dispatcher->dispatch($event, CampaignWaitingEvent::NAME);
            }

            if ($transition === 'cancel') {
                $formCancelCampaign = $this->createForm(CampaignCancelType::class, $campaign);
                $formCancelCampaign->handleRequest($request);

                $event = new CampaignCancelledEvent($campaign);
                $dispatcher->dispatch($event, CampaignCancelledEvent::NAME);
            }

            foreach ($campaign->getMissions() as $mission) {
                $workflow = $workflows->get($mission, 'classic');
                if ($workflow->can($mission, $transition)) {
                    $workflow->apply($mission, $transition);
                }
            }

            // If we validate the campaign, we dispatch the campaign.validated event
            if ($transition === 'validate') {
                $flag = true;
                foreach ($campaign->getMissions() as $mission){
                    $i = 0;
                    foreach ($mission->getParticipants() as $participant){
                        if ($participant->getRole() == Role::ROLE_SUBCONTRACTOR){
                            $i++;
                        }
                        if ($i < 1 || $participant->getMission()->getStateProvider() == 'A évaluer'){
                            $flag = false;
                        }
                    }
                }
                if ($flag == true){
                    $event = new CampaignValidatedEvent($campaign);
                    $dispatcher->dispatch($event, CampaignValidatedEvent::NAME);
                }else{
                    return $this->redirectToRoute('handle_mission_campaign', ['id' => $campaign->getId(), 'erreur' => true]);
                }
            }

            if ($transition === 'activated'){
                foreach ($campaign->getMissions() as $mission){
                    if (!empty($mission->getWorkflow())){
                        $step = $mission->getWorkflow()->getSteps()->first();
                        $step->setActive(true);
                    }else{
                        $this->addFlash(
                            type: 'error',
                            message: 'Activation impossible car la campagne n\'a pas de workflow',
                        );
                        return $this->redirectToRoute('mission_index');
                    }

                    if ($step->getManager() == 0) {
                        $mission->setStateClient('Déclenchement en attente');
                    } else {
                        $mission->setStateProvider($step->getName());
                    }

                    $event = new WorkflowStepEnteredEvent($step);
                    $dispatcher->dispatch($event, WorkflowStepEnteredEvent::NAME);
                }
            }
            $entityManager->flush();

            $message = match ($transition) {
                'validate' => 'validée',
                'pause' => 'mise en pause',
                'unpause' => 'relancée',
                'cancel' => 'annulée',
                default => 'enregistrée',
            };

            $this->addFlash(
                type: 'success',
                message: 'La campagne a bien été '.$message,
            );
        }

        return $this->redirectToRoute('mission_index');
    }

    #[Route('/campagne/{id}/missions', name: 'handle_mission_campaign', methods: ['GET','POST'])]
    public function handleAllMission(Request $request, Campaign $campaign, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher, MissionParticipant $missionParticipant = null, MissionRepository $missionRepository, MissionParticipantRepository $missionParticipantRepository, MissionService $missionService, UserRepository $userRepository): Response
    {
        $clients = $userRepository->findBy(['company' => $campaign->getOrderedBy()->getCompany()]);
        $missionsInCampaign = [];
        $allMissionsInCampaign = $missionRepository->findBy(['campaign' => $campaign]);
        foreach ($allMissionsInCampaign as $missionInCampaign){
            if (!isset($missionsInCampaign[$missionInCampaign->getId()])){
                $missionsInCampaign[$missionInCampaign->getId()] = [];
            }
            if (!empty($missionInCampaign->getDesiredDelivery())){
                $missionsInCampaign[$missionInCampaign->getId()] = clone $missionInCampaign->getDesiredDelivery();
            }
        }

        $formEditCampaign = $this->createForm(ListMissionFormType::class, $campaign);
        $formEditCampaign->handleRequest($request);

        if ($formEditCampaign->isSubmitted() && $formEditCampaign->isValid()) {
            $missions = $formEditCampaign->get('missions');

            foreach ($missions as $mission) {
                if (empty($mission->getData()->getReference())) {
                    foreach ($campaign->getMissions() as $mis) {
                        if (!empty($mis->getReference())) {
                            $mission->getData()->setReference($mis->getReference());
                            break;
                        }
                    }

                    if (empty($mission->getData()->getReference())) {
                        $mission->getData()->setReference($missionService->generateReference());
                    }
                }
                $missionId = $missionRepository->findOneBy(['id' => $mission->get('missionId')->getData()]);
                if (isset($missionsInCampaign[$mission->getData()->getId()]) && $mission->getData()->getDesiredDelivery() != $missionsInCampaign[$mission->getData()->getId()] && !in_array($mission->getData()->getState(), ['provisional', 'waiting'])){
                    $event = new MissionDesiredDeliveryUpdatedAfterValidationEvent($missionId);
                    $dispatcher->dispatch($event, MissionDesiredDeliveryUpdatedAfterValidationEvent::NAME);
                }elseif (isset($missionsInCampaign[$mission->getData()->getId()]) && $mission->getData()->getDesiredDelivery() != $missionsInCampaign[$mission->getData()->getId()] && ($mission->getData()->getState() == 'provisional' || $mission->getData()->getState() == 'waiting') ){
                    $campaign->setState('provisional');

                    $event = new MissionDesiredDeliveryUpdatedBeforeValidationEvent($mission->getData());
                    $dispatcher->dispatch($event, MissionDesiredDeliveryUpdatedBeforeValidationEvent::NAME);
                }

                if (
                    (empty($mission->get('newJob')->getData()) && empty($mission->get('newSubContractor')->getData()))
                    || (!empty($mission->get('newJob')->getData()) && !empty($mission->get('newSubContractor')->getData()))
                ) {
                    if (!empty($mission->get('newJob')->getData()) && !empty($mission->get('newSubContractor')->getData())) {
                        $missionParticipant = new MissionParticipant();
                        if (empty($missionId)){
                            $missionParticipant->setMission($mission->getData());
                        }else{
                            $missionParticipant->setMission($missionId);
                        }
                        $missionParticipant->setUser($mission->get('newSubContractor')->getData());
                        $missionParticipant->setJob($mission->get('newJob')->getData());
                        $missionParticipant->setRole(Role::ROLE_SUBCONTRACTOR);
                        $entityManager->persist($missionParticipant);
                        $entityManager->flush();
                    }
                } else {
                    $entityManager->persist($campaign);
                    $entityManager->flush();
                    $this->addFlash(
                        type: 'error',
                        message: 'Veuillez sélectionner les deux champs de la colonne sous-traitant',
                    );
                    return $this->redirect($request->headers->get('referer'));
                }

                $searchParticipant = $missionParticipantRepository->findOneBy(['mission' => $missionId, 'role' => 'ROLE_SUBCONTRACTOR']);

                if (empty($searchParticipant)){
                    $event = new MissionWithoutSubContractorCheckedEvent($mission->getData());
                    $dispatcher->dispatch($event, MissionWithoutSubContractorCheckedEvent::NAME);
                }

                if (!empty($mission->get('newWorkflow')->getData())){
                    $newWorkflow = clone $mission->get('newWorkflow')->getData();
                    $newWorkflow->setTemplate(false);
                    $mission->getData()->setWorkflow($newWorkflow);
                    $entityManager->persist($newWorkflow);
                }
            }

            $campaign->setState('provisional');
            foreach ($campaign->getMissions() as $mission){
                $mission->setState('provisional');
            }
            $entityManager->persist($campaign);
            $entityManager->flush();

            $this->addFlash('success', 'Modification enregistré');

            if ($formEditCampaign->getClickedButton() && 'editGlobalMssion' === $formEditCampaign->getClickedButton()->getName()) {
                // Btn édit on envoie juste un email.
                $event = new CampaignModifiedEvent($campaign);
                $dispatcher->dispatch($event, CampaignModifiedEvent::NAME);
            } else if ($formEditCampaign->getClickedButton() && 'saveGlobalMission' === $formEditCampaign->getClickedButton()->getName()) {
                $nbMission = [];
                $nbInitialNotEmpty = [];
                foreach ($campaign->getMissions() as $mission){
                    $nbMission[] = $mission;
                    $initialTime = $mission->getInitialTime();
                    if (!empty($initialTime)){
                        $nbInitialNotEmpty[] = $initialTime;
                    }
                }
                if (count($nbMission) === count($nbInitialNotEmpty)){
                    return $this->redirectToRoute('campaign_transition', ['id' => $campaign->getId(), 'transition' => 'unpause']);
                }
            }

            return $this->redirectToRoute('mission_index');
        }

        return $this->renderForm('campaign/edit_mission.html.twig', [
            'campaign' => $campaign,
            'form' => $formEditCampaign,
            'clients' => $clients,
        ]);
    }

    #[Route('/campagne/{id}/demande-evaluation', name: 'campaign_demande_evaluation')]
    public function evaluation(Request $request, Campaign $campaign, EventDispatcherInterface $dispatcher): Response
    {
        $event = new CampaignEvaluationEvent($campaign);
        $dispatcher->dispatch($event, CampaignEvaluationEvent::NAME);

        $this->addFlash(
            type: 'success',
            message: 'La demande d\'évaluation a bien été envoyée'
        );

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/campagne/{id}/recapitulatif', name: 'campaign_recapitulatif')]
    public function recapPanier(Request $request, Campaign $campaign): Response
    {
        $text = match ((int) $campaign->getCompany()->getContract()) {
            Company::PACK_CREDIT => 'Votre solde actuel',
            Company::END_OF_MONTH_BILLING => 'Budget HT consommé depuis le 1er du mois',
            Company::MONTHLY_BILLING => 'Solde sur '.$campaign->getCompany()->getCreditHistories()->last()->getMensualite().'€ HT/mois',
            default => '',
        };

        $currency = match ((int) $campaign->getCompany()->getContract()) {
            Company::PACK_CREDIT => 'crédits',
            default => '€',
        };

        return $this->renderForm('campaign/recapitulatif.html.twig', [
            'campaign' => $campaign,
            'text' => $text,
            'currency' => $currency,
            'balance' => $campaign->getCompany()->getCurrentBalance(),
        ]);
    }

    #[Route('/campagne/{id}/accepter', name: 'campaign_accept_all')]
    public function campaignAccept(Request $request, Campaign $campaign, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher): Response
    {
        $campaign->setState('in_progress');
        $entityManager->persist($campaign);

        foreach ($campaign->getMissions() as $mission){
            $user = $mission->getContact();
            $mission->setState('in_progress');
            $entityManager->persist($mission);

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
        }
        $entityManager->flush();

        $event = new CampaignValidatedEvent($campaign);
        $dispatcher->dispatch($event, CampaignValidatedEvent::NAME);

        $this->addFlash(
            type: 'success',
            message: 'La campagne à bien été acceptée'
        );
        return $this->redirectToRoute('mission_index');
    }

    #[Route('/campagne/{id}/resoumission', name: 'campaign_resoumission')]
    public function campaignResoumission(Request $request, Campaign $campaign, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher): Response
    {
        $event = new CampaignWaitingEvent($campaign);
        $dispatcher->dispatch($event, CampaignWaitingEvent::NAME);

        $this->addFlash(
            type: 'success',
            message: 'La campagne à bien été resoumise'
        );

        return $this->redirect($request->headers->get('referer'));
    }

    public function onMissionWithoutWorkflow(MissionWithoutWorkflowEvent $event,$userRepository,$mailer)
    {
        
        $mission = $event->getMission();

        if (!$mission instanceof Mission) {
            return;
        }
   
        $admins = AdminMail::cases();

        foreach ($admins as $admin) {
            
            $notification = (new NotificationEmail())
                ->to(new Address($admin->value))
                ->subject('Une mission a été créée sans workflow')
                ->content('
                <p>Bonjour,</p>
                <p>La mission '. $mission->getReference() .' contient un produit "'.$mission->getProduct()->getName().'" qui n\'a pas de Workflow associé.</p>
                <p>Merci d\'en créer un pour ce produit et d\'aller le relier à la mission.</p>
            ')
                ->action('Modifier la mission', $this->generateUrl('handle_mission_campaign', ['id' => $mission->getCampaign()->getId()], UrlGeneratorInterface::ABSOLUTE_URL))
                ->markAsPublic()
            ;
        
            $mailer->send($notification);
        }
    }
}
