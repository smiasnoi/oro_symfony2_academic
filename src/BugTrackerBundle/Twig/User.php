<?php

namespace BugTrackerBundle\Twig;

use BugTrackerBundle\Entity\Issue;
use BugTrackerBundle\Entity\Project;
use BugTrackerBundle\Repository\UserRepository;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;

class User extends \Twig_Extension
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
            new \Twig_SimpleFunction('bt_project_members', [$this, 'getProjectMembers']),
        ];
    }

    /**
     * @param Project $project
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getProjectMembers(Project $project)
    {
        $page = (int)$this->request->query->get(UserRepository::PAGE_VAR) ?: 1;
        $pagination = [UserRepository::KEY_PAGE => $page];

        return $this->em->getRepository('BugTrackerBundle:User')
            ->findProjectMembers($project, $pagination);
    }
}
