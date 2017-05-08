<?php
namespace BugTrackerBundle\Repository;

use BugTrackerBundle\Entity\User;
use BugTrackerBundle\Entity\Project;

class ProjectRepository extends Paginated
{
    const PAGE_VAR = 'pp';

    /**
     * @param User $user
     * @param array $pagination
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findAllByUser(User $user = null, $pagination = [])
    {
        $qb = $this->createQueryBuilder('p');
        if ($user) {
            $qb->innerJoin('p.members', 'm')
                ->where('m.id=:user_id')
                ->setParameter('user_id', $user->getId());
        }

        return $this->getPaginatedResultForQuery($qb, 'p', $pagination);
    }
}
