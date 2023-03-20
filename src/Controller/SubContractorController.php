<?php

namespace App\Controller;

use App\Entity\Service;
use App\Entity\SubContractorCompany;
use App\Entity\SystemEmail;
use App\Entity\User;
use App\Enum\ProductType;
use App\Enum\Role;
use App\Event\AdminNotifiedSubContractorCompletedEvent;
use App\Event\SubContractor\SubContractorCompletedProfileEvent;
use App\Event\SubContractor\SubContractorServiceAddedEvent;
use App\Event\SubContractorUpdatedEvent;
use App\Form\SubContractorCompanyType;
use App\Form\SubContractorType;
use App\Form\ServiceType;
use App\Repository\HistoriqueRepository;
use App\Repository\JobRepository;
use App\Repository\MessageRepository;
use App\Repository\MissionParticipantRepository;
use App\Repository\ProductRepository;
use App\Repository\ServiceRepository;
use App\Repository\SubContractorCompanyRepository;
use App\Repository\SystemEmailRepository;
use App\Repository\UserRepository;
use App\Service\ServiceService;
use App\Service\ShortcodeService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class SubContractorController extends AbstractController
{
    /**
     * @param UserRepository $userRepository
     * @return Response
     */
    #[Route('/admin/fournisseurs', name: 'sub_contractor_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $role = Role::ROLE_SUBCONTRACTOR->value;
        return $this->render('sub_contractor/index.html.twig', [
            'subContractors' => $userRepository->findByRole($role),
        ]);
    }

    /**
     * @param User|null $user
     * @param Service|null $service
     * @param Request $request
     * @param UserService $userService
     * @param UserPasswordHasherInterface $hasher
     * @param ServiceRepository $serviceRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/admin/fournisseurs/ajouter', name: 'sub_contractor_new', methods: ['GET','POST'])]
    #[Route('/admin/fournisseurs/{id}', name: 'sub_contractor_edit', methods: ['GET','POST'])]
    #[Route('/mon-profil', name: 'my_profil', methods: ['GET','POST'])]
    public function handleSubContractor(User $user = null, Service $service = null, Request $request, UserService $userService, UserPasswordHasherInterface $hasher, ServiceRepository $serviceRepository, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher, ServiceService $serviceService, SystemEmailRepository $systemEmailRepository, ShortcodeService $shortcodeService, MailerInterface $mailer, ProductRepository $productRepository): Response
    {
        if ($user === null && $this->isGranted('ROLE_ADMIN')){
            $user = new User();
            $servicesSubContractor = null;
        }elseif ($this->isGranted('ROLE_ADMIN')){
            $servicesSubContractor = $serviceRepository->findBy(['user' => $user->getId()]);
        }else{
            $user = $this->getUser();
            $servicesSubContractor = $serviceRepository->findBy(['user' => $user->getId()]);
        }

        $oldJobs = clone $user->getJobs();

        $form = $this->createForm(SubContractorType::class, $user, ['admin' => $this->isGranted('ROLE_ADMIN')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($request->get('_route') === 'sub_contractor_new') {
                $password = $userService->generatePassword();
                $hashedPassword = $hasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword)
                     ->setRoles([Role::ROLE_SUBCONTRACTOR->value])
                    ->setEnabled(false);
                $entityManager->persist($user);
                $this->addFlash('success', 'Le sous-traitant a bien été ajouté');
                $notification = true;
            } else {
                if (!empty($form->getData()->getPlainPassword())){
                    $hashedPassword = $hasher->hashPassword($user, $form->getData()->getPlainPassword());
                    $user->setPassword($hashedPassword);
                }
                $this->addFlash('success', 'Le sous-traitant a bien été modifié');
                $notification = false;
            }
            $entityManager->flush();

            // dispatch the subonctractor.updated event
            $event = new SubContractorUpdatedEvent($user, $notification, false, false);
            $dispatcher->dispatch($event, SubContractorUpdatedEvent::NAME);

            if ($this->isGranted('ROLE_ADMIN')){
                return $this->redirectToRoute('sub_contractor_edit', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
            }else{
                //$event = new AdminNotifiedSubContractorCompletedEvent($user);
                //$dispatcher->dispatch($event, AdminNotifiedSubContractorCompletedEvent::NAME);
                return $this->redirectToRoute('my_profil', [], Response::HTTP_SEE_OTHER);
            }
        }

        $form2 = $this->createForm(ServiceType::class, $service, ['admin' => $this->isGranted('ROLE_ADMIN'), 'subContractor' => $user]);
        $form2->handleRequest($request);

        if ($form2->isSubmitted() && $form2->isValid()){
            $serviceId = $request->request->get('service')['serviceId'];
            $findService = $serviceRepository->findOneBy(['id' => $serviceId]);
            $servicesPerUser = $serviceRepository->findAllServiceByUser($user);
            $findProduct = $productRepository->findOneBy(['id' => $form2->getData()->getProduct()->getId()]);

            if (empty($findService)) {
                $service = new Service();

                $service->setPrice($form2->getData()->getPrice())
                        ->setProduct($form2->getData()->getProduct())
                        ->setResale($findProduct->getPrice())
                        ->setUser($user);

                foreach ($servicesPerUser as $serviceUser){
                    if ($serviceUser->getProduct() === $form2->getData()->getProduct()) {
                        $this->addFlash('error', 'Le service existe déjà pour cet utilisateur');

                        if ($this->isGranted('ROLE_ADMIN')){
                            return $this->redirectToRoute('sub_contractor_edit', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
                        }else{
                            return $this->redirectToRoute('my_profil', [], Response::HTTP_SEE_OTHER);
                        }
                    }
                }
                if ($user->getBillingMethod() == 2){
                    $event = new SubContractorServiceAddedEvent($user);
                    $dispatcher->dispatch($event, SubContractorServiceAddedEvent::NAME);
                }

                $this->addFlash('success', 'Le produit a bien été associé');
            } else {
                $service = $findService;

                foreach ($servicesPerUser as $serviceUser) {
                    if ($serviceUser->getProduct() !== $service->getProduct() && $serviceUser->getProduct() === $form2->getData()->getProduct()) {
                        $this->addFlash('error', 'Le service existe déjà pour cet utilisateur');

                        if ($this->isGranted('ROLE_ADMIN')){
                            return $this->redirectToRoute('sub_contractor_edit', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
                        }else{
                            return $this->redirectToRoute('my_profil', [], Response::HTTP_SEE_OTHER);
                        }
                    }
                }

                $service->setPrice($form2->getData()->getPrice())
                    ->setProduct($form2->getData()->getProduct())
                    ->setResale($findProduct->getPrice())
                    ->setUser($user);

                $this->addFlash('success', 'Le service a bien été modifié');
            }

            $entityManager->persist($service);
            $entityManager->flush();

            $subContractorCompanys = $user->getSubContractorCompanies();
            foreach ($subContractorCompanys as $subContractorCompany){
                $company = $subContractorCompany->getCompany();
                $currentMarge = $serviceService->checkService($service, $company);

                if ($currentMarge < 30){
                    $entityManager->remove($subContractorCompany);
                    $entityManager->flush();
                }
            }

            if ($this->isGranted('ROLE_ADMIN')){
                if (!$user->getIsActiveNotification()) {
                    $notification = true;
                    foreach ($user->getServices() as $service) {
                        if (empty($service->getResale()) || $service->getResale() < $service->getPrice()) {
                            $notification = false;
                        }
                    }

                    if ($notification) {
                        $email = $systemEmailRepository->findOneBy(['code' => SystemEmail::ACTIVATION_PRESTATAIRE]);

                        if (null !== $email) {
                            $notification = (new NotificationEmail())
                                ->from(new Address($email->getSender(), $email->getSenderName()))
                                ->to(new Address($user->getEmail()))
                                ->subject($shortcodeService->parse($email->getSubject(), $user))
                                ->content($shortcodeService->parse($email->getContent(), $user))
                                ->markAsPublic()
                            ;

                            try {
                                $mailer->send($notification);
                                $user->setIsActiveNotification(true);
                                $entityManager->persist($service);
                                $entityManager->flush();
                            } catch (\Exception $e) { /* TODO: logger ou afficher une alerte que l'email n'a pas été envoyé */ }
                        }
                    }
                }

                return $this->redirectToRoute('sub_contractor_edit', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
            }else{
                return $this->redirectToRoute('my_profil', [], Response::HTTP_SEE_OTHER);
            }
        }

        $handleCompanyJobsForm = $this->createForm(SubContractorCompanyType::class);

        return $this->renderForm('sub_contractor/handle.html.twig', [
            'form' => $form,
            'form2' => $form2,
            'user' => $user,
            'servicesSubContractor' => $servicesSubContractor,
            'service' => $this->getUser(),
            'handleCompanyJobsForm' => $handleCompanyJobsForm,
        ]);
    }

    /**
     * @param Service $service
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    #[Route('/service/{id}/supprimer', name: 'service_remove', methods: ['GET','POST'])]
    public function deleteService(Service $service, Request $request, EntityManagerInterface $entityManager)
    {
        if ($this->isCsrfTokenValid('delete'.$service->getId(), $request->request->get('_token'))) {
            $entityManager->remove($service);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le service a bien été supprimé'
            );
        }

        if ($this->isGranted('ROLE_ADMIN')){
            return $this->redirectToRoute('sub_contractor_edit', ['id' => $service->getUser()->getId()], Response::HTTP_SEE_OTHER);
        }else{
            return $this->redirectToRoute('my_profil', [], Response::HTTP_SEE_OTHER);
        }
    }

    /**
     * @param Service $service
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/service/{id}', name: 'service_action_get')]
    public function getService(Service $service, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse([
            'service' => json_decode($serializer->serialize($service, 'json', [
                AbstractNormalizer::ATTRIBUTES => [
                    'id',
                    'price',
                    'product' => [
                        'id'
                    ],
                    'resale',
                ]
            ])),
        ]);
    }

    #[Route('/api/subcontractors/{id}', name: 'api_edit_subcontractor', methods: ['POST'])]
    public function apiEditClient(User $user, Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher, JobRepository $jobRepository, ProductRepository $productRepository): JsonResponse
    {
        $user->setFirstname($request->request->get('firstname'));
        $user->setLastname($request->request->get('lastname'));
        $user->setEmail($request->request->get('email'));
        $user->setCellPhone($request->request->get('cellPhone'));
        $user->setEnabled(true);
        $user->setGender($request->request->get('gender'));

        $hashedPassword = $passwordHasher->hashPassword($user, $request->request->get('password'));
        $user->setPassword($hashedPassword);
        $user->setDailyRate($request->request->get('dailyRate'));

        if (!empty($request->request->get('jobs'))) {
            foreach ($request->request->get('jobs') as $job) {
                $job = $jobRepository->find($job);

                if (null !== $job) {
                    $user->addJob($job);
                }
            }
        }

        $prices = $request->request->get('prices');
        foreach ($prices as $productId => $price) {
            $product = $productRepository->findOneBy(['frontId' => $productId]);

            if (null !== $product) {
                $s = (new Service())
                    ->setUser($user)
                    ->setProduct($product)
                    ->setPrice($price);

                if ($product->getType() === ProductType::AU_FORFAIT) {
                    $s->setResale($product->getPrice());
                }

                $entityManager->persist($s);
            }
        }

        $entityManager->flush();

        $event = new SubContractorCompletedProfileEvent($user, true, true);
        $dispatcher->dispatch($event, SubContractorCompletedProfileEvent::NAME);

        $event = new SubContractorUpdatedEvent($user, false, true, false);
        $dispatcher->dispatch($event, SubContractorUpdatedEvent::NAME);

        return new JsonResponse(['result' => 'success']);
    }

    #[Route('/api/subcontractors/{id}/profile-picture', name: 'api_edit_subcontractor_profile_picture', methods: ['POST'])]
    public function apiEditClientProfilePicture(User $user, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!empty($request->files->get('picture'))) {
            $user->setPicture($request->files->get('picture'));
        }

        $entityManager->flush();

        return new JsonResponse(['result' => 'success']);
    }

    #[Route('/admin/fournisseurs/{subContractorId}/liens/{linkId}/supprimer', name: 'sub_contractor_delete_link', methods: ['GET'])]
    public function deleteLinkToCompany(string $subContractorId, string $linkId, UserRepository $userRepository, SubContractorCompanyRepository $subContractorCompanyRepository, EntityManagerInterface $entityManager, Request $request, EventDispatcherInterface $dispatcher): Response
    {
        $user = $userRepository->find($subContractorId);
        $link = $subContractorCompanyRepository->find($linkId);

        if (null === $user || null === $link) {
            throw new NotFoundHttpException();
        }

        $entityManager->remove($link);
        $entityManager->flush();

        $event = new SubContractorUpdatedEvent($user);
        $dispatcher->dispatch($event, SubContractorUpdatedEvent::NAME);

        $this->addFlash(
            type: 'success',
            message: 'L\'association entre le sous-traitant et l\'entreprise a bien été supprimée',
        );

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/fournisseurs/{subContractor}/jobs/{jobs}', name: 'subcontractor_edit_jobs', methods: ['POST'])]
    public function editCompanyJobs(User $subContractor, SubContractorCompany $jobs, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $form = $this->createForm(SubContractorCompanyType::class, $jobs);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                type: 'success',
                message: 'L\'association au client a bien été mis à jour',
            );
        } elseif ($form->isSubmitted()) {
            $this->addFlash(
                type: 'error',
                message: 'Une erreur a eu lieu, merci de réessayer',
            );
        }

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/fournisseur/remove/{id}', name: 'sub_contractor_remove', methods: ['POST','GET'])]
    public function removeSubContractor(Request $request,User $user ,EntityManagerInterface $entityManager, ServiceRepository $serviceRepository, MissionParticipantRepository $missionParticipantRepository, HistoriqueRepository $historiqueRepository, MessageRepository $messageRepository, SubContractorCompanyRepository $subContractorCompanyRepository)
    {
        $services = $serviceRepository->findBy(['user' => $user]);
        $missionsParticipants = $missionParticipantRepository->findBy(['user' => $user]);
        $historiques = $historiqueRepository->findBy(['user' => $user]);
        $messages = $messageRepository->findBy(['user' => $user]);
        $subContractorsCompany = $subContractorCompanyRepository->findBy(['user' => $user]);

        foreach ($services as $service){
            $entityManager->remove($service);
        }

        foreach ($missionsParticipants as $missionParticipant){
            $entityManager->remove($missionParticipant);
        }

        foreach($historiques as $historique){
            $entityManager->remove($historique);
        }

        foreach ($messages as $message){
            $entityManager->remove($message);
        }

        foreach ($subContractorsCompany as $subContractorCompany){
            $entityManager->remove($subContractorCompany);
        }
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash(
            type: 'success',
            message: 'Votre compte à bien été supprimé',
        );

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/admin/fournisseurs/{id}/renvoie-email-inscription', name: 'sub_contractor_resend_registration_email', methods: ['GET','POST'])]
    public function resendRegistrationEmail(Request $request, User $user, EventDispatcherInterface $dispatcher)
    {
        $event = new SubContractorUpdatedEvent($user, true);
        $dispatcher->dispatch($event, SubContractorUpdatedEvent::NAME);

        $this->addFlash(
            type: 'success',
            message: 'L\'email d\'inscription a bien été envoyé',
        );

        return $this->redirect($request->headers->get('referer'));
    }
}
