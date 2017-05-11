<?php

namespace BugTrackerBundle\Helper;

use BugTrackerBundle\Entity\Issue as IssueEntity;

class Issue
{
    public function __construct()
    {
    }

    /**
     * Issue statuses vocabulary
     * @return array
     */
    protected function getAllStatuses()
    {
        return [
            'new' => 'New',
            'reopened' => 'Reopened',
            'in_progress' => 'In progress',
            'closed' => 'Closed',
            'resolved' => 'Resolved'
        ];
    }

    /**
     * @param string $code
     * @return string
     */
    public function getStatusLabel($code)
    {
        $allStatuses = $this->getAllStatuses();
        return isset($allStatuses[$code]) ? $allStatuses[$code] : $code;
    }

    /**
     * @param Issue $issue
     * @return array
     */
    public function getIssueStatusesToChange(IssueEntity $issue)
    {
        $allStatuses = $this->getAllStatuses();

        switch ($issue->getStatus()) {
            case 'new':
            case 'reopened':
                $allowedStatuses = ['in_progress', 'closed'];
                break;
            case 'closed':
                $allowedStatuses = ['reopened'];
                break;
            case 'in_progress':
                $allowedStatuses = ['closed', 'resolved'];
                break;
            default:
                $allowedStatuses = [];
        }

        return array_intersect_key($allStatuses, array_flip($allowedStatuses));
    }
}
