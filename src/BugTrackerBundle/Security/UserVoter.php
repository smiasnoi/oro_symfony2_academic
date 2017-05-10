<?php

namespace BugTrackerBundle\Security;

use BugTrackerBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    protected $decisionManager;

    // these strings are just invented: you can use anything
    const EDIT = 'edit';
    const EDIT_ROLE = 'edit_role';

    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::EDIT, self::EDIT_ROLE]) || !$subject instanceof User) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Project $project */
        $profile = $subject;
        switch ($attribute) {
            case self::EDIT:
                return $this->canEditProfile($profile, $user, $token);
            case self::EDIT_ROLE:
                return $this->canEditProfileRole($profile, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    /**
     * @param User $profile
     * @param User $user
     * @return bool
     */
    protected function canEditProfile(User $profile, User $user, TokenInterface $token)
    {
        return $this->decisionManager->decide($token, ['ROLE_ADMIN']) || $profile->getId() === $user->getId();
    }

    /**
     * @param User $profile
     * @param User $user
     * @return bool
     */
    protected function canEditProfileRole(User $profile, User $user, TokenInterface $token)
    {
        return $this->decisionManager->decide($token, ['ROLE_ADMIN']) || $profile->getId() !== $user->getId();
    }
}
