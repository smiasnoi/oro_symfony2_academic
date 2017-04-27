<?php

namespace BugTrackerBundle\Controller;

use BugTrackerBundle\Entity\Activity;
use BugTrackerBundle\Entity\Comment;
use BugTrackerBundle\Entity\Issue;
use Doctrine\Common\Collections\Criteria;
use BugTrackerBundle\Form\Issue\CommentType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BugTrackerBundle\Helper\Pagination;

class IssueController extends Controller
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
            $snappedData = [
                'issue_code' => $issue->getCode(),
                'comment_body' => $comment->getBody()
            ];
            $activity->setIssue($issue)
                ->setProject($issue->getProject())
                ->setUser($this->getUser())
                ->setEntityId($comment->getId())
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
                'comments' => $comments,
                'activities' => $activities,
                'comment_form' => $form->createView(),
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
    public function editAction(Issue $issue)
    {
        // @TODO implement new issue form for storie's subtask creation
        return new Response();
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
        $issuesTotalsQb = clone $issues;
        $totalIssueItems = $issuesTotalsQb->select('COUNT(i)')
            ->getQuery()
            ->getSingleScalarResult();
        $totalIssuesPages = ceil($totalIssueItems / self::ISSUES_PAGE_SIZE);
        $issuesPagesInfo = $helper->getPrevNextUrls(self::ISSUES_PAGE_VAR, $totalIssuesPages, 'issues_list');
        $offset = self::ISSUES_PAGE_SIZE * ($issuesPagesInfo['current_page'] - 1);
        $filteredIssues = $issues->setMaxResults(self::ISSUES_PAGE_SIZE)
            ->setFirstResult($offset)
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
}
