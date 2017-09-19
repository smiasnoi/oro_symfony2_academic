<?php
namespace BugTrackerBundle\Validator\Constraints\Issue;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Type extends Constraint
{
    public $typeProperty = "type";
    public $message = 'issue.type.not_valid';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
