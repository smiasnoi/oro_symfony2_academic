<?php

namespace BugTrackerBundle\Form\Handler;

use BugTrackerBundle\Entity\Activity;
use BugTrackerBundle\Entity\Comment;
use Doctrine\ORM\EntityManager;
use BugTrackerBundle\Entity\User;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class IssueHandler
{
    private $em;

    public function __construct(
        EntityManager $entityManager,
        RequestStack $requestStack
    ){
        $this->em = $entityManager;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @param FormInterface $form
     * @return bool
     */
    public function handleCreateForm(FormInterface $form)
    {
        $issue = $this->getIssue($form);

        $form->handleRequest($this->request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;

            $project = $issue->getProject();
            $reporter = $issue->getReporter();

            $issue->setCode($project->getCode() . '-' . uniqid())
                ->setStatus('new')
                ->addCollaborator($reporter)
                ->addCollaborator($issue->getAssignee())
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());
            $em->persist($issue);
            $project->addMember($issue->getAssignee())
                ->addMember($this->getUser());
            $em->persist($issue);
            $em->flush();

            $activity = new Activity();
            $snappedData = ['issue_code' => $issue->getCode()];
            $activity->setProject($project)
                ->setUser($reporter)
                ->setEntityId($issue->getId())
                ->setEntity('Issue')
                ->setSnappedData($snappedData)
                ->setType(Activity::NEW_ISSUE_TYPE)
                ->setCreatedAt(new \DateTime());
            $em->persist($activity);
            $em->flush();

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param FormInterface $form
     * @throws \Exception
     * @return bool
     */
    public function handleEditForm(FormInterface $form)
    {
        $issue = $this->getIssue($form);

        $form->handleRequest($this->request);
        if ($form->isSubmitted() && $form->isValid()) {
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
    protected function getIssue(FormInterface $form)
    {
        $issue = $form->getData();
        if (!is_object($issue) && !($issue instanceof Issue)) {
            throw new \Exception("Form has no issue entity set");
        }

        if (!$issue->getProject() || !$issue->getReporter()) {
            throw new \Exception("Issue hasn't been bound to any project or reporter");
        }

        return $issue;
    }

    /**
     * @param FormInterface $form
     * @return User
     * @throws \Exception
     */
    protected function getComment(FormInterface $form)
    {
        $comment = $form->getData();
        if (!is_object($comment) && !($comment instanceof Comment)) {
            throw new \Exception("Form has no comment entity set");
        }

        if (!$comment->getAuthor() || !$comment->getIssue()) {
            throw new \Exception("Comment hasn't been bount to any author or issue");
        }

        return $comment;
    }
}
