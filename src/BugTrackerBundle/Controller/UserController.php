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
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use BugTrackerBundle\Entity\User;
use BugTrackerBundle\Form\User\EditType as UserForm;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\Common\Collections\Criteria;
use BugTrackerBundle\Helper\Pagination;

class UserController extends Controller
{
    const SUBMITTED_USER_PASSWORD_MIN_LENGTH = 7;

    CONST USERS_PAGE_SIZE = 16;
    CONST USERS_PAGE_VAR = 'up';

    CONST ACTIVITIES_PAGE_SIZE = 10;
    CONST ACTIVITIES_PAGE_VAR = 'acp';

    CONST ISSUES_PAGE_SIZE = 8;
    CONST ISSUES_PAGE_VAR = 'isp';

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
     * @Method({"GET"})
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
        $form = $this->createForm(
            UserForm::class, $user,
            ['validation_groups' => ['user_register'], 'required' => false]
        );
        $form->add('register', SubmitType::class);
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
            $this->get("security.context")->setToken($token);
            $event = new InteractiveLoginEvent($request, $token);
            $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

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
        $password = $user->getPassword();
        $cpassword = $user->getCpassword();
        if ($cpassword) {
            if ($password != $cpassword) {
                $field = $form->get('cpassword');
                $field->addError(new FormError("Passwords must be equal"));
                $isValid = false;
            } elseif (strlen($cpassword) < self::SUBMITTED_USER_PASSWORD_MIN_LENGTH) {
                $field = $form->get('password');
                $field->addError(new FormError("Password must have length of 7 or more characters"));
                $isValid = false;
            }
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
    public function viewAction(Request $request, User $user)
    {
        $queryParams = (array)$request->query->all();
        $queryParams['id'] = $user->getId();
        $helper = new Pagination($this->container, $request);

        $criteria = new Criteria();

        $activities = $user->getActivities();
        $activitiesTotalPages = ceil($activities->count() / self::ACTIVITIES_PAGE_SIZE);
        $activitiesPagesInfo = $helper->getPrevNextUrls(self::ACTIVITIES_PAGE_VAR, $activitiesTotalPages, 'user_view', $queryParams);
        $activitiesPage = $activitiesPagesInfo['current_page'];
        $offset = ($activitiesPage - 1) * self::ACTIVITIES_PAGE_SIZE;
        $criteria->setFirstResult($offset)
            ->setMaxResults(self::ACTIVITIES_PAGE_SIZE);
        $filteredActivities = $activities->matching($criteria);

        $em = $this->getDoctrine()->getEntityManager();
        $issuesQb = $em->createQueryBuilder();
        $issuesQb->select('i')
            ->from('BugTrackerBundle:Issue', 'i')
            ->where('i.status IN(:statuses) AND i.assignee=:assignee')
            ->orderBy('i.updatedAt', 'DESC')
            ->setParameter(':statuses', ['open', 'reopened'])
            ->setParameter(':assignee', $user->getId());
        $issuesTotalsQb = clone $issuesQb;
        $totalIssueItems = $issuesTotalsQb->select('COUNT(i)')
            ->getQuery()
            ->getSingleScalarResult();
        $totalIssuesPages = ceil($totalIssueItems / self::ISSUES_PAGE_SIZE);
        $issuesPagesInfo = $helper->getPrevNextUrls(self::ISSUES_PAGE_VAR, $totalIssuesPages, 'user_view', $queryParams);
        $offset = self::ISSUES_PAGE_SIZE * ($issuesPagesInfo['current_page'] - 1);
        $filteredIssues = $issuesQb->setMaxResults(self::ISSUES_PAGE_SIZE)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return $this->render(
            'BugTrackerBundle:user:view.html.twig',
            [
                'user' => $user,
                'activities' => array_merge(['items' => $filteredActivities], $activitiesPagesInfo),
                'issues' => array_merge(['items' => $filteredIssues], $issuesPagesInfo),
                'route_group' => 'users'
            ]
        );
    }

    /**
     * @Route("/user/edit/{id}", name="user_edit", requirements={
     *     "userId": "\d+"
     * })
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @param User $user
     * @return Response
     */
    public function editAction(Request $request, User $user)
    {
        // checking access
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser()->getId() != $user->getId()) {
            throw $this->createAccessDeniedException('Unable to access this page!');
        }

        if ($this->getUser()->getId() != $user->getId()) {
            $validationGroups = ['user_edit'];
        } else {
            $validationGroups = ['profile_edit'];
        }
        $form = $this->createForm(
            UserForm::class, $user,
            ['validation_groups' => $validationGroups, 'required' => false]
        );
        $form->add('save', SubmitType::class);

        $form->handleRequest($request);
        $userRepository = $this->getDoctrine()->getRepository('BugTrackerBundle:User');
        if ($form->isSubmitted() && $form->isValid()
            && $this->validateSubmittedUser($user, $userRepository, $form)
        ){
            $encoder = $this->container->get('security.password_encoder');

            if ($user->getCpassword()) {
                $plainPassword = $user->getPassword();
                $encodedPassword = $encoder->encodePassword($user, $plainPassword);
                $user->setPassword($encodedPassword);
            }
            $userRepository->save($user);

            return $this->redirect($request->getUri());
        }

        // replace this example code with whatever you need
        return $this->render(
            'BugTrackerBundle:user:edit.html.twig',
            [
                'form' => $form->createView(),
                'user' => $user,
                'route_group' => 'users'
            ]
        );
    }

    /**
     * @Route("/user/list", name="users_list")
     * @Method({"GET"})
     */
    public function listViewAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $helper = new Pagination($this->container, $request);

        $users = $em->createQueryBuilder();
        $users->select('u')->from('BugTrackerBundle:User', 'u');
        $usersTotalsQb = clone $users;
        $totalUserItems = $usersTotalsQb->select('COUNT(u)')
            ->getQuery()
            ->getSingleScalarResult();
        $totalUsersPages = ceil($totalUserItems / self::USERS_PAGE_SIZE);
        $issuesPagesInfo = $helper->getPrevNextUrls(self::USERS_PAGE_VAR, $totalUsersPages, 'users_list');
        $offset = self::USERS_PAGE_SIZE * ($issuesPagesInfo['current_page'] - 1);
        $filteredUsers = $users->setMaxResults(self::USERS_PAGE_SIZE)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return $this->render(
            'BugTrackerBundle:user:list.html.twig',
            [
                'users' => array_merge(['items' => $filteredUsers], $issuesPagesInfo),
                'route_group' => 'users'
            ]
        );
    }
}
