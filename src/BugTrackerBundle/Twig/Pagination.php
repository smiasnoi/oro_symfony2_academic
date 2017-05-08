<?php

namespace BugTrackerBundle\Twig;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RequestStack;
use BugTrackerBundle\Repository\Paginated;

class Pagination extends \Twig_Extension
{
    private $request;
    private $router;

    public function __construct(RequestStack $requestStack, Router $router)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->router = $router;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('bt_pagination', [$this, 'getPagination']),
        ];
    }

    /**
     * @param array $collection
     * @param string $route
     * @param array $queryVars
     * @return array
     */
    public function getPagination($collection, $route, $queryVars)
    {
        $result = [
            'prev_page_url' => null,
            'next_page_url' => null,
            'total_pages' => $collection[Paginated::KEY_TOTAL_PAGES]
        ];

        $queryParams = array_merge($queryVars, $this->request->query->all());
        $pageVar = $collection[Paginated::KEY_PAGE_VAR];

        $result['current_page'] = $page = !empty($queryParams[$pageVar]) ? $queryParams[$pageVar] : 1;

        $_queryParams = $queryParams;
        if ($page > 1) {
            $_queryParams[$pageVar]--;
            if ($_queryParams[$pageVar] == 1) {
                unset($_queryParams[$pageVar]);
            }
            $result['prev_page_url'] = $this->router->generate($route, $_queryParams);
        }

        $_queryParams = $queryParams;
        if ($page < $collection[Paginated::KEY_TOTAL_PAGES]) {
            if (isset($_queryParams[$pageVar])) {
                $_queryParams[$pageVar]++;
            } else {
                $_queryParams[$pageVar] = 2;
            }
            $result['next_page_url'] = $this->router->generate($route, $_queryParams);
        }

        return $result;
    }
}
