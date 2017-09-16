<?php
namespace BugTrackerBundle\Validator\Constraints\Issue;

use Symfony\Component\Validator\Constraint;
use BugTrackerBundle\Entity\Issue as IssueEntity;

class TypeValidator extends AbstractValidator
{
    /**
     * @param object $issue
     * @param Constraint $constraint
     */
    public function validate($issue,  Constraint $constraint)
    {
        if (!is_object($issue) || (is_object($issue) && !($issue instanceof IssueEntity))) {
            return;
        }

        $typeField = $constraint->typeProperty;
        $originalData = $this->unitOfWork->getOriginalEntityData($issue);
        $origIssueType = isset($originalData[$typeField]) ? $originalData[$typeField] : null;
        $issueType = $issue->getType();

        $allowedTypes = $this->helper->getAllowedTypesToChange($origIssueType, is_object($issue->getParent()));
        if ($origIssueType != $issueType && !in_array($issueType, $allowedTypes)) {
            $this->context->buildViolation($constraint->message)
                ->atPath($typeField)
                ->setParameter('%old_type%', $origIssueType)
                ->setParameter('%new_type%', $issueType)
                ->addViolation();
        }
    }
}
