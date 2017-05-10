<?php

namespace BugTrackerBundle\Twig;

use BugTrackerBundle\Entity\Issue;
use BugTrackerBundle\Entity\Project;
use BugTrackerBundle\Entity\User;
use BugTrackerBundle\Repository\ActivityRepository;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;

class Activity extends \Twig_Extension
{
    private $em;
    private $request;

    public function __construct(EntityManager $entityManager, RequestStack $requestStack)
    {
        $this->em = $entityManager;
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('bt_issue_activities', [$this, 'getIssueActivities']),
            new \Twig_SimpleFunction('bt_project_activities', [$this, 'getProjectActivities']),
            new \Twig_SimpleFunction('bt_user_activities', [$this, 'getUserActivities']),
        ];
    }

    /**
     * @param Issue $issue
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getIssueActivities(Issue $issue)
    {
        return $this->em->getRepository('BugTrackerBundle:Activity')
            ->findAllByIssue($issue);
    }

    /**
     * @param Project $project
     * @return mixed
     */
    public function getProjectActivities(Project $project)
    {
        $page = (int)$this->request->query->get(ActivityRepository::PAGE_VAR) ?: 1;
        $pagination = [ActivityRepository::KEY_PAGE => $page];

        return $this->em->getRepository('BugTrackerBundle:Activity')
            ->findAllByProject($project, $pagination);
    }

    /**
     * @param User $user
     */
    public function getUserActivities(User $user)
    {
        $page = (int)$this->request->query->get(ActivityRepository::PAGE_VAR) ?: 1;
        $pagination = [ActivityRepository::KEY_PAGE => $page];

        return $this->em->getRepository('BugTrackerBundle:Activity')
            ->findAllByUser($user, $pagination);
    }
}
