<?php
namespace BugTrackerBundle\Repository;

use BugTrackerBundle\Entity\User;
use BugTrackerBundle\Entity\Issue;

class IssueRepository extends Paginated
{
    const PAGE_VAR = 'ip';

    /**
     * @param User $collaborator
     * @param array $pagination
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findAllByCollaborator(User $collaborator = null, $pagination = [])
    {
        $qb = $this->createQueryBuilder('i');
        if ($collaborator) {
            $qb->innerJoin('i.collaborators', 'c')
                ->where('c.id=:user_id')
                ->setParameter('user_id', $collaborator->getId());
        }

        return $this->getPaginatedResultForQuery($qb, 'i', $pagination);
    }

    /**
     * @param User $assignee
     * @param array $pagination
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findAllOpenedByAssignee(User $assignee, $pagination)
    {
        $qb = $this->createQueryBuilder('i')
            ->where('i.status IN(:statuses) AND i.assignee=:assignee')
            ->orderBy('i.updatedAt', 'DESC')
            ->setParameter(':statuses', ['new', 'reopened', 'in_progress'])
            ->setParameter(':assignee', $assignee->getId());

        return $this->getPaginatedResultForQuery($qb, 'i', $pagination);
    }

    /**
     * @param User $collaborator
     * @param $pagination
     * @return array
     */
    public function findAllOpenedByCollaborator(User $collaborator, $pagination)
    {
        $qb = $this->createQueryBuilder('i')
            ->innerJoin('i.collaborators', 'c', 'WITH', 'c.id=:user_id')
            ->where('i.status IN(:statuses)')
            ->orderBy('i.updatedAt', 'DESC')
            ->setParameter(':statuses', ['open', 'reopened'])
            ->setParameter(':user_id', $collaborator->getId());

        return $this->getPaginatedResultForQuery($qb, 'i', $pagination);
    }
}
