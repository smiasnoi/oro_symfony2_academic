<?php

namespace BugTrackerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="BugTrackerBundle\Repository\UserRepository")
 * @ORM\Table(name="users", indexes={@ORM\Index(name="roles_idx", columns={"roles"})})
 */
class User implements UserInterface, \Serializable
{
    const ADMIN_ROLE = 'ROLE_ADMIN';
    const MANAGER_ROLE = 'ROLE_MANAGER';
    const OPERATOR_ROLE = 'ROLE_OPERATOR';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=32, unique=true)
     *
     * @Assert\NotBlank()
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=128)
     *
     * @Assert\NotBlank()
     */
    private $fullname;

    /**
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank(groups={"registration"})
     */
    private $password_hash;

    /**
     * @Assert\NotBlank(groups={"registration"})
     */
    private $cpassword;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"user_edit"})
     */
    private $roles;

    /**
     * @ORM\OneToMany(targetEntity="Activity", mappedBy="user")
     */
    private $activities;

    /**
     * @return array
     */
    static public function availableRoles()
    {
        return [
            self::OPERATOR_ROLE => 'Operator',
            self::MANAGER_ROLE => 'Manager',
            self::ADMIN_ROLE => 'Administrator'
        ];
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->activities = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set fullname
     *
     * @param string $fullname
     * @return User
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * Get fullname
     *
     * @return string
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Set password_hash
     *
     * @param string $passwordHash
     * @return User
     */
    public function setPassword($passwordHash)
    {
        if ($passwordHash) {
            $this->password_hash = $passwordHash;
        }

        return $this;
    }

    /**
     * Get password_hash
     *
     * @Assert\NotBlank(groups={"user_register"})
     * @Assert\Length(min=7)
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password_hash;
    }

    /**
     *
     * @return User
     */
    public function setCpassword($cpassword)
    {
        $this->cpassword = $cpassword;

        return $this;
    }

    /**
     * @Assert\NotBlank(groups={"user_register"})
     *
     * @return null
     */
    public function getCpassword()
    {
        return $this->cpassword;
    }

    /**
     * Gets salt
     * @return null
     */
    public function getSalt()
    {
        return null;
    }

    public function eraseCredentials()
    {
    }

    /**
     * Set roles
     *
     * @param array $roles
     * @return User
     */
    public function setRoles($roles)
    {
        $this->roles = implode(',', (array)$roles);

        return $this;
    }

    /**
     * Get roles
     *
     * @return string
     */
    public function getRoles()
    {
        return explode(',', $this->roles);
    }

    /**
     * Add activity
     *
     * @param \BugTrackerBundle\Entity\Activity $activity
     * @return Issue
     */
    public function addActivity(\BugTrackerBundle\Entity\Activity $activity)
    {
        $this->activities[] = $activity;

        return $this;
    }

    /**
     * Remove activity
     *
     * @param \BugTrackerBundle\Entity\Activity $acactivity
     */
    public function removeActivity(\BugTrackerBundle\Entity\Activity $acactivity)
    {
        $this->activities->removeElement($acactivity);
    }

    /**
     * Get activities
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize([
            $this->id,
            $this->username,
            $this->password_hash
        ]);
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password_hash
            ) = unserialize($serialized);
    }
}
