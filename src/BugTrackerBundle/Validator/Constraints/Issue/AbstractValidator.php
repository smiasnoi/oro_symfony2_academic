<?php
namespace BugTrackerBundle\Validator\Constraints\Issue;

use Symfony\Component\Validator\ConstraintValidator;
use Doctrine\ORM\EntityManager;
use BugTrackerBundle\Helper\Issue as IssueHelper;

abstract class AbstractValidator extends ConstraintValidator
{
    protected $em;
    protected $unitOfWork;
    protected $helper;

    /**
     * IssueTypeValidator constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em, IssueHelper $helper)
    {
        $this->em = $em;
        $this->unitOfWork = $em->getUnitOfWork();
        $this->helper = $helper;
    }
}