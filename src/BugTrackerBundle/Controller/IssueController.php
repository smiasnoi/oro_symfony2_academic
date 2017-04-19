<?php

namespace BugTrackerBundle\Controller;

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
            $em = $this->getDoctrine()->getEntityManager();
            $comment->setCreatedAt(new \DateTime());
            $em->persist($comment);
            $issue->addCollaborator($author);
            $em->persist($issue);
            $em->flush();
            $this->redirect($this->generateUrl('issue_view', ['id' => $issue->getId()]));
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
