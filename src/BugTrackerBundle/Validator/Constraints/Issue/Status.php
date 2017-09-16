<?php
namespace BugTrackerBundle\Validator\Constraints\Issue;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Status extends Constraint
{
    public $statusProperty = "status";
    public $message = "Not allowed status change for issue from '%old_status%' to '%new_status%'";

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
