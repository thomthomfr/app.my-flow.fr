<?php

namespace App\Controller;

use App\Entity\Job;
use App\Event\Job\JobCreatedEvent;
use App\Event\Job\JobDeletedEvent;
use App\Event\Job\JobUpdatedEvent;
use App\Form\JobType;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class JobController extends AbstractController
{
    /**
     * Displays all the jobs
     *
     * @param Request $request
     * @param JobRepository $jobRepository
     *
     * @return Response - template job/index.html.twig
     */
    #[Route('/admins/metiers', name: 'job_index', methods: ['GET','POST'])]
    public function index(Request $request, JobRepository $jobRepository): Response
    {
        return $this->render('job/index.html.twig', [
            'jobs' => $jobRepository->findBy([], ['name' => 'DESC']),
        ]);
    }

    /**
     * Add or edit a Job
     *
     * @param Request $request
     * @param Job|null $job The job to edit, if not null
     * @param EntityManagerInterface $entityManager
     * @param EventDispatcherInterface $dispatcher
     *
     * @return Response - template job/handle.html.twig
     */
    #[Route('/admin/metiers/ajouter', name: 'job_new', methods: ['GET','POST'])]
    #[Route('/admin/metiers/{id}', name: 'job_edit', methods: ['GET','POST'])]
    public function handle(Request $request, Job $job = null, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher): Response
    {
        if (null == $job){
            $job = new Job();
        }

        $form = $this->createForm(JobType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            if($request->get('_route') === 'job_new') {
                $entityManager->persist($job);

                $event = new JobCreatedEvent($job);
                $dispatcher->dispatch($event, JobCreatedEvent::NAME);

                $this->addFlash('success', 'Le métier a bien été ajouté');
            }else{
                $event = new JobUpdatedEvent($job);
                $dispatcher->dispatch($event, JobUpdatedEvent::NAME);

                $this->addFlash('success', 'Le métier a bien été modifié');
            }
            $entityManager->flush();

            return $this->redirectToRoute('job_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('job/handle.html.twig', [
            'job' => $job,
            'form' => $form,
        ]);
    }

    /**
     * Delete a job
     *
     * @param Job $job - The job to delete
     * @param EntityManagerInterface $entityManager
     * @param EventDispatcherInterface $dispatcher
     * @return RedirectResponse
     */
    #[Route('/admin/metiers/{id}/supprimer', name: 'job_delete', methods: ['GET','POST'])]
    public function delete(Job $job, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher)
    {
        $entityManager->remove($job);
        $entityManager->flush();

        $event = new JobDeletedEvent($job);
        $dispatcher->dispatch($event, JobDeletedEvent::NAME);

        $this->addFlash('success', 'Le métier a bien été supprimé');
        return $this->redirectToRoute('job_index', [], Response::HTTP_SEE_OTHER);
    }
}
