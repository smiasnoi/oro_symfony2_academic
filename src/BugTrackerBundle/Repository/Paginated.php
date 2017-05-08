<?php
namespace BugTrackerBundle\Repository;

use Doctrine\ORM\EntityRepository;

abstract class Paginated extends EntityRepository
{
    const DEFAULT_PAGE_SIZE = 10;

    const PAGE_VAR = 'p';

    const KEY_ITEMS = 'items';
    const KEY_PAGE = 'page';
    const KEY_PAGE_SIZE = 'page_size';
    const KEY_TOTAL_PAGES = 'total_pages';
    const KEY_PAGE_VAR = 'page_var';

    /**
     * @return array
     */
    protected function getDefaultPagination()
    {
        return [
            static::KEY_PAGE => 1,
            static::KEY_PAGE_SIZE => static::DEFAULT_PAGE_SIZE,
            static::KEY_PAGE_VAR => static::PAGE_VAR
        ];
    }

    protected function getPaginatedResultForQuery($qb, $alias, $pagination = [])
    {
        $pagination = array_merge($this->getDefaultPagination(), $pagination);
        $page = $pagination[static::KEY_PAGE];
        $pageSize = $pagination[static::KEY_PAGE_SIZE];
        $offset = ($page - 1) * $pageSize;

        $totalDb = clone $qb;
        $totalItems = $totalDb->select("COUNT($alias)")
            ->getQuery()
            ->getSingleScalarResult();
        $totalPages = ceil($totalItems / $pageSize);

        $items = $qb->setFirstResult($offset)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();

        $result = [static::KEY_ITEMS => $items, static::KEY_TOTAL_PAGES => $totalPages];
        return array_merge($result, $pagination);
    }
}
