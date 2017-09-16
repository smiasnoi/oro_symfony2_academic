<?php
namespace BugTrackerBundle\Validator\Constraints\Issue;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Type extends Constraint
{
    public $typeProperty = "type";
    public $message = "Not allowed type change for issue from '%old_type%' to '%new_type%'";

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
