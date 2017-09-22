<?php

namespace BugTrackerBundle\Form\Handler;

use BugTrackerBundle\Entity\Activity;
use BugTrackerBundle\Mailer\Activity as ActivityMailer;
use BugTrackerBundle\Entity\Comment;
use Doctrine\ORM\EntityManager;
use BugTrackerBundle\Entity\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use BugTrackerBundle\Helper\Issue as IssueHelper;
use BugTrackerBundle\Entity\Issue as IssueEntity;

class IssueHandler
{
    protected $em;
    protected $request;
    protected $helper;
    protected $activityMailer;

    public function __construct(
        EntityManager $entityManager,
        RequestStack $requestStack,
        IssueHelper $helper,
        ActivityMailer $activityMailer
    ){
        $this->em = $entityManager;
        $this->requestStack = $requestStack;
        $this->helper = $helper;
        $this->activityMailer = $activityMailer;
    }

    /**
     * @param FormInterface $form
     * @return bool
     */
    public function handleCreateForm(FormInterface $form)
    {
        $issue = $this->getIssue($form);
        $project = $issue->getProject();
        $reporter = $issue->getReporter();

        $issue->setCode($project->getCode() . '-' . uniqid())
            ->setStatus($this->helper->getNewStatus());

        $form->handleRequest($this->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;

            $issue->addCollaborator($reporter)
                ->addCollaborator($issue->getAssignee())
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());
            $em->persist($issue);
            $project->addMember($issue->getAssignee())
                ->addMember($reporter);
            $em->persist($issue);
            $em->flush();

            $activity = new Activity();
            $snappedData = ['issue_code' => $issue->getCode()];
            $activity->setProject($project)
                ->setIssue($issue)
                ->setUser($reporter)
                ->setEntityId($issue->getId())
                ->setEntity('Issue')
                ->setSnappedData($snappedData)
                ->setType(Activity::NEW_ISSUE_TYPE)
                ->setCreatedAt(new \DateTime());
            $em->persist($activity);
            $em->flush();
            $this->activityMailer->notifyCollaborators($activity);

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param FormInterface $form
     * @return bool
     */
    public function handleCreateSubtaskForm(FormInterface $form)
    {
        $issue = $this->getIssue($form);
        $issue->setType($this->helper->getSubtaskType());

        return $this->handleCreateForm($form);
    }

    /**
     * @param FormInterface $form
     * @throws \Exception
     * @return bool
     */
    public function handleEditForm(FormInterface $form)
    {
        $issue = $this->getIssue($form);

        $form->handleRequest($this->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;

            $issue->addCollaborator($issue->getAssignee())
                ->setUpdatedAt(new \DateTime());
            $em->persist($issue);
            $project = $issue->getProject();
            $project->addMember($issue->getAssignee());
            $em->persist($project);
            $em->flush();

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param FormInterface $form
     * @return bool
     */
    public function handleCommentPostForm(FormInterface $form)
    {
        $comment = $this->getComment($form);
        $issue = $comment->getIssue();
        $author = $comment->getAuthor();

        $form->handleRequest($this->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;

            $comment->setCreatedAt(new \DateTime());
            $em->persist($comment);

            $issue->addCollaborator($author);
            $em->persist($issue);
            $em->flush();

            $activity = new Activity();
            $snappedData = [
                'issue_code' => $issue->getCode(),
                'comment_body' => $comment->getBody()
            ];
            $activity->setIssue($issue)
                ->setProject($issue->getProject())
                ->setUser($author)
                ->setEntityId($issue->getId())
                ->setEntity('Comment')
                ->setSnappedData($snappedData)
                ->setType(Activity::COMMENT_POST_TYPE)
                ->setCreatedAt(new \DateTime());
            $em->persist($activity);
            $em->flush();
            $this->activityMailer->notifyCollaborators($activity);

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param FormInterface $form
     * @return bool
     */
    public function handleCommentEditForm(FormInterface $form)
    {
        $comment = $this->getComment($form);
        $author = $comment->getAuthor();

        $form->handleRequest($this->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($comment);

            $issue = $comment->getIssue();
            $issue->addCollaborator($author);
            $em->persist($issue);
            $em->flush();

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param FormInterface $form
     * @return bool
     */
    public function handleCommentDeleteForm(FormInterface $form)
    {
        $comment = $this->getComment($form);

        $form->handleRequest($this->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->remove($comment);
            $this->em->flush();

            return true;
        } else {
            return false;
        }
    }


    /**
     * @param IssueEntity $issue
     * @param User $reporter
     * @param string $status
     * @return bool
     */
    public function handleIssueStatusChange(IssueEntity $issue, User $reporter, $status)
    {
        $allowedStatuses = $this->helper->getIssueStatusesToChange($issue);
        if (array_key_exists($status, $allowedStatuses)) {
            $em = $this->em;

            $issue->setStatus($status);
            $em->persist($issue);
            $em->flush();

            $activity = new Activity();
            $snappedData = [
                'issue_code' => $issue->getCode(),
                'old_status' => 'new',
                'status' => $issue->getStatus()
            ];
            $activity->setIssue($issue)
                ->setProject($issue->getProject())
                ->setUser($reporter)
                ->setEntityId($issue->getId())
                ->setEntity('Issue')
                ->setSnappedData($snappedData)
                ->setType(Activity::ISSUE_STATUS_CHANGE_TYPE)
                ->setCreatedAt(new \DateTime());
            $em->persist($activity);
            $em->flush();
            $this->activityMailer->notifyCollaborators($activity);

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
        if (!is_object($issue) && !($issue instanceof IssueEntity)) {
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
}
