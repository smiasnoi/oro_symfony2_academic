<?php

namespace BugTrackerBundle\Controller;

use BugTrackerBundle\Entity\Project;
use BugTrackerBundle\Repository\ProjectRepository;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use BugTrackerBundle\Helper\Pagination;
use BugTrackerBundle\Form\Project\EditType as ProjectForm;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ProjectController extends Controller
{
    /**
     * @Route("/project/{id}", name="project_view", requirements={
     *     "id": "\d+"
     * })
     */
    public function viewAction(Request $request, Project $project)
    {
        $this->denyAccessUnlessGranted('handle', $project);
        return $this->render('BugTrackerBundle:project:view.html.twig', ['project' => $project]);
    }

    /**
     * @Route("/issue/edit/{id}", name="project_edit", requirements={
     *     "id": "\d+"
     * })
     */
    public function editAction(Request $request, Project $project)
    {
        // @TODO implement new issue form for storie's subtask creation
        return new Response();
    }

    /**
     * @Route("/project/new", name="project_new")
     */
    public function createAction(Request $request)
    {
        $project = new Project();
        $this->denyAccessUnlessGranted('handle', $project);

        $form = $this->createForm(
            ProjectForm::class, $project,
            ['validation_groups' => ['Default'], 'required' => false]
        );
        $form->add('create', SubmitType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $project->addMember($this->getUser());
            $em->persist($project);
            $em->flush();

            return $this->redirectToRoute('project_view', ['id' => $project->getId()]);
        }

        return $this->render(
            'BugTrackerBundle:project:new.html.twig',
            [
                'form' => $form->createView(),
                'route_group' => 'projects'
            ]
        );
    }

    /**
     * @Route("/project/list", name="projects_list")
     */
    public function listAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $user = $this->isGranted('ROLE_MANAGER') ? null : $this->getUser();
        $page = (int)$request->query->get(ProjectRepository::PAGE_VAR) ?: 1;
        $pagination = [ProjectRepository::KEY_PAGE => $page];

        return $this->render(
            'BugTrackerBundle:project:list.html.twig',
            ['projects' => $em->getRepository('BugTrackerBundle:Project')->findAllByUser($user, $pagination)]
        );
    }
}
