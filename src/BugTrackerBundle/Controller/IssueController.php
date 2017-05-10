<?php

namespace BugTrackerBundle\Controller;

use BugTrackerBundle\Entity\Activity;
use BugTrackerBundle\Entity\Comment;
use BugTrackerBundle\Entity\Issue;
use BugTrackerBundle\Repository\IssueRepository;
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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class IssueController extends Controller
{
    /**
     * @Route("/issue/{id}", name="issue_view", requirements={
     *     "id": "\d+"
     * })
     */
    public function viewAction(Request $request, Issue $issue)
    {
        $this->denyAccessUnlessGranted('handle', $issue);

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

        return $this->render(
            'BugTrackerBundle:issue:view.html.twig',
            [
                'issue' => $issue,
                'statuses_to_change' => $this->getIssueStatusesToChange($issue),
                'comments' => $comments,
                'comment_form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/comment/edit/{id}", name="comment_edit", requirements={
     *     "id": "\d+"
     * })
     */
    public function editCommentAction(Request $request, Comment $comment)
    {
        $this->denyAccessUnlessGranted('edit', $comment);

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
            ['comment' => $comment, 'form' => $form->createView()]
        );
    }

    /**
     * @Route("/comment/delete/{id}", name="comment_delete", requirements={
     *     "id": "\d+"
     * })
     */
    public function deleteCommentAction(Request $request, Comment $comment)
    {
        $this->denyAccessUnlessGranted('delete', $comment);

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
            ['comment' => $comment, 'form' => $form->createView()]
        );
    }

    /**
     * @Route("/issue/new/{id}", name="issue_new", requirements={
     *     "id": "\d+"
     * }))
     */
    public function createAction(Request $request, Project $project)
    {
        $this->denyAccessUnlessGranted('handle', $project);

        $issue = new Issue();
        $issue->setProject($project)
            ->setReporter($this->getUser());

        $form = $this->createForm(
            IssueForm::class, $issue,
            ['validation_groups' => [], 'required' => false]
        );
        $form->add('create', SubmitType::class);

        $formHandler = $this->get('bugtracker.issue.form_handler');
        if ($formHandler->handleCreateForm($form)) {
            return $this->redirectToRoute('issue_view', ['id' => $issue->getId()]);
        }

        return $this->render(
            'BugTrackerBundle:issue:new.html.twig',
            ['form' => $form->createView(), 'project' => $project]
        );
    }

    /**
     * @Route("/issue/edit/{id}", name="issue_edit", requirements={
     *     "id": "\d+"
     * })
     */
    public function editAction(Request $request, Issue $issue)
    {
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
            ['issue' => $issue, 'form' => $form->createView()]
        );
    }

    /**
     * @Route("/issue/change_status/{id}/{status}", name="issue_change_status", requirements={
     *     "id": "\d+"
     * })
     */
    public function changeStatusAction(Issue $issue, $status)
    {
        $this->denyAccessUnlessGranted('handle', $issue);

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
     */
    public function createStorySubtaskAction(Issue $story)
    {
        // @TODO implement new issue form for storie's subtask creation
        return new Response();
    }

    /**
     * @Route("/issue/list", name="issues_list")
     */
    public function listAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $collaborator = $this->isGranted('ROLE_MANAGER') ? null : $this->getUser();
        $page = (int)$request->query->get(IssueRepository::PAGE_VAR) ?: 1;
        $pagination = [IssueRepository::KEY_PAGE => $page];

        return $this->render(
            'BugTrackerBundle:issue:list.html.twig',
            ['issues' => $em->getRepository('BugTrackerBundle:Issue')->findAllByCollaborator($collaborator, $pagination)]
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
