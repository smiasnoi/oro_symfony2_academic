<?php

namespace BugTrackerBundle\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class Pagination
{
    private $container;
    private $request;

    /**
     * Pagination constructor.
     * @param ContainerInterface $container
     * @param Request $request
     */
    public function __construct(ContainerInterface $container, Request $request)
    {
        $this->container = $container;
        $this->request = $request;
    }

    /**
     * @param string $queryVar
     * @param int $totalPages
     * @param string $route
     * @param array $queryParams
     * @return array
     */
    public function getPrevNextUrls($queryVar, $totalPages, $route, $queryParams = [])
    {
        $result = [
            'prev_page_url' => null,
            'next_page_url' => null,
            'total_pages' => $totalPages
        ];

        if (!$queryParams) {
            $queryParams = $this->request->query->all();
        }

        $result['current_page'] = $page = !empty($queryParams[$queryVar]) ? $queryParams[$queryVar] : 1;

        $_queryParams = $queryParams;
        if ($page > 1) {
            $_queryParams[$queryVar]--;
            if ($_queryParams[$queryVar] == 1) {
                unset($_queryParams[$queryVar]);
            }
            $result['prev_page_url'] = $this->container->get('router')->generate($route, $_queryParams);
        }

        $_queryParams = $queryParams;
        if ($page < $totalPages) {
            if (isset($_queryParams[$queryVar])) {
                $_queryParams[$queryVar]++;
            } else {
                $_queryParams[$queryVar] = 2;
            }
            $result['next_page_url'] = $this->container->get('router')->generate($route, $_queryParams);
        }

        return $result;
    }
}
