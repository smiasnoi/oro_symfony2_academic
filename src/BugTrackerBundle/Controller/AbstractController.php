<?php
namespace BugTrackerBundle\Controller;
use BugTrackerBundle\Entity\Issue;
use BugTrackerBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use BugTrackerBundle\Entity\User;

class AbstractController extends Controller
{
    /**
     * @param $role
     * @param User $entityOwner
     */
    protected function roleOrEntityOwnerAccessCheck($role, User $entityOwner)
    {
        if (!$this->isGranted($role) && $this->getUser()->getId() != $entityOwner->getId()) {
            throw $this->createAccessDeniedException('Unable to access this page!');
        }
    }

    /**
     * @param string $role
     * @param Issue $issue
     */
    protected function checkIfUserCanHandleIssue($role, Issue $issue)
    {
        $project = $issue->getProject();
        if (!$this->isGranted($role) && !$this->isUserProjectMember($project)) {
            throw $this->createAccessDeniedException('Unable to access this page!');
        }
    }

    /**
     * @param string $role
     * @param Project $project
     */
    protected function checkIfUserCanHandleProject($role, Project $project)
    {
        if (!$this->isGranted($role) && !$this->isUserProjectMember($project)) {
            throw $this->createAccessDeniedException('Unable to access this page!');
        }
    }

    /**
     * @param Project $project
     * @return int
     */
    protected function isUserProjectMember(Project $project)
    {
        $em = $this->getDoctrine()->getEntityManager();

        return $em->createQueryBuilder()->select('COUNT(p)')
            ->from('BugTrackerBundle:Project', 'p')
            ->innerJoin('p.members', 'm')
            ->where('m.id=:user_id AND p.id=:project_id')
            ->setParameter('user_id', $this->getUser()->getId())
            ->setParameter('project_id', (int)$project->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
