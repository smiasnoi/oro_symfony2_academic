<?php
namespace BugTrackerBundle\Repository;

use BugTrackerBundle\Entity\Project;
use BugTrackerBundle\Entity\User;
use Doctrine\Common\Collections\Criteria;

class UserRepository extends Paginated
{
    const PAGE_VAR = 'up';

    /**
     * @param User $user
     * @return int
     */
    public function userExists(User $user)
    {
        $userIdExpression = $user->getId() ? ' AND u.id <> :id' : null;
        $query = $this->getEntityManager()
            ->createQuery(
                "SELECT COUNT(u) 
                 FROM BugTrackerBundle:User u 
                 WHERE (u.username = :username OR u.email = :email)$userIdExpression"
            )
            ->setParameter('username', $user->getUsername())
            ->setParameter('email', $user->getEmail());
        if ($userIdExpression) {
            $query->setParameter('id', $user->getId());
        }

        return $query->getSingleScalarResult();
    }

    /**
     * @param array $pagination
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findAllPaginated($pagination = [], $filters = [])
    {
        $qb = $this->createQueryBuilder('u');
        foreach ($filters as $filter => $filterData) {
            $method = $filter . "Filter";
            if (method_exists($this, $method)) {
                $this->$method($qb, $filterData);
            }
        }

        return $this->getPaginatedResultForQuery($qb, 'u', $pagination);
    }

    /**
     * @param $qb
     * @param $criteria
     */
    protected function searchCriteriaFilter($qb, $criteria)
    {
        if (trim($criteria)) {
            $criteria = '%' . preg_replace('/\s+/', '%', trim($criteria)) . '%';
            $qb->where('u.fullname like :criteria or u.email like :criteria or u.username like :criteria')
                ->setParameter('criteria', $criteria);
        }
    }

    /**
     * @param Project $project
     * @param array $pagination
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findProjectMembers(Project $project, $pagination = [])
    {
        $criteria = new Criteria();

        $pagination = array_merge($this->getDefaultPagination(), $pagination);
        $page = $pagination[static::KEY_PAGE];
        $pageSize = $pagination[static::KEY_PAGE_SIZE];
        $offset = ($page - 1) * $pageSize;

        $items = $project->getMembers();
        $totalPages = ceil($items->count() / $pageSize);
        $criteria->setFirstResult($offset)
            ->setMaxResults($pageSize);
        $items = $items->matching($criteria);

        $result = [static::KEY_ITEMS => $items, static::KEY_TOTAL_PAGES => $totalPages];
        return array_merge($result, $pagination);
    }

    /**
     * @param User $user
     * @return User
     */
    public function save(User $user)
    {
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }
}
