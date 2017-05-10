<?php

namespace BugTrackerBundle\Security;

use BugTrackerBundle\Entity\User;
use BugTrackerBundle\Entity\Comment;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CommentVoter extends Voter
{
    protected $decisionManager;

    // these strings are just invented: you can use anything
    const EDIT = 'edit';
    const DELETE = 'delete';

    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::EDIT, self::DELETE]) || !$subject instanceof Comment) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Comment $comment */
        $comment = $subject;
        switch ($attribute) {
            case self::EDIT:
            case self::DELETE:
                return $this->isUserCommentOwner($comment, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    /**
     * @param Comment $comment
     * @param User $user
     * @return bool
     */
    protected function isUserCommentOwner(Comment $comment, User $user)
    {
        $author = $comment->getAuthor();
        return $author && $author->getId() === $user->getId();
    }
}
