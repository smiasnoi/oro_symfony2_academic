<?php

namespace BugTrackerBundle\Controller;

use BugTrackerBundle\Entity\Activity;
use BugTrackerBundle\Entity\Project;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ProjectController extends Controller
{
    /**
     * @Route("/project/{id}", name="project_view", requirements={
     *     "id": "\d+"
     * })
     * @Method({"GET"})
     */
    public function viewAction(Project $project)
    {
        return $this->render(
            'BugTrackerBundle:project:view.html.twig',
            ['project' => $project]
        );
    }
}
