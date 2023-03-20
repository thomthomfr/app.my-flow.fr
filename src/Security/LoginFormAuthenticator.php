<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private UrlGeneratorInterface $urlGenerator;
    private EntityManagerInterface $entityManager;
    private SessionInterface $session;
    private HttpClientInterface $httpClient;
    private ParameterBagInterface $parameterBag;

    public function __construct(UrlGeneratorInterface $urlGenerator, EntityManagerInterface $entityManager, SessionInterface $session, HttpClientInterface $httpClient, ParameterBagInterface $parameterBag)
    {
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->httpClient = $httpClient;
        $this->parameterBag = $parameterBag;
    }

    public function authenticate(Request $request): PassportInterface
    {
        $email = $request->request->get('email', '');

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $request->request->get('email')]);
        if(null !== $user && $user->isEnabled() == false){
            throw new CustomUserMessageAuthenticationException('Votre compte n\'est plus actif, veuillez contacter l\'administrateur');
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        //Blocked SSO if admin 
        if (in_array("ROLE_ADMIN",$token->getUser()->getRoles() ) || in_array("ROLE_SUBCONTRACTOR",$token->getUser()->getRoles() ) ) {
            return new RedirectResponse($this->urlGenerator->generate('mission_index'));
        }
        
        $response = $this->httpClient->request('GET', $this->parameterBag->get('front_website_url'), [
            'query' => [
                'tsso' => hash('sha256', $token->getUser()->getEmail().$token->getUser()->getEmail()),
            ],
            'max_redirects' => 0,
        ]);

        $headers = $response->getHeaders(false);
        foreach ($headers['set-cookie'] ?? [] as $cookie) {
            $infos = explode(';', $cookie);
            [$name, $value] = explode('=', $infos[0]);

            foreach ($infos as $info) {
                if (preg_match('#path#', $info)) {
                    [$str, $path] = explode('=',$info);
                }
            }

            setrawcookie($name, $value, 0, $path ?? '', str_replace('https://', '', $this->parameterBag->get('front_website_url')));
        }

        if (null !== $redirect = $this->session->get('redirect_to')) {
            $this->session->remove('redirect_to');

            return new RedirectResponse($redirect.'?tsso='.hash('sha256', $token->getUser()->getEmail().$token->getUser()->getEmail()));
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('mission_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
