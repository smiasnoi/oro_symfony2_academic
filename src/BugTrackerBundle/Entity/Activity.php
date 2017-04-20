<?php

namespace BugTrackerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="activity",
 *     indexes={
 *         @ORM\Index(name="entity_id_idx", columns={"entity_id"}),
 *         @ORM\Index(name="type_idx", columns={"type"}),
 *         @ORM\Index(name="created_idx", columns={"created_at"})
 *     })
 */
class Activity
{
    const COMMENT_POST_TYPE = 'comment_post';
    const NEW_ISSUE_TYPE = 'new_issue';
    const ISSUE_STATUS_CHANGE_TYPE = 'issue_change_status';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Project")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id")
     */
    private $project;

    /**
     * @ORM\ManyToOne(targetEntity="Issue")
     * @ORM\JoinColumn(name="issue_id", referencedColumnName="id")
     */
    private $issue;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $type;

    /**
     * @ORM\Column(type="integer")
     */
    private $entityId;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $entity;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $snappedData;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

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
     * Set type
     *
     * @param string $type
     * @return Activity
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set entityId
     *
     * @param integer $entityId
     * @return Activity
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * Get entityId
     *
     * @return integer 
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set entity
     *
     * @param string $entity
     * @return Activity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity
     *
     * @return string 
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Activity
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set issue
     *
     * @param \BugTrackerBundle\Entity\Issue $issue
     * @return Activity
     */
    public function setIssue(\BugTrackerBundle\Entity\Issue $issue = null)
    {
        $this->issue = $issue;

        return $this;
    }

    /**
     * Get issue
     *
     * @return \BugTrackerBundle\Entity\Issue 
     */
    public function getIssue()
    {
        return $this->issue;
    }

    /**
     * Set issue
     *
     * @param \BugTrackerBundle\Entity\Project $project
     * @return Activity
     */
    public function setProject(\BugTrackerBundle\Entity\Project $project = null)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get issue
     *
     * @return \BugTrackerBundle\Entity\Project
     */
    public function getProject()
    {
        return $this->project;
    }


    /**
     * Set snappedData
     *
     * @param array $snappedData
     * @return Activity
     */
    public function setSnappedData($snappedData)
    {
        $this->snappedData = $snappedData;

        return $this;
    }

    /**
     * Get snappedData
     *
     * @return array
     */
    public function getSnappedData()
    {
        return $this->snappedData;
    }

    /**
     * Set user
     *
     * @param \BugTrackerBundle\Entity\User $user
     * @return Activity
     */
    public function setUser(\BugTrackerBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get issue
     *
     * @return \BugTrackerBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}
