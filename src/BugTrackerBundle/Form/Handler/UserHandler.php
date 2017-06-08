<?php

namespace BugTrackerBundle\Form\Handler;

use Doctrine\ORM\EntityManager;
use BugTrackerBundle\Entity\User;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserHandler
{
    private $em;
    private $encoder;
    private $request;
    private $requestStack;

    const SUBMITTED_USER_PASSWORD_MIN_LENGTH = 7;

    public function __construct(
        EntityManager $entityManager,
        UserPasswordEncoderInterface $encoder,
        RequestStack $requestStack
    ){
        $this->em = $entityManager;
        $this->encoder = $encoder;
        $this->requestStack = $requestStack;
    }

    /**
     * @param FormInterface $form
     * @return bool
     */
    public function handleRegisterForm(FormInterface $form)
    {
        $form->handleRequest($this->getRequest());
        if (!$form->isValid()) {
            return false;
        }

        $user = $this->getUser($form);
        if ($this->validateSubmittedUser($user, $form)) {
            $plainPassword = $user->getPassword();
            $encodedPassword = $this->encoder->encodePassword($user, $plainPassword);
            $user->setPassword($encodedPassword)
                ->setRoles([User::OPERATOR_ROLE]);
            $this->em->persist($user);
            $this->em->flush($user);

            return true;
        }

        return false;
    }

    /**
     * @param FormInterface $form
     * @throws \Exception
     * @return bool
     */
    public function handleEditForm(FormInterface $form)
    {
        $form->handleRequest($this->getRequest());
        if (!$form->isValid()) {
            return false;
        }

        $user = $this->getUser($form);
        if ($this->validateSubmittedUser($user, $form)) {
            if ($user->getCpassword()) {
                $plainPassword = $user->getPassword();
                $encodedPassword = $this->encoder->encodePassword($user, $plainPassword);
                $user->setPassword($encodedPassword);
            }

            $this->em->persist($user);
            $this->em->flush($user);

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param FormInterface $form
     * @return User
     * @throws \Exception
     */
    protected function getUser(FormInterface $form)
    {
        $user = $form->getData();
        if (!is_object($user) && !($user instanceof User)) {
            throw new \Exception("From has no user entity set");
        }

        return $user;
    }

    /**
     * @return null|\Symfony\Component\HttpFoundation\Request
     * @throws \Exception
     */
    public function getRequest()
    {
        if (!$this->request) {
            $request = $this->requestStack->getCurrentRequest();
            if (!$request) {
                throw new \Exception("No HTTP request has been initialized");
            }
            $this->request = $request;
        }

        return $this->request;
    }

    /**
     * @param UserInterface $user
     * @param FormInterface $form
     * @return bool
     */
    protected function validateSubmittedUser(UserInterface $user, FormInterface $form)
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

        $userRepository = $this->em->getRepository('BugTrackerBundle:User');
        if ($userRepository->userExists($user)){
            $form->addError(new FormError("User with given username or email already exists"));
            $isValid = false;
        }

        return $isValid;
    }
}
