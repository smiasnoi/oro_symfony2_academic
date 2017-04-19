<?php

namespace BugTrackerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DashboardController extends Controller
{
    /**
     * Fixture route to populate bug tracker data
     * (Doctrine entities relations checks)
     *
     * @Route("/", name="dashboard")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        // replace this example code with whatever you need
        return $this->render('BugTrackerBundle::dashboard.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ));
    }
}
