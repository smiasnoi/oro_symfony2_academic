<?php
namespace BugTrackerBundle\Validator\Constraints\Issue;

use Symfony\Component\Validator\Constraint;
use BugTrackerBundle\Entity\Issue as IssueEntity;

class StatusValidator extends AbstractValidator
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

        $statusField = $constraint->statusProperty;
        $originalData = $this->unitOfWork->getOriginalEntityData($issue);
        $origIssueStatus = isset($originalData[$statusField]) ? $originalData[$statusField] : null;
        $issueStatus = $issue->getStatus();

        $allowedTypes = $this->helper->getAllowedStatusesToChange($origIssueStatus, is_object($issue->getParent()));
        if ($origIssueStatus != $issueStatus && !in_array($issueStatus, $allowedTypes)) {
            $this->context->buildViolation($constraint->message)
                ->atPath($statusField)
                ->setParameter('%old_status%', $origIssueStatus)
                ->setParameter('%new_status%', $issueStatus)
                ->addViolation();
        }
    }
}
