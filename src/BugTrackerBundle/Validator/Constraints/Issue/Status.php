<?php
namespace BugTrackerBundle\Validator\Constraints\Issue;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Status extends Constraint
{
    public $statusProperty = "status";
    public $message = "issue.status.not_valid";

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
