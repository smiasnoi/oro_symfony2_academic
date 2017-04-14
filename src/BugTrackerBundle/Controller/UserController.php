<?php

namespace BugTrackerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use BugTrackerBundle\Entity\User;
use BugTrackerBundle\Form\User\RegisterType as RegisterForm;

class UserController extends Controller
{
    /**
     * @Route("/user/login", name="login")
     * @Method({"GET"})
     */
    public function loginAction()
    {
        $authenticationUtils = $this->get('security.authentication_utils');
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render(
            'user/login.html.twig',
            array(
                'last_username' => $lastUsername,
                'error'
                => $error,
            )
        );
    }

    /**
     * @Route("/user/loginPost", name="loginPost")
     * @Method({"POST"})
     */
    public function loginPostAction()
    {
        return new Response('');
    }

    /**
     * @Route("/user/logout", name="logout")
     * @Method({"get"})
     */
    public function logoutAction()
    {
        return new Response('');
    }

    /**
     * @Route("/user/register", name="register")
     * @Method({"GET", "POST"})
     */
    public function registerAction(Request $request)
    {
        $userRepository = $this->getDoctrine()->getRepository('BugTrackerBundle:User');
        $encoder = $this->container->get('security.password_encoder');
        $userRepository->setEncoder($encoder);

        $user = new User();
        $form = $this->createForm(RegisterForm::class, $user, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $userRepository->validateSubmitedUser($user)) {
            $userRepository->prepareAndSaveOperator($user);

            // auto login
            $token = new UsernamePasswordToken($user, $user->getPassword(), "secure_area", $user->getRoles());
            $securityContext = $this->container->get('security.context');
            $securityContext->setToken($token);

            return $this->redirectToRoute('dashboard');
        }
        $userRepository->appendAdditionalErrorsToForm($form);

        return $this->render('user/register.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'form' => $form->createView()
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
