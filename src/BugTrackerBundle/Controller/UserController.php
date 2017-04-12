<?php

namespace BugTrackerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use BugTrackerBundle\Entity;

class UserController extends Controller
{
    /**
     * @Route("/user/login", name="user_login")
     * @Method({"GET"})
     */
    public function loginAction()
    {

    }

    /**
     * @Route("/user/register", name="user_register")
     * @Method({"GET"})
     */
    public function registerAction()
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ));
    }

    /**
     * @Route("/user/registerPost", name="user_register_post")
     * @Method({"POST"})
     */
    public function registerPostAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ));
    }

    /**
     * @Route("/user/edit/{userId}", name="user_edit", requirements={
     *     "userId": "\d+"
     * })
     * @Method({"GET"})
     */
    public function editAction($userId)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ));
    }
}
