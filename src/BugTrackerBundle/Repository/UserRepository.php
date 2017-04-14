<?php
namespace BugTrackerBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use BugTrackerBundle\Entity\User;

class UserRepository extends EntityRepository
{
    private $encoder;
    private $submitErrors = [];

    public function setEncoder(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;

        return $this;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function validateSubmitedUser(User$user)
    {
        $this->checkEncoder();

        if ($user->getPassword() !== $user->getCpassword()) {
            $this->submitErrors['cpassword'] = "Passwords must be equal";
        }

        if ($this->isUserExists($user)){
            $this->submitErrors['__customer_exists'] = "User with given username or email already exists";
        }

        return $this->submitErrors ? false : true;
    }

    /**
     * @param User $user
     * @return int
     */
    protected function isUserExists(User $user)
    {
        $query = $this->getEntityManager()
            ->createQuery(
                "SELECT COUNT(u) FROM BugTrackerBundle:User u WHERE u.username = :username OR u.email = :email"
            )
            ->setParameter('username', $user->getUsername())
            ->setParameter('email', $user->getEmail());

        return $query->getResult(Query::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * @param User $user
     * @return User
     */
    public function prepareAndSaveOperator(User $user)
    {
        $this->checkEncoder();

        $plainPassword = $user->getPassword();
        $encodedPassword = $this->encoder->encodePassword($user, $plainPassword);
        $user->setPassword($encodedPassword)
            ->setRoles([User::OPERATOR_ROLE]);

        $this->save($user);

        return $user;
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

    /**
     * @param FormInterface $form
     */
    public function appendAdditionalErrorsToForm(FormInterface $form)
    {
        if ($this->submitErrors){
            foreach ($this->submitErrors as $fieldName => $message) {
                try {
                    $field = $form->get($fieldName);
                    $field->addError(new FormError($message));
                } catch (\Exception $e) {
                    $form->addError(new FormError($message));
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function checkEncoder()
    {
        if (!$this->encoder) {
            throw new Exception("Password encoder hasn't been setup");
        }
    }
}
