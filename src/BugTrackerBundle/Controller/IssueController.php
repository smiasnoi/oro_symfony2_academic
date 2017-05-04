<?php

namespace BugTrackerBundle\Controller;

use BugTrackerBundle\Entity\Activity;
use BugTrackerBundle\Entity\Comment;
use BugTrackerBundle\Entity\Issue;
use BugTrackerBundle\Entity\Project;
use Doctrine\Common\Collections\Criteria;
use BugTrackerBundle\Form\Issue\CommentType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BugTrackerBundle\Form\Issue\EditType as IssueForm;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use BugTrackerBundle\Helper\Pagination;

class IssueController extends AbstractController
{
    CONST ISSUES_PAGE_SIZE = 16;
    CONST ISSUES_PAGE_VAR = 'isp';

    /**
     * @Route("/issue/{id}", name="issue_view", requirements={
     *     "id": "\d+"
     * })
     * @Method({"GET", "POST"})
     */
    public function viewAction(Request $request, Issue $issue)
    {
        $this->checkIfUserCanHandleIssue('ROLE_MANAGER', $issue);

        $comment = new Comment();
        $comment->setAuthor($this->getUser())
            ->setIssue($issue);

        $form = $this->createForm(
            CommentType::class, $comment,
            ['validation_groups' => [], 'required' => false]
        );
        $form->add('add', SubmitType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // saving new comment
            $em = $this->getDoctrine()->getEntityManager();
            $comment->setCreatedAt(new \DateTime());
            $em->persist($comment);

            // adding issue collaborator
            $issue->addCollaborator($this->getUser());
            $em->persist($issue);
            $em->flush();

            // adding 'post comment' project activity
            $activity = new Activity();
            $snappedData = [
                'issue_code' => $issue->getCode(),
                'comment_body' => $comment->getBody()
            ];
            $activity->setIssue($issue)
                ->setProject($issue->getProject())
                ->setUser($this->getUser())
                ->setEntityId($issue->getId())
                ->setEntity('Comment')
                ->setSnappedData($snappedData)
                ->setType(Activity::COMMENT_POST_TYPE)
                ->setCreatedAt(new \DateTime());
            $em->persist($activity);
            $em->flush();

            return $this->redirect($this->generateUrl('issue_view', ['id' => $issue->getId()]));
        }

        $criteria = new Criteria();
        $criteria->orderBy(['createdAt' => Criteria::DESC]);
        $comments = $issue->getComments()->matching($criteria);
        $activities = $issue->getActivities()->matching($criteria);

        return $this->render(
            'BugTrackerBundle:issue:view.html.twig',
            [
                'issue' => $issue,
                'statuses_to_change' => $this->getIssueStatusesToChange($issue),
                'comments' => $comments,
                'activities' => $activities,
                'comment_form' => $form->createView(),
                'route_group' => 'issues'
            ]
        );
    }

    /**
     * @Route("/comment/edit/{id}", name="comment_edit", requirements={
     *     "id": "\d+"
     * })
     * @Method({"GET", "POST"})
     */
    public function editCommentAction(Request $request, Comment $comment)
    {
        $this->roleOrEntityOwnerAccessCheck('ROLE_MANAGER', $comment->getAuthor());

        $form = $this->createForm(
            CommentType::class, $comment,
            ['validation_groups' => [], 'required' => false]
        );
        $form->add('update', SubmitType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // updating comment
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($comment);
            // adding issue collaborator
            $issue = $comment->getIssue();
            $issue->addCollaborator($this->getUser());
            $em->persist($issue);
            $em->flush();

            return $this->redirect($this->generateUrl('issue_view', ['id' => $issue->getId()]));
        }

        return $this->render(
            'BugTrackerBundle:issue:comment\edit.html.twig',
            [
                'comment' => $comment,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/comment/delete/{id}", name="comment_delete", requirements={
     *     "id": "\d+"
     * })
     * @Method({"GET", "POST"})
     */
    public function deleteCommentAction(Request $request, Comment $comment)
    {
        $this->roleOrEntityOwnerAccessCheck('ROLE_MANAGER', $comment->getAuthor());

        $form = $this->createFormBuilder()
            ->add('delete', SubmitType::class)
            ->add('cancel', ResetType::class)
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // updating comment
            $em = $this->getDoctrine()->getEntityManager();
            $issue = $comment->getIssue();
            $em->remove($comment);
            $em->flush();

            return $this->redirect($this->generateUrl('issue_view', ['id' => $issue->getId()]));
        }

        return $this->render(
            'BugTrackerBundle:issue:comment\delete.html.twig',
            [
                'comment' => $comment,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/issue/new/{id}", name="issue_new", requirements={
     *     "id": "\d+"
     * }))
     * @Method({"GET", "POST"})
     */
    public function createAction(Request $request, Project $project)
    {
        $this->checkIfUserCanHandleProject('ROLE_MANAGER', $project);

        $issue = new Issue();
        $form = $this->createForm(
            IssueForm::class, $issue,
            ['validation_groups' => [], 'required' => false]
        );
        $form->add('create', SubmitType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $issue->setProject($project)
                ->setCode($project->getCode() . '-' . uniqid())
                ->setStatus('new')
                ->setReporter($this->getUser())
                ->addCollaborator($this->getUser())
                ->addCollaborator($issue->getAssignee())
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());
            $em->persist($issue);
            $project->addMember($issue->getAssignee())
                ->addMember($this->getUser());
            $em->persist($issue);
            $em->flush();

            // adding 'new issue' activity
            $activity = new Activity();
            $snappedData = ['issue_code' => $issue->getCode()];
            $activity->setProject($project)
                ->setUser($this->getUser())
                ->setEntityId($issue->getId())
                ->setEntity('Issue')
                ->setSnappedData($snappedData)
                ->setType(Activity::NEW_ISSUE_TYPE)
                ->setCreatedAt(new \DateTime());
            $em->persist($activity);
            $em->flush();

            return $this->redirectToRoute('issue_view', ['id' => $issue->getId()]);
        }

        return $this->render(
            'BugTrackerBundle:issue:new.html.twig',
            [
                'form' => $form->createView(),
                'project' => $project,
                'route_group' => 'issues'
            ]
        );
    }

    /**
     * @Route("/issue/edit/{id}", name="issue_edit", requirements={
     *     "id": "\d+"
     * })
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Issue $issue)
    {
        $this->checkIfUserCanHandleIssue('ROLE_MANAGER', $issue);

        $form = $this->createForm(
            IssueForm::class, $issue,
            ['validation_groups' => [], 'required' => false]
        );
        $form->add('update', SubmitType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $issue->addCollaborator($issue->getAssignee())
                ->setUpdatedAt(new \DateTime());
            $em->persist($issue);
            $project = $issue->getProject();
            $project->addMember($issue->getAssignee());
            $em->persist($project);
            $em->flush();

            return $this->redirectToRoute('issue_view', ['id' => $issue->getId()]);
        }

        return $this->render(
            'BugTrackerBundle:issue:edit.html.twig',
            [
                'issue' => $issue,
                'form' => $form->createView(),
                'route_group' => 'issues'
            ]
        );
    }

    /**
     * @Route("/issue/change_status/{id}/{status}", name="issue_change_status", requirements={
     *     "id": "\d+"
     * })
     * @Method({"GET"})
     */
    public function changeStatusAction(Issue $issue, $status)
    {
        $this->checkIfUserCanHandleIssue('ROLE_MANAGER', $issue);

        $allowedStatuses = $this->getIssueStatusesToChange($issue);
        if (array_key_exists($status, $allowedStatuses)) {
            $em = $this->getDoctrine()->getEntityManager();
            $issue->setStatus($status);
            $em->persist($issue);
            $em->flush();
        }

        return $this->redirectToRoute('issue_view', ['id' => $issue->getId()]);
    }

    /**
     * @Route("/story/new/subtask/{id}", name="new_story_subtask", requirements={
     *     "id": "\d+"
     * })
     * @Method({"GET"})
     */
    public function createStorySubtaskAction(Issue $story)
    {
        // @TODO implement new issue form for storie's subtask creation
        return new Response();
    }

    /**
     * @Route("/issue/list", name="issues_list")
     * @Method({"GET"})
     */
    public function listViewAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $helper = new Pagination($this->container, $request);

        $issues = $em->createQueryBuilder();
        $issues->select('i')->from('BugTrackerBundle:Issue', 'i');
        if (!$this->isGranted('ROLE_MANAGER')) {
            $issues->innerJoin('i.collaborators', 'c')
                ->where('c.id=:user_id')
                ->setParameter('user_id', $this->getUser()->getId());
        }

        $issuesTotalsQb = clone $issues;
        $totalIssueItems = $issuesTotalsQb->select('COUNT(i)')
            ->getQuery()
            ->getSingleScalarResult();
        $totalIssuesPages = ceil($totalIssueItems / self::ISSUES_PAGE_SIZE);
        $issuesPagesInfo = $helper->getPrevNextUrls(self::ISSUES_PAGE_VAR, $totalIssuesPages, 'issues_list');
        $offset = self::ISSUES_PAGE_SIZE * ($issuesPagesInfo['current_page'] - 1);
        $filteredIssues = $issues->setMaxResults(self::ISSUES_PAGE_SIZE)
            ->setFirstResult($offset)
            ->orderBy('i.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render(
            'BugTrackerBundle:issue:list.html.twig',
            [
                'issues' => array_merge(['items' => $filteredIssues], $issuesPagesInfo),
                'route_group' => 'issues'
            ]
        );
    }

    /**
     * @param Issue $issue
     * @return array
     */
    protected function getIssueStatusesToChange(Issue $issue)
    {
        switch ($issue->getStatus()) {
            case 'new':
            case 'reopened':
                $allowedStatuses = ['in_progress', 'closed'];
                break;
            case 'closed':
                $allowedStatuses = ['reopened'];
                break;
            default:
                $allowedStatuses = [];
        }
        return array_intersect_key(Issue::getStatuses(), array_flip($allowedStatuses));
    }
}
