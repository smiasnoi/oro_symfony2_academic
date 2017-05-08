<?php
namespace BugTrackerBundle\Repository;

use BugTrackerBundle\Entity\Issue;
use BugTrackerBundle\Entity\Project;
use BugTrackerBundle\Entity\User;
use Doctrine\Common\Collections\Criteria;

class ActivityRepository extends Paginated
{
    const PAGE_VAR = 'ap';

    /**
     * @param Project $project
     * @param array $pagination
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findAllByProject(Project $project, $pagination = [])
    {
        $criteria = new Criteria();

        $pagination = array_merge($this->getDefaultPagination(), $pagination);
        $page = $pagination[static::KEY_PAGE];
        $pageSize = $pagination[static::KEY_PAGE_SIZE];
        $offset = ($page - 1) * $pageSize;

        $items = $project->getActivities();
        $totalPages = ceil($items->count() / $pageSize);
        $criteria->setFirstResult($offset)
            ->setMaxResults($pageSize)
            ->orderBy(['createdAt' => Criteria::DESC]);
        $items = $items->matching($criteria);

        $result = [static::KEY_ITEMS => $items, static::KEY_TOTAL_PAGES => $totalPages];
        return array_merge($result, $pagination);
    }

    /**
     * @param Issue $issue
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findAllByIssue(Issue $issue)
    {
        return $this->createQueryBuilder('a')
            ->where('a.issue=:issue')
            ->setParameter('issue', $issue)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User $user
     * @param array $pagination
     * @return array
     */
    public function findAllByUser(User $user, $pagination = [])
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.user=:user')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'DESC');

        return $this->getPaginatedResultForQuery($qb, 'a', $pagination);
    }
}
