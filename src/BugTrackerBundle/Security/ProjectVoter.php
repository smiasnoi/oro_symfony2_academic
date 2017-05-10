<?php

namespace BugTrackerBundle\Security;

use BugTrackerBundle\Entity\Project;
use BugTrackerBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProjectVoter extends Voter
{
    protected $em;
    protected $decisionManager;
    protected $validated = [];

    // these strings are just invented: you can use anything
    const HANDLE = 'handle';

    public function __construct(EntityManager $entityManager, AccessDecisionManagerInterface $decisionManager)
    {
        $this->em = $entityManager;
        $this->decisionManager = $decisionManager;
    }

    /**
     * @return AccessDecisionManagerInterface
     */
    public function getDecisionManager()
    {
        return $this->decisionManager;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::HANDLE]) || !$subject instanceof Project) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if ($this->decisionManager->decide($token, ['ROLE_MANAGER'])) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Project $project */
        $project = $subject;
        switch ($attribute) {
            case self::HANDLE:
                return $this->canHandleProject($project, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    /**
     * @param Project $project
     * @param User $user
     * @return bool
     */
    public function canHandleProject(Project $project, User $user)
    {
        $key = sprintf('pr_%d_%d', $project->getId(), $user->getId());
        if (!isset($this->validated[$key])) {
            $accessFlag = (bool)$this->createQueryBuilder()->select('COUNT(p)')
                ->from('BugTrackerBundle:Project', 'p')
                ->innerJoin('p.members', 'm')
                ->where('m.id=:user_id AND p.id=:project_id')
                ->setParameter('user_id', $this->getUser()->getId())
                ->setParameter('project_id', (int)$project->getId())
                ->getQuery()
                ->getSingleScalarResult();

            $this->validated[$key] = $accessFlag;
        }

        return $this->validated[$key];
    }
}
