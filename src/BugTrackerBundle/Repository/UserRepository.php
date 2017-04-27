<?php
namespace BugTrackerBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormInterface;
use BugTrackerBundle\Entity\User;

class UserRepository extends EntityRepository
{
    /**
     * @param User $user
     * @return int
     */
    public function userExists(User $user)
    {
        $userIdExpression = $user->getId() ? ' AND u.id <> :id' : null;
        $query = $this->getEntityManager()
            ->createQuery(
                "SELECT COUNT(u) 
                 FROM BugTrackerBundle:User u 
                 WHERE (u.username = :username OR u.email = :email)$userIdExpression"
            )
            ->setParameter('username', $user->getUsername())
            ->setParameter('email', $user->getEmail());
        if ($userIdExpression) {
            $query->setParameter('id', $user->getId());
        }

        return $query->getSingleScalarResult();
    }

    /**
     * @param User $user
     * @return User
     */
    public function save(User $user)
    {
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }
}
