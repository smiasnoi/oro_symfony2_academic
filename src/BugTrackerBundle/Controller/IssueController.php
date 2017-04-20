<?php

namespace BugTrackerBundle\Controller;

use BugTrackerBundle\Entity\Activity;
use BugTrackerBundle\Entity\Comment;
use BugTrackerBundle\Entity\Issue;
use BugTrackerBundle\Form\Issue\CommentType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class IssueController extends Controller
{
    /**
     * @Route("/issue/{id}", name="issue_view", requirements={
     *     "id": "\d+"
     * })
     * @Method({"GET", "POST"})
     */
    public function viewAction(Request $request, Issue $issue, Comment $comment = null)
    {
        $author = $this->getUser();

        $comment = new Comment();
        $comment->setAuthor($author)
            ->setIssue($issue);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // saving new comment
            $em = $this->getDoctrine()->getEntityManager();
            $comment->setCreatedAt(new \DateTime());
            $em->persist($comment);

            // adding issue collaborator
            $issue->addCollaborator($author);
            $em->persist($issue);
            $em->flush();

            // adding 'post comment' project activity
            $activity = new Activity();
            $activity->setIssue($issue)
                ->setProject($issue->getProject())
                ->setEntityId($comment->getId())
                ->setEntity('Comment')
                ->setType(Activity::COMMENT_POST_TYPE)
                ->setCreatedAt(new \DateTime());
            $em->persist($activity);
            $em->flush();

            return $this->redirect($this->generateUrl('issue_view', ['id' => $issue->getId()]));
        }

        return $this->render(
            'BugTrackerBundle:issue:view.html.twig',
            [
                'issue' => $issue,
                'comment_form' => $form->createView()
            ]
        );
    }
}
