<?php
namespace BugTrackerBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use BugTrackerBundle\Entity\Issue;

class IssueRepository extends EntityRepository
{
}
