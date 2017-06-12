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
    public function viewAction(Issue $issue)
    {
        $this->denyAccessUnlessGranted('handle', $issue);

        $comment = new Comment();
        $comment->setAuthor($this->getUser())
            ->setIssue($issue);

        $form = $this->createForm(CommentType::class, $comment, ['required' => false]);
        $form->add('add', SubmitType::class);

        $formHandler = $this->get('bugtracker.issue.form_handler');
        if ($formHandler->handleCommentPostForm($form)) {
            return $this->redirect($this->generateUrl('issue_view', ['id' => $issue->getId()]));
        }

        $criteria = new Criteria();
        $criteria->orderBy(['createdAt' => Criteria::DESC]);
        $comments = $issue->getComments()->matching($criteria);

        return $this->render(
            'BugTrackerBundle:issue:view.html.twig',
            [
                'issue' => $issue,
                'statuses_to_change' => $this->get('bugtracker.issue.helper')->getIssueStatusesToChange($issue),
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
    public function editCommentAction(Comment $comment)
    {
        $this->denyAccessUnlessGranted('edit', $comment);
        $comment->setAuthor($this->getUser());

        $form = $this->createForm(
            CommentType::class, $comment,
            ['required' => false]
        );
        $form->add('update', SubmitType::class);

        $formHandler = $this->get('bugtracker.issue.form_handler');
        if ($formHandler->handleCommentPostForm($form)) {
            return $this->redirect($this->generateUrl('issue_view', ['id' => $comment->getIssue()->getId()]));
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
    public function deleteCommentAction(Comment $comment)
    {
        $this->denyAccessUnlessGranted('delete', $comment);

        $form = $this->createFormBuilder()
            ->add('delete', SubmitType::class)
            ->add('cancel', ResetType::class)
            ->getForm()
            ->setData($comment);

        $formHandler = $this->get('bugtracker.issue.form_handler');
        if ($formHandler->handleCommentDeleteForm($form)) {
            return $this->redirect($this->generateUrl('issue_view', ['id' => $comment->getIssue()->getId()]));
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
    public function createAction(Project $project)
    {
        $this->denyAccessUnlessGranted('handle', $project);

        $issue = new Issue();
        $issue->setProject($project)
            ->setReporter($this->getUser());

        $form = $this->createForm(IssueForm::class, $issue, ['required' => false]);
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
     * @Route("/story/new/subtask/{id}", name="new_story_subtask", requirements={
     *     "id": "\d+"
     * })
     */
    public function createStorySubtaskAction(Issue $story)
    {
        $this->denyAccessUnlessGranted('handle', $story);

        $subIssue = new Issue();

        $subIssue->setProject($story->getProject())
            ->setParent($story)
            ->setReporter($this->getUser());

        $form = $this->createForm(IssueForm::class, $subIssue, ['required' => false]);
        $form->add('create', SubmitType::class);

        $formHandler = $this->get('bugtracker.issue.form_handler');
        if ($formHandler->handleCreateSubtaskForm($form)) {
            return $this->redirectToRoute('issue_view', ['id' => $subIssue->getId()]);
        }

        return $this->render(
            'BugTrackerBundle:issue:new_story.html.twig',
            ['form' => $form->createView(), 'story' => $story]
        );
    }

    /**
     * @Route("/issue/edit/{id}", name="issue_edit", requirements={
     *     "id": "\d+"
     * })
     */
    public function editAction(Issue $issue)
    {
        $form = $this->createForm(
            IssueForm::class, $issue,
            ['required' => false]
        );
        $form->add('update', SubmitType::class);

        $formHandler = $this->get('bugtracker.issue.form_handler');
        if ($formHandler->handleEditForm($form)) {
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

        $formHandler = $this->get('bugtracker.issue.form_handler');
        $formHandler->handleIssueStatusChange($issue, $this->getUser(), $status);

        return $this->redirectToRoute('issue_view', ['id' => $issue->getId()]);
    }

    /**
     * @Route("/issue/list", name="issues_list")
     */
    public function listAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $collaborator = $this->isGranted('ROLE_MANAGER') ? null : $this->getUser();
        $page = (int)$request->query->get(IssueRepository::PAGE_VAR) ?: 1;
        $pagination = [IssueRepository::KEY_PAGE => $page];

        return $this->render(
            'BugTrackerBundle:issue:list.html.twig',
            ['issues' => $em->getRepository('BugTrackerBundle:Issue')->findAllByCollaborator($collaborator, $pagination)]
        );
    }
}
