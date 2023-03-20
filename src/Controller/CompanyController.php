<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\SubContractorCompany;
use App\Entity\User;
use App\Entity\CreditHistory;
use App\Enum\BillingMethod;
use App\Event\ClientUpdatedEvent;
use App\Event\CompanyUpdatedEvent;
use App\Event\SubContractor\SubContractorReferencedEvent;
use App\Event\SubContractorUpdatedEvent;
use App\Form\CompanyType;
use App\Form\SubContractorCompanyType;
use App\Form\AddCreditCompanyType;
use App\Repository\CampaignRepository;
use App\Repository\CompanyRepository;
use App\Repository\CreditHistoryRepository;
use App\Repository\ServiceRepository;
use App\Repository\SubContractorCompanyRepository;
use App\Repository\UserRepository;
use App\Service\ServiceService;
use App\Service\UserService;
use App\Service\CreditService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/admin')]
class CompanyController extends AbstractController
{
    /**
     * @param CompanyRepository $companyRepository
     * @return Response template company/index.html.twig
     */
    #[Route('/entreprises', name: 'company_index', methods: ['GET'])]
    public function index(CompanyRepository $companyRepository): Response
    {
        return $this->render('company/index.html.twig', [
            'companys' => $companyRepository->findBy([], ['name' => 'DESC']),
        ]);
    }

    /**
     * @param User|null $user
     * @param CreditHistory|null $creditHistory
     * @param CreditService $creditService
     * @param Company|null $company
     * @param Request $request
     * @param UserService $userService
     * @param UserPasswordEncoderInterface $encoder
     * @param UserRepository $userRepository
     * @param CreditHistoryRepository $creditHistoryRepository
     * @param CampaignRepository $campaignRepository
     * @param ParameterBagInterface $parameter
     *
     * @return Response template company/handle.html.twig
     */
    #[Route('/entreprise/ajouter', name: 'company_new', methods: ['GET','POST'])]
    #[Route('/entreprise/{id}', name: 'company_edit', methods: ['GET','POST'])]
    public function handleCompany(SubContractorCompany $subContractorCompany = null, User $user = null, CreditHistory $creditHistory = null, CreditService $creditService, Company $company = null, Request $request, UserService $userService, UserPasswordEncoderInterface $encoder, UserRepository $userRepository, CreditHistoryRepository $creditHistoryRepository, CampaignRepository $campaignRepository, SerializerInterface $serializer, EmailService $emailService, EntityManagerInterface $entityManager, SubContractorCompanyRepository $subContractorCompanyRepository, EventDispatcherInterface $dispatcher, ValidatorInterface $validator, ServiceRepository $serviceRepository, ServiceService $serviceService,ParameterBagInterface $parameter): Response
    {
        
        if (null == $company){
            $company = new Company();
            $listClients = null;
            $listSubContractors = null;
            $listServices = null;
            $creditHistory = null;
            $allCredit = 0;
            $roleSubContractor = 'ROLE_SUBCONTRACTOR';
        } else {
            $roleCLient = 'ROLE_CLIENT';
            $roleCLientAdmin = 'ROLE_CLIENT_ADMIN';
            $roleSubContractor = 'ROLE_SUBCONTRACTOR';
            $thisCompany = $company->getId();
            $listClients = $userRepository->findClientByCompany($thisCompany, $roleCLient, $roleCLientAdmin);
            $listSubContractors = $subContractorCompanyRepository->findBy(['company' => $company]);
//            $listServices = $serviceRepository->findServiceByUser($thisCompany);
            $creditAvailable = $creditService->CreditAvailable($company);
            $allCredit = 0;
            foreach ($creditAvailable as $credit){
                $allCredit += $credit->getCredit();
            }

            $creditsHistoryCompany = $creditHistoryRepository->findBy(['company' => $company]);
            $campainsHistory = $campaignRepository->findBy(['company' => $company]);

            $sorted = [];

            foreach ($creditsHistoryCompany as $creditHistoryCompany){
                $date = $creditHistoryCompany->getCreatedAt()->format('YmdHis');
                $sorted[$date] = $creditHistoryCompany;
            }

            foreach ($campainsHistory as $campainHistory){
                $date = $campainHistory->getCreatedAt()->format('YmdHis');
                $sorted[$date] = $campainHistory;
            }
            ksort($sorted);
            $sorted = array_reverse($sorted);
        }

        /**
         * Form for add company
         */
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($request->get('_route') === 'company_new') {
                $company->setCreatedAt(new \DateTime());
                $entityManager->persist($company);
                $this->addFlash('success', 'L\'entreprise a bien été ajouté');
            }else{
                $company->setUpdatedAt(new \DateTime());
                $this->addFlash('success', 'L\'entreprise a bien été modifié');
            }
            $entityManager->flush();

            // dispatch the company.update event
            $event = new CompanyUpdatedEvent($company);
            $dispatcher->dispatch($event, CompanyUpdatedEvent::NAME);

            return $this->redirectToRoute('company_index', [], Response::HTTP_SEE_OTHER);
        }

        /**
         * Form for add subContractor that does not exist to company
         */
        $form2 = $this->createForm(SubContractorCompanyType::class, $user);
        $form2->handleRequest($request);

        $allSubContractorsEmails = $userRepository->findByRole($roleSubContractor);
        if ($form2->isSubmitted() && $form2->isValid()){
            $email = $request->request->get('emailSubContractor');

            if ($validator->validate($email, [new Email()])->count() > 0) {
                $this->addFlash('error', 'L\'adresse '.$email.' n\'est pas valide. Nous vous invitons à vérifier votre saisie');

                return $this->redirectToRoute('company_edit', ['id' => $company->getId()], Response::HTTP_SEE_OTHER);
            }

            $checkEmail = $userRepository->findOneBy(['email' => $email]);
            $subContractorCompany = new  SubContractorCompany();

            if (empty($checkEmail)) {
                $user = new User();
                $password = $userService->generatePassword();
                $encodedPassword = $encoder->encodePassword($user, $password);
                $user->setPassword($encodedPassword);
                $user->setEmail($email);
                $user->setRoles([$roleSubContractor]);
                $user->setEnabled(false);
            } else {
                $user = $userRepository->findOneBy(['email' => $email]);
            }

            if ($request->query->get('perte') == null) {
                $services = $serviceRepository->findBy(['user' => $checkEmail]);
                foreach ($form2->getData()->getProducts() as $productName) {
                    $name = $productName->getId();
                }
                foreach ($form2->getData()->getJobs() as $jobName){
                    $nameJob = $jobName->getId();
                }

                if (!empty($user->getResaleRate() || empty($checkEmail))) {
                    $flag = false;
                    foreach ($services as $service){
                        if ($service->getProduct()->getId() === $name){
                            $flag = true;
                        }
                    }
                    if ($flag !== true){
                        $this->addFlash('error', 'Veuillez ajouter le produit et le prix pratique par ce partenaire au niveau de sa fiche.');
                        return $this->redirectToRoute('company_edit', ['id' => $company->getId()], Response::HTTP_SEE_OTHER);
                    }

                    if ($user->getBillingMethod() == 2){
                        foreach ($services as $service) {
                            if ($user->isEnabled()){
                                if ($service->getProduct()->getId() === $name) {
                                    $currentMarge = $serviceService->checkService($service, $company);
                                    if ($currentMarge < 30) {
                                        return $this->redirectToRoute('company_edit', ['id' => $company->getId(), 'perte' => $currentMarge, 'email' => $email, 'product' => $name, 'job' => $nameJob], Response::HTTP_SEE_OTHER);
                                    }
                                }
                            }else{
                                $this->addFlash('error', 'Ce partenaire a un profil incomplet, nous vous invitons à vous rendre sur sa fiche pour compléter son profil et l’activer');
                                return $this->redirectToRoute('company_edit', ['id' => $company->getId()], Response::HTTP_SEE_OTHER);
                            }
                        }
                    }else{
                        $currentMarge = ($user->getResaleRate() / $user->getDailyRate() -1) *100;
                        if ($currentMarge < 30) {
                            return $this->redirectToRoute('company_edit', ['id' => $company->getId(), 'perte' => $currentMarge, 'email' => $email, 'product' => $name, 'job' => $nameJob, 'billing' => 1], Response::HTTP_SEE_OTHER);
                        }
                    }
                } else {
                    $this->addFlash('error', 'Le tarif revente doit obligatoirement être renseigné. Nous vous invitons à vous rendre sur la fiche du partenaire pour compléter son profil.');
                    return $this->redirectToRoute('company_edit', ['id' => $company->getId()], Response::HTTP_SEE_OTHER);
                }
            }

            $verifyEmailSend = $subContractorCompanyRepository->findBy(['user' => $user, 'company' => $company]);
            $flagSend = false;
            foreach ($verifyEmailSend as $verify){
                if ($verify->getEmailSend() == true){
                    $flagSend = true;
                }
            }

            foreach ($form2->getData()->getJobs() as $job){
                $subContractorCompany->addJob($job);
            }
            foreach ($form2->getData()->getProducts() as $product){
                $subContractorCompany->addProduct($product);
            }

            $subContractorCompany->setCompany($company)
                                 ->setUser($user)
                                 ->setEmailSend(true);
            $user->addSubContractorCompany($subContractorCompany);
            if (!empty($listSubContractors)){
                foreach ($listSubContractors as $subContractor){
                    $productUser = [];
                    foreach ($subContractor->getProducts() as $product){
                        $productUser[] = $product->getName();
                    }
                    foreach ($form2->getData()->getProducts() as $item){
                        if (in_array($item->getName(), $productUser)) {
                            $this->addFlash('error', 'Un sous traitant est déjà associé à cette entreprise pour ce produit. Nous vous invitons à retirer le partenaire actuel avant d\'en ajouter un nouveau');
                            return $this->redirectToRoute('company_edit', ['id' => $company->getId()], Response::HTTP_SEE_OTHER);
                        }
                    }
                }
                $entityManager->persist($user);
                $entityManager->persist($subContractorCompany);
                $entityManager->flush();
            }else{
                $entityManager->persist($user);
                $entityManager->persist($subContractorCompany);
                $entityManager->flush();
            }

            $event = true;
            foreach ($user->getServices() as $service) {
                if (empty($service->getResale())) {
                    $event = false;
                }
            }

            if ($event && $flagSend == false) {
                $event = new SubContractorReferencedEvent($user);
                $dispatcher->dispatch($event, SubContractorReferencedEvent::NAME);
            }

            // dispatch the subonctractor.updated event
            $event = new SubContractorUpdatedEvent($user, empty($checkEmail));
            $dispatcher->dispatch($event, SubContractorUpdatedEvent::NAME);

            $this->addFlash('success', 'L\'utilisateur a bien été ajouté');

            return $this->redirectToRoute('company_edit', ['id' => $company->getId()], Response::HTTP_SEE_OTHER);
        }

        /**
         * Add credit for company
         */
        $form4 = $this->createForm(AddCreditCompanyType::class, $creditHistory);
        $form4->handleRequest($request);

        if ($form4->isSubmitted() && $form4->isValid()){
            $nbCredits = $form4->getData()->getCredit();
            $typePack = $form4->getData()->getTypePack();
            $dateStartContract = $form4->getData()->getStartDateContract();

            $creditHistory =  new CreditHistory();
            $creditHistory->setCompany($company)
                          ->setName('Pack de '.$nbCredits.' crédit')
                          ->setCredit($nbCredits)
                          ->setTypePack($typePack)
                          ->setAnnuite($form4->getData()->getAnnuite())
                          ->setMensualite($form4->getData()->getMensualite())
                          ->setReport($form4->getData()->getReport())
                          ->setStartDateContract($dateStartContract)
                          ->setCost($company->getCostOfDiscountedCredit() * $nbCredits)
                          ->setOrderedBy($this->getUser())
                          ->setCreatedAt(new \DateTime());

            if ($typePack == 0){
                $reportInMonth = $form4->getData()->getReport();
                $expireAt = new \DateTime($dateStartContract->add(new \DateInterval('P'.$reportInMonth.'M'))->format('Y-m-d'));
                $creditHistory->setCreditExpiredAt($expireAt);
                $company->addToBalance($nbCredits);
            }elseif ($typePack == 1){
                $expireAt = new \DateTime($dateStartContract->add(new \DateInterval('P1M'))->format('Y-m-d'));
                $creditHistory->setCreditExpiredAt($expireAt);
                $company->addToBalance($form4->getData()->getMensualite());
            }elseif ($typePack == 2){
                $expireAt = new \DateTime($dateStartContract->add(new \DateInterval('P1Y'))->format('Y-m-d'));
                $creditHistory->setCreditExpiredAt($expireAt);
                $company->addToBalance($form4->getData()->getAnnuite());
            }

            $entityManager->persist($company);
            $entityManager->persist($creditHistory);
            $entityManager->flush();
            $this->addFlash('success', 'Les crédits on bien été ajouté');
            return $this->redirectToRoute('company_edit', ['id' => $company->getId()], Response::HTTP_SEE_OTHER);
        }

        $clients = $userRepository->findBy([],['email' => 'ASC']);
        $mailClientToCommand = "";
        $listClients = $userRepository->findClientAdminByCompany($thisCompany, $roleCLientAdmin);
        if (!empty($listClients)) {
            foreach ($listClients as $client) {
                if (($client->isEnabled())) {
                    $mailClientToCommand = $client->getEmail();
                    break;
                }
            }
            
        }
        
        $orderAsLink = !empty($mailClientToCommand) ? $parameter->get('url_redirect_to_front').'?tsso='.hash('sha256', $mailClientToCommand.$mailClientToCommand) : null;
        return $this->renderForm('company/handle.html.twig', [
            'form' => $form,
            'form2' => $form2,
            'form4' => $form4,
            'company' => $company,
            'listClients' => $listClients,
            'listSubContractors' => $listSubContractors,
            'listServices' => $listServices ?? [],
            'allCredit' => $allCredit,
            'clients' => $serializer->serialize($clients, 'json', [AbstractNormalizer::ATTRIBUTES => ['email']]),
            'sorted' => $sorted ?? [],
            'allSubContractorsEmails' => $serializer->serialize($allSubContractorsEmails, 'json', [AbstractNormalizer::ATTRIBUTES => ['email']]),
            'orderAsLink' => $orderAsLink
        ]);
    }

    /**
     * @param Request $request
     * @param UserRepository $userRepository
     * @param CompanyRepository $companyRepository
     * @param EmailService $emailService
     * @param EntityManagerInterface $entityManager
     * @param UserService $userService
     * @param UserPasswordEncoderInterface $encoder
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    #[Route('/client-company/ajouter', name: 'company_add_client', methods: ['GET','POST'])]
    public function addClientCompany(Request $request, UserRepository $userRepository, CompanyRepository $companyRepository, EntityManagerInterface $entityManager, UserService $userService, UserPasswordHasherInterface $passwordHasher, EventDispatcherInterface $dispatcher, ValidatorInterface $validator)
    {
        /**
         * We get the user and the company in the query and then register them
         */
        $user = $userRepository->findOneBy(['email' => $request->query->get('email')]);
        $company = $companyRepository->findOneBy(['id' => $request->query->get('company')]);
        $email = $request->query->get('email');

        if (!empty($user)){
            $user->setCompany($company);
            $user->setRoles(['ROLE_CLIENT']);
            $entityManager->persist($user);
        }else{
            $user = new User();
            $password = $userService->generatePassword();
            $encodedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($encodedPassword);
            $user->setEmail($email);
            $user->setRoles(['ROLE_CLIENT']);
            $user->setEnabled(false);
            $user->setCompany($company);
        }

        if ($validator->validate($email, [new Email()])->count() > 0) {
            $this->addFlash('error', 'L\'adresse '.$email.' n\'est pas valide. Nous vous invitons à vérifier votre saisie');

            return $this->redirectToRoute('company_edit', ['id' => $company->getId()], Response::HTTP_SEE_OTHER);
        }else{
            $event = new ClientUpdatedEvent($user, true);
            $dispatcher->dispatch($event, ClientUpdatedEvent::NAME);
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Le client à bien été ajouté');
        }

        return $this->redirectToRoute('company_edit', ['id' => $company->getId()], Response::HTTP_SEE_OTHER);
    }

    /**
     * @param Request $request
     * @param UserRepository $userRepository
     * @param CompanyRepository $companyRepository
     * @param EmailService $emailService
     * @param EntityManagerInterface $entityManager
     * @param UserService $userService
     * @param UserPasswordEncoderInterface $encoder
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    #[Route('/subcontractor-company/ajouter', name: 'company_add_subcontractor', methods: ['GET','POST'])]
    public function addSubContractorCompany(Request $request, SubContractorCompany $subContractorCompany = null, UserRepository $userRepository, CompanyRepository $companyRepository, EmailService $emailService, EntityManagerInterface $entityManager, UserService $userService, UserPasswordEncoderInterface $encoder)
    {
        /**
         * We get the subContractor and the company in the query and then register them
         */
        $subContractorCompany = new SubContractorCompany();
        $user = $userRepository->findOneBy(['email' => $request->query->get('email')]);
        $company = $companyRepository->findOneBy(['id' => $request->query->get('company')]);
        $subContractorCompany->setCompany($company)
                             ->setUser($user);
        $subContractorCompany->addJob();
        $subContractorCompany->addProduct();
        $user->setRoles(['ROLE_SUBCONTRACTOR']);

        $entityManager->persist($user);
        $entityManager->persist($subContractorCompany);
        $entityManager->flush();
        $this->addFlash('success', 'Le sous-traitant à bien été ajouté');

        return $this->redirectToRoute('company_edit', ['id' => $company->getId()], Response::HTTP_SEE_OTHER);
    }

}
