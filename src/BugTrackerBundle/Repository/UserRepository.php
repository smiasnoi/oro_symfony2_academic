<?php
namespace BugTrackerBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormInterface;
use BugTrackerBundle\Entity\User;

class UserRepository extends EntityRepository
{
    private $searchedPageSize = 20;

    /**
     * @param $pageSize
     * @return $this
     */
    public function setSearchedPageSize($pageSize)
    {
        $this->searchedPageSizes = $pageSize;

        return $this;
    }

    /**
     * @param User $user
     * @return int
     */
    public function userExists(User $user)
    {
        $query = $this->getEntityManager()
            ->createQuery(
                "SELECT COUNT(u) FROM BugTrackerBundle:User u WHERE u.username = :username OR u.email = :email"
            )
            ->setParameter('username', $user->getUsername())
            ->setParameter('email', $user->getEmail());

        return $query->getSingleScalarResult();
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

    /**
     * @param int $page
     * @param array $filters
     * @return array
     */
    public function getFilteredCollection($page = 1, $filters = [])
    {
        // @TODO filtering if it's needed
        $queryBuilder = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC');

        $totalsQueryBuilder = clone $queryBuilder;
        $totalItems = $totalsQueryBuilder->select('COUNT(u)')
            ->getQuery()
            ->getSingleScalarResult();
        $pageSize = $this->searchedPageSize;
        $totalPages = ceil($totalItems / $pageSize);

        // limiting resulting collection
        if ($page > $totalPages && $page > 0) {
            $page = $totalPages;
        } elseif ($page < 0 || !$page) {
            $page = 1;
        }
        $offset = $pageSize * ($page - 1);
        $query = $queryBuilder->setMaxResults($pageSize)
            ->setFirstResult($offset)
            ->getQuery();

        return [
            'items' => $query->getResult(),
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'current_page' => $page
        ];
    }
}
