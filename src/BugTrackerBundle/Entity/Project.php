<?php

namespace BugTrackerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="BugTrackerBundle\Repository\ProjectRepository")
 * @ORM\Table(name="projects")
 */
class Project
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    private $label;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     */
    private $summary;

    /**
     * @ORM\Column(type="string", length=32)
     * @Assert\NotBlank()
     */
    private $code;

    /**
     * Many Users have Many Projects.
     * @ORM\ManyToMany(targetEntity="User")
     * @ORM\JoinTable(name="users_projects",
     *      joinColumns={@ORM\JoinColumn(name="project_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     *      )
     */
    private $members;

    /**
     * @ORM\OneToMany(targetEntity="Activity", mappedBy="project")
     */
    private $activities;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->members = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set label
     *
     * @param string $label
     * @return Project
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string 
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set summary
     *
     * @param string $summary
     * @return Project
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * Get summary
     *
     * @return string 
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Project
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Add members
     *
     * @param \BugTrackerBundle\Entity\User $member
     * @return Project
     */
    public function addMember(\BugTrackerBundle\Entity\User $member)
    {
        if (!$this->members->contains($member)) {
            $this->members[] = $member;
        }

        return $this;
    }

    /**
     * Remove members
     *
     * @param \BugTrackerBundle\Entity\User $member
     */
    public function removeMember(\BugTrackerBundle\Entity\User $member)
    {
        $this->members->removeElement($member);
    }

    /**
     * Get members
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMembers()
    {
        return $this->members;
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
     * @param \BugTrackerBundle\Entity\Activity $activity
     */
    public function removeActivity(\BugTrackerBundle\Entity\Activity $activity)
    {
        $this->activities->removeElement($activity);
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
}
