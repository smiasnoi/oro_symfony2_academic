<?php

namespace BugTrackerBundle\Security;

use BugTrackerBundle\Entity\Issue;
use BugTrackerBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;


class IssueVoter extends ProjectVoter
{
    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::HANDLE]) || !$subject instanceof Issue) {
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

        /** @var Issue $issue */
        $issue = $subject;
        switch ($attribute) {
            case self::HANDLE:
                return $this->canHandleIssue($issue, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    /**
     * @param Issue $issue
     * @param User $user
     * @return bool
     */
    public function canHandleIssue(Issue $issue, User $user)
    {
        $key = sprintf('is_%d_%d', $issue->getId(), $user->getId());
        if (!isset($this->validated[$key])) {
            $project = $issue->getProject();
            $accessFlag = is_object($project) ? $this->canHandleProject($project, $user) : false;

            $this->validated[$key] = $accessFlag;
        }

        return $this->validated[$key];
    }
}
