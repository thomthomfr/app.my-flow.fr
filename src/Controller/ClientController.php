<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\Role;
use App\Event\ClientUpdatedEvent;
use App\Form\ClientProfilType;
use App\Form\ClientType;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Json;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\UserRepository;
use App\Service\UserService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use GuzzleHttp\Client;
use App\Event\ClientDeleteWpEvent;

class ClientController extends AbstractController
{
    /**
     * @param UserRepository $userRepository
     * @return Response
     */
    #[Route('/admin/clients', name: 'client_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $role = Role::ROLE_CLIENT->value;
        $observer = Role::ROLE_OBSERVER->value;
        $roleClientAdmin = Role::ROLE_CLIENT_ADMIN->value;
        return $this->render('client/index.html.twig', [
            'clients' => $userRepository->findByRoleClients($role, $observer, $roleClientAdmin),
        ]);
    }

    /**
     * @param User|null $user
     * @param Request $request
     * @param UserService $userService
     * @param UserPasswordHasherInterface $hasher
     * @return Response
     */
    #[Route('/admin/client/ajouter', name: 'client_new', methods: ['GET','POST'])]
    #[Route('/admin/client/{id}', name: 'client_edit', methods: ['GET','POST'])]
    public function handleClient(User $user = null, Request $request, UserService $userService, UserPasswordHasherInterface $hasher, EventDispatcherInterface $dispatcher): Response
    {
        if ($user === null){
            $user = new User();
        }

        $form = $this->createForm(ClientType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            if ($request->get('_route') === 'client_new') {
                if ($form->getData()->getRoles()[0] == 'ROLE_CLIENT_ADMIN'){
                    $role = Role::ROLE_CLIENT_ADMIN->value;
                }else{
                    $role = Role::ROLE_CLIENT->value;
                }
                $password = $userService->generatePassword();
                $hashedPassword = $hasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword)
                     ->setRoles([$role])
                    ->setEnabled(false);
                $entityManager->persist($user);
                $this->addFlash('success', 'Le client a bien été ajouté');
                $notification = true;
            } else {
                $this->addFlash('success', 'Le client a bien été modifié');
                $notification = false;
            }
            $entityManager->flush();

            $event = new ClientUpdatedEvent($user, $notification);
            $dispatcher->dispatch($event, ClientUpdatedEvent::NAME);

            return $this->redirectToRoute('client_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->renderForm('client/handle.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    /**
     * @param Request $request
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    #[Route('/api/clients/search', name: 'api_clients_search')]
    public function apiSearch(Request $request, UserRepository $userRepository)
    {
        $query = $request->query->get('query');
        if ($request->query->get('client') == 1){
            $role = 'ROLE_CLIENT';
        }else{
            $role = 'ROLE_SUBCONTRACTOR';
        }

        return new JsonResponse([
            'clients' => $userRepository->apiQuerySearch($query, $role),
        ]);
    }

    #[Route('/api/clients/{id}', name: 'api_clients', methods: ['GET'])]
    public function apiClients(User $user, SerializerInterface $serializer): JsonResponse
    {
        if ($user->isEnabled()) {
            return new JsonResponse([
                'alreadyEnabled' => true,
            ]);
        }

        return new JsonResponse(
            $serializer->serialize($user, 'json', [AbstractNormalizer::ATTRIBUTES => [
                'lastname', 'firstname', 'email', 'cellPhone', 'billingMethod', 'gender',
            ]]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/clients/{id}', name: 'api_edit_client', methods: ['POST'])]
    public function apiEditClient(User $user, Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher): JsonResponse
    {
        $user->setFirstname($request->request->get('firstname'));
        $user->setLastname($request->request->get('lastname'));
        $user->setEmail($request->request->get('email'));
        $user->setCellPhone($request->request->get('cellPhone'));
        $user->setEnabled(true);
        $user->setGender($request->request->get('gender'));

        $hashedPassword = $passwordHasher->hashPassword($user, $request->request->get('password'));
        $user->setPassword($hashedPassword);

        $entityManager->flush();

        $event = new ClientUpdatedEvent($user, false, $request->request->get('password'), true);
        $dispatcher->dispatch($event, ClientUpdatedEvent::NAME);

        return new JsonResponse(['result' => 'success']);
    }

    #[Route('/admin/client/{id}/{availabilty<enable|disable>}', name: 'client_toggle_availabilty')]
    public function toggleAvailability(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setEnabled(!$user->isEnabled());
        $entityManager->flush();

        $this->addFlash(
            type: 'success',
            message: 'Le client '.$user.' a bien été '. ($user->isEnabled() ? 'activé' : 'désactivé')
        );

        return $this->redirectToRoute('client_index');
    }

    #[Route('/admin/client/{id}/invitation', name: 'client_send_another_invitation')]
    public function sendAnotherInvitation(EventDispatcherInterface $dispatcher, User $user): Response
    {
        $event = new ClientUpdatedEvent($user, true, null, false, false);
        $dispatcher->dispatch($event, ClientUpdatedEvent::NAME);

        $this->addFlash(
            type: 'success',
            message: 'L\'email d\'invitation a bien été envoyé'
        );

        return $this->redirectToRoute('client_index');
    }

    #[Route('/admin/client/{id}/supprimer', name: 'client_remove', methods: ['GET','POST'])]
    public function deleteClient(User $user, EntityManagerInterface $entityManager,EventDispatcherInterface $dispatcher)
    {
        $user->setDeleted(true);
        $user->setEnabled(false);
        $entityManager->flush();
        $event = new ClientDeleteWpEvent($user, true);
        $dispatcher->dispatch($event, ClientDeleteWpEvent::NAME);

        $this->addFlash(
            'success',
            'Le client a bien été supprimé'
        );

        return $this->redirectToRoute('client_index');
    }

    #[Route('/mon-profil-client', name: 'my_profil_client', methods: ['GET','POST'])]
    public function Profil(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(ClientProfilType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            if (!empty($form->getData()->getPlainPassword())){
                $hashedPassword = $hasher->hashPassword($user, $form->getData()->getPlainPassword());
                $user->setPassword($hashedPassword);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre profil a bien été modifié'
            );

            return $this->redirectToRoute('my_profil_client');
        } elseif ($form->isSubmitted()) {
            $this->addFlash(
                'error',
                'Merci de corriger les erreurs',
            );
        }

        return $this->renderForm('client/profil.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/client/{id}/renvoie-email-inscription', name: 'client_resend_registration_email', methods: ['GET','POST'])]
    public function resendRegistrationEmail(Request $request, User $user, EventDispatcherInterface $dispatcher)
    {
        $event = new ClientUpdatedEvent($user, true);
        $dispatcher->dispatch($event, ClientUpdatedEvent::NAME);
        $this->addFlash(
            type: 'success',
            message: 'L\'email d\'inscription a bien été envoyé',
        );

        return $this->redirect($request->headers->get('referer'));
    }
}
