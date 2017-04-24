<?php

namespace BugTrackerBundle\Controller;

use BugTrackerBundle\Entity\Project;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ProjectController extends Controller
{
    CONST ACTIVITIES_PAGE_SIZE = 10;
    CONST ACTIVITIES_PAGE_VAR = 'acp';

    CONST MEMBERS_PAGE_SIZE = 10;
    CONST MEMBERS_PAGE_VAR = 'up';

    /**
     * @Route("/project/{id}", name="project_view", requirements={
     *     "id": "\d+"
     * })
     * @Method({"GET"})
     */
    public function viewAction(Request $request, Project $project)
    {
        $pagingParams = ['id' => $project->getId()];
        $query = $request->query;

        $pageVarsPrefixes = [
            'activities' => self::ACTIVITIES_PAGE_VAR,
            'members' => self::MEMBERS_PAGE_VAR
        ];
        foreach ($pageVarsPrefixes as $pageVarPrefix => $queryVar) {
            $pageVar = $pageVarPrefix . 'Page';
            $$pageVar = 1;
            if ($page = $query->get($queryVar)) {
                $pagingParams[$queryVar] = $$pageVar = $page;
            }
        }

        $activities = $project->getActivities();
        $activitiesTotalPages = ceil($activities->count() / self::ACTIVITIES_PAGE_SIZE);
        $members = $project->getMembers();
        $membersTotalPages = ceil($members->count() / self::MEMBERS_PAGE_SIZE);

        foreach ($pageVarsPrefixes as $pageVarPrefix => $queryVar) {
            $pageVar = $pageVarPrefix . 'Page';
            $totalPagesVar = $pageVarPrefix . 'TotalPages';
            $prevPageUrlVar = $pageVarPrefix . 'PrevPageUrl';
            $nextPageUrlVar = $pageVarPrefix . 'NextPageUrl';

            $_pagingParams = $pagingParams;
            if ($$pageVar > 1) {
                $_pagingParams[$queryVar]--;
                if ($_pagingParams[$queryVar] == 1) {
                    unset($_pagingParams[$queryVar]);
                }
                $$prevPageUrlVar = $this->generateUrl('project_view', $_pagingParams);
            } else {
                $$prevPageUrlVar = null;
            }

            $_pagingParams = $pagingParams;
            if ($$pageVar < $$totalPagesVar) {
                if (isset($_pagingParams[$queryVar])) {
                    $_pagingParams[$queryVar]++;
                } else {
                    $_pagingParams[$queryVar] = 2;
                }
                $$nextPageUrlVar = $this->generateUrl('project_view', $_pagingParams);
            } else {
                $$nextPageUrlVar = null;
            }
        }

        $criteria = new Criteria();
        $offset = ($membersPage - 1) * self::MEMBERS_PAGE_SIZE;
        $criteria->setFirstResult($offset)
            ->setMaxResults(self::MEMBERS_PAGE_SIZE);
        $filteredMembers = $members->matching($criteria);

        $offset = ($activitiesPage - 1) * self::ACTIVITIES_PAGE_SIZE;
        $criteria->setFirstResult($offset)
            ->setMaxResults(self::ACTIVITIES_PAGE_SIZE)
            ->orderBy(['createdAt' => Criteria::DESC]);
        $filteredActivities = $activities->matching($criteria);

        return $this->render(
            'BugTrackerBundle:project:view.html.twig',
            [
                'project' => $project,
                'activities' => [
                    'items' => $filteredActivities,
                    'total_pages' => $activitiesTotalPages,
                    'prev_page_url' => $activitiesPrevPageUrl,
                    'next_page_url' => $activitiesNextPageUrl,
                    'current_page' => $activitiesPage
                ],
                'members' => [
                    'items' => $filteredMembers,
                    'total_pages' => $membersTotalPages,
                    'prev_page_url' => $membersPrevPageUrl,
                    'next_page_url' => $membersNextPageUrl,
                    'current_page' => $membersPage
                ],
            ]
        );
    }
}
