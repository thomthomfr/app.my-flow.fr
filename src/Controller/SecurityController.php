<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils, Request $request, SessionInterface $session): Response
    {
        
        if (null !== $request->query->get('redirect_to')) {
            $session->set('redirect_to', $request->query->get('redirect_to'));

            return $this->redirectToRoute('app_login');
        }

        if ($this->getUser()) {
            if (null !== $redirect = $session->get('redirect_to')) {
                $session->remove('redirect_to');

                return $this->redirect($redirect.'?tsso='.hash('sha256', $this->getUser()->getEmail().$this->getUser()->getEmail()));
            }

            return $this->redirectToRoute('mission_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
