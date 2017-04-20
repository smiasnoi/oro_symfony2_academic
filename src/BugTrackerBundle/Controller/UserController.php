<?php

namespace BugTrackerBundle\Controller;

use BugTrackerBundle\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Form\FormError;

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
            'BugTrackerBundle:user:login.html.twig',
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

        $user = new User();
        $form = $this->createForm(RegisterForm::class, $user, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()
            && $this->validateSubmittedUser($user, $userRepository, $form)
        ){
            $plainPassword = $user->getPassword();
            $encodedPassword = $encoder->encodePassword($user, $plainPassword);
            $user->setPassword($encodedPassword)
                ->setRoles([User::OPERATOR_ROLE]);
            $userRepository->save($user);

            // auto login
            $token = new UsernamePasswordToken($user, $user->getPassword(), "secured_area", $user->getRoles());
            $securityContext = $this->container->get('security.context');
            $securityContext->setToken($token);

            return $this->redirectToRoute('dashboard');
        }

        return $this->render(
            'BugTrackerBundle:user:register.html.twig',
            ['form' => $form->createView()]
        );
    }

    /**
     * @param User $user
     * @param UserRepository $userRepository
     * @param FormInterface $form
     * @return bool
     */
    protected function validateSubmittedUser(User $user, UserRepository $userRepository, FormInterface $form)
    {
        $isValid = true;
        if ($user->getPassword() !== $user->getCpassword()) {
            $field = $form->get('cpassword');
            $field->addError(new FormError("Passwords must be equal"));
            $isValid = false;
        }

        if ($userRepository->userExists($user)){
            $form->addError(new FormError("User with given username or email already exists"));
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @Route("/user/{id}", name="user_view", requirements={
     *     "id": "\d+"
     * })
     * @Method({"GET"})
     */
    public function viewAction(User $user)
    {
        // @TODO implement user profile representation
        echo $user->getId(). ' | '.$user->getFullname();
        return new Response();
    }

    /**
     * @Route("/user/edit/{userId}", name="user_edit", requirements={
     *     "userId": "\d+"
     * })
     * @Method({"GET"})
     * @param User $user
     * @return Response
     */
    public function editAction(User $user)
    {
        // replace this example code with whatever you need
        return $this->render('BugTrackerBundle:default:index.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ));
    }

    /**
     * @Route("/user/list", name="users_list_view")
     * @Method({"GET"})
     */
    public function listViewAction(Request $request)
    {
        $userRepository = $this->getDoctrine()->getRepository('BugTrackerBundle:User');
        $collection = $userRepository->getFilteredCollection((int)$request->query->get('page'));
        $collection['prev_link'] = $collection['current_page'] > 1 ?
            $this->generateUrl('users_list_view', ['page' => $collection['current_page'] - 1]) : null;
        $collection['next_link'] = $collection['current_page'] < $collection['total_pages'] ?
            $this->generateUrl('users_list_view', ['page' => $collection['current_page'] + 1]) : null;

        return $this->render('BugTrackerBundle:user:list.html.twig',
            ['collection' => $collection]
        );
    }
}
