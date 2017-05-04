<?php

namespace BugTrackerBundle\Controller;

use BugTrackerBundle\Entity\Project;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use BugTrackerBundle\Helper\Pagination;
use BugTrackerBundle\Form\Project\EditType as ProjectForm;

class ProjectController extends AbstractController
{
    CONST ACTIVITIES_PAGE_SIZE = 10;
    CONST ACTIVITIES_PAGE_VAR = 'acp';

    CONST MEMBERS_PAGE_SIZE = 10;
    CONST MEMBERS_PAGE_VAR = 'up';

    CONST PROJECTS_PAGE_SIZE = 16;
    CONST PROJECTS_PAGE_VAR = 'p';

    /**
     * @Route("/project/{id}", name="project_view", requirements={
     *     "id": "\d+"
     * })
     * @Method({"GET"})
     */
    public function viewAction(Request $request, Project $project)
    {
        $this->checkIfUserCanHandleProject('ROLE_MANAGER', $project);

        $queryParams = (array)$request->query->all();
        $queryParams['id'] = $project->getId();
        $helper = new Pagination($this->container, $request);

        $criteria = new Criteria();

        $members = $project->getMembers();
        $membersTotalPages = ceil($members->count() / self::MEMBERS_PAGE_SIZE);
        $membersPagesInfo = $helper->getPrevNextUrls(self::MEMBERS_PAGE_VAR, $membersTotalPages, 'project_view', $queryParams);
        $membersPage = $membersPagesInfo['current_page'];
        $offset = ($membersPage - 1) * self::MEMBERS_PAGE_SIZE;
        $criteria->setFirstResult($offset)
            ->setMaxResults(self::MEMBERS_PAGE_SIZE);
        $filteredMembers = $members->matching($criteria);

        $activities = $project->getActivities();
        $activitiesTotalPages = ceil($activities->count() / self::ACTIVITIES_PAGE_SIZE);
        $activitiesPagesInfo = $helper->getPrevNextUrls(self::ACTIVITIES_PAGE_VAR, $activitiesTotalPages, 'project_view', $queryParams);
        $activitiesPage = $activitiesPagesInfo['current_page'];
        $offset = ($activitiesPage - 1) * self::ACTIVITIES_PAGE_SIZE;
        $criteria->setFirstResult($offset)
            ->setMaxResults(self::ACTIVITIES_PAGE_SIZE)
            ->orderBy(['createdAt' => Criteria::DESC]);
        $filteredActivities = $activities->matching($criteria);

        return $this->render(
            'BugTrackerBundle:project:view.html.twig',
            [
                'project' => $project,
                'activities' => array_merge(['items' => $filteredActivities], $activitiesPagesInfo),
                'members' => array_merge(['items' => $filteredMembers], $membersPagesInfo)
            ]
        );
    }


    /**
     * @Route("/issue/edit/{id}", name="project_edit", requirements={
     *     "id": "\d+"
     * })
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @param Project $project
     * @return Response
     */
    public function editAction(Request $request, Project $project)
    {
        // @TODO implement new issue form for storie's subtask creation
        return new Response();
    }

    /**
     * @Route("/project/new", name="project_new")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request)
    {
        $project = new Project();
        $this->checkIfUserCanHandleProject('ROLE_MANAGER', $project);

        $form = $this->createForm(
            ProjectForm::class, $project,
            ['validation_groups' => [], 'required' => false]
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
     * @param Request $request
     * @return Response
     */
    public function listAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $helper = new Pagination($this->container, $request);

        $projects = $em->createQueryBuilder();
        $projects->select('p')->from('BugTrackerBundle:Project', 'p');
        if (!$this->isGranted('ROLE_MANAGER')) {
            $projects->innerJoin('p.members', 'm')
                ->where('m.id=:user_id')
                ->setParameter('user_id', $this->getUser()->getId());
        }
        $projectsTotalsQb = clone $projects;
        $totalProjectsItems = $projectsTotalsQb->select('COUNT(p)')
            ->getQuery()
            ->getSingleScalarResult();
        $totalProjectPages = ceil($totalProjectsItems / self::PROJECTS_PAGE_SIZE);
        $projectsPagesInfo = $helper->getPrevNextUrls(self::PROJECTS_PAGE_VAR, $totalProjectPages, 'projects_list');
        $offset = self::PROJECTS_PAGE_SIZE * ($projectsPagesInfo['current_page'] - 1);
        $filteredProjects = $projects->setMaxResults(self::PROJECTS_PAGE_SIZE)
            ->setFirstResult($offset)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render(
            'BugTrackerBundle:project:list.html.twig',
            [
                'projects' => array_merge(['items' => $filteredProjects], $projectsPagesInfo),
                'route_group' => 'projects'
            ]
        );
    }
}
