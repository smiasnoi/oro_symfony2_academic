<?php

namespace BugTrackerBundle\Controller;

use BugTrackerBundle\Repository\IssueRepository;
use BugTrackerBundle\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use BugTrackerBundle\Entity\User;
use BugTrackerBundle\Form\User\EditType as UserForm;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UserController extends Controller
{
    /**
     * @Route("/user/login", name="login")
     */
    public function loginAction()
    {
        $authenticationUtils = $this->get('security.authentication_utils');
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render(
            'BugTrackerBundle:user:login.html.twig',
            [
                'last_username' => $lastUsername,
                'error' => $error,
            ]
        );
    }

    /**
     * @Route("/user/loginPost", name="loginPost")
     */
    public function loginPostAction()
    {
        return new Response('');
    }

    /**
     * @Route("/user/logout", name="logout")
     */
    public function logoutAction()
    {
        return new Response('');
    }

    /**
     * @Route("/user/register", name="register")
     */
    public function registerAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm(
            UserForm::class, $user,
            ['validation_groups' => ['user_register'], 'required' => false]
        );
        $form->add('register', SubmitType::class);

        $formHandler = $this->get('bugtracker.user.form_handler');
        if ($formHandler->handleRegisterForm($form)) {
            // auto login
            $token = new UsernamePasswordToken($user, $user->getPassword(), "secured_area", $user->getRoles());
            $this->get("security.context")->setToken($token);
            $event = new InteractiveLoginEvent($request, $token);
            $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

            return $this->redirectToRoute('dashboard');
        }

        return $this->render('BugTrackerBundle:user:register.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/user/{id}", name="user_view", requirements={
     *     "id": "\d+"
     * })
     */
    public function viewAction(Request $request, User $user)
    {
        $page = (int)$request->query->get(IssueRepository::PAGE_VAR) ?: 1;
        $pagination = [IssueRepository::KEY_PAGE => $page];
        $em = $this->getDoctrine()->getEntityManager();

        return $this->render(
            'BugTrackerBundle:user:view.html.twig',
            [
                'user' => $user,
                'issues' => $em->getRepository('BugTrackerBundle:Issue')->findAllOpenedByAssignee($user, $pagination)
            ]
        );
    }

    /**
     * @Route("/", name="dashboard")
     */
    public function dashboardAction(Request $request)
    {
        $page = (int)$request->query->get(IssueRepository::PAGE_VAR) ?: 1;
        $pagination = [IssueRepository::KEY_PAGE => $page];
        $em = $this->getDoctrine()->getEntityManager();

        return $this->render(
            'BugTrackerBundle::dashboard.html.twig',
            ['issues' => $em->getRepository('BugTrackerBundle:Issue')->findAllOpenedByAssignee($this->getUser(), $pagination)]
        );
    }

    /**
     * @Route("/user/edit/{id}", name="user_edit", requirements={
     *     "id": "\d+"
     * })
     */
    public function editAction(Request $request, User $user)
    {
        $this->denyAccessUnlessGranted('edit', $user);

        $validationGroups = $this->getUser()->getId() != $user->getId() ? ['user_edit'] : ['profile_edit'];
        $form = $this->createForm(
            UserForm::class, $user,
            ['validation_groups' => $validationGroups, 'required' => false]
        );
        $form->add('save', SubmitType::class);

        $formHandler = $this->get('bugtracker.user.form_handler');
        if ($formHandler->handleEditForm($form)) {
            return $this->redirect($request->getUri());
        } else {
            return $this->render('BugTrackerBundle:user:edit.html.twig', ['form' => $form->createView(), 'user' => $user]);
        }
    }

    /**
     * @Route("/user/list", name="users_list")
     */
    public function listViewAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $page = (int)$request->query->get(UserRepository::PAGE_VAR) ?: 1;
        $pagination = [UserRepository::KEY_PAGE => $page];

        return $this->render(
            'BugTrackerBundle:user:list.html.twig',
            ['users' => $em->getRepository('BugTrackerBundle:User')->findAllPaginated($pagination)]
        );
    }

    /**
     * @Route("/user/ajaxSearch", name="users_ajax_search")
     */
    public function ajaxSearchAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $users = $em->getRepository('BugTrackerBundle:User')->findAllPaginated(
            [UserRepository::KEY_PAGE => (int)$request->query->get('p') ?: 1],
            ['searchCriteria' => $request->query->get('q')]
        );

        $hidratedItems = [];
        foreach ($users[UserRepository::KEY_ITEMS] as $user) {
            $hidratedItems[] = [
                'id' => $user->getId(),
                'fullname' => htmlspecialchars($user->getFullname()),
                'email' => htmlspecialchars($user->getEmail())
            ];
        }
        $users['items'] = $hidratedItems;

        return new Response(json_encode($users), 200, ['Content-Type' => 'application/json']);
    }
}
