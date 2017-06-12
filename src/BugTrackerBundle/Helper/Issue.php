<?php

namespace BugTrackerBundle\Helper;

use BugTrackerBundle\Entity\Issue as IssueEntity;

class Issue
{
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
     * Issue types vocabulary
     * @return array
     */
    protected function getAllTypes()
    {
        return [
            'bug' => 'Bug',
            'subtask' =>'Subtask',
            'story_bug' =>'Story Bug',
            'task' => 'Task',
            'story' => 'Story'
        ];
    }

    /**
     * Issue priorities vocabulary
     * @return array
     */
    public function getPriorities()
    {
        return [
            'low' => 'Low',
            'minor' => 'Minor',
            'major' => 'Major',
            'critical' => 'Critical'
        ];
    }

    /**
     * Issue resolutions vocabulary
     * @return array
     */
    static public function getResolutions()
    {
        return [
            'wont_fix' => 'Won\'t fix',
            'fix' => 'Fix',
            'duplicate' => 'Duplicate',
            'done' => 'Done',
            'incomplete' =>'Incomplete'
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
     * @param IssueEntity $issue
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
            case 'resolved':
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

    /**
     * @param IssueEntity $issue
     * @return array
     */
    public function getIssueTypesToChange(IssueEntity $issue)
    {
        $allTypes = $this->getAllTypes();
        switch ($issue->getType()) {
            case 'bug':
                $allowedTypes = ['bug', 'task', 'story'];
                break;
            case 'subtask':
            case 'story_bug':
                $allowedTypes = ['subtask', 'story_bug'];
                break;
            default:
                $allowedTypes = !$issue->getParent() ? ['bug', 'task', 'story'] : [];
        }

        return array_intersect_key($allTypes, array_flip($allowedTypes));
    }

    /**
     * @return string
     */
    public function getNewStatus()
    {
        return 'new';
    }

    /**
     * @return string
     */
    public function getSubtaskType()
    {
        return 'subtask';
    }
}
