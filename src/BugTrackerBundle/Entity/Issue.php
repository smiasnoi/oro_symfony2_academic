<?php


namespace BugTrackerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="issues")
 */
class Issue
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $summary;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $code;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $priority;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $resolution;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="reporter", referencedColumnName="id")
     */
    private $reporter;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="assignee", referencedColumnName="id")
     */
    private $assignee;

    /**
     * @ORM\ManyToMany(targetEntity="User")
     * @ORM\JoinTable(name="collaborators_issues",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="issue_id", referencedColumnName="id")}
     *      )
     */
    private $collaborators;

    /**
     * @ORM\OneToMany(targetEntity="Issue", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="Issue", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;

    /**
     * Many Users have One Address.
     * @ORM\ManyToOne(targetEntity="Project")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id")
     */
    private $project;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * Issue types vocabulary
     * @return array
     */
    static public function getTypes()
    {
        return [
            'bug' => 'Bug',
            'subtask' =>'Subtask',
            'task' => 'Task',
            'strory' => 'Story'
        ];
    }

    /**
     * Issue priorities vocabulary
     * @return array
     */
    static public function getPriorities()
    {
        return [
            'minor' => 'Minor',
            'major' => 'Major',
            'low' => 'Low',
            'critical' => 'Critical'
        ];
    }

    /**
     * Issue statuses vocabulary
     * @return array
     */
    static public function getStatuses()
    {
        return [
            'open' => 'Open',
            'in_porgress' => 'In progress',
            'closed' => 'Closed'
        ];
    }

    /**
     * Issue resolutions vocabulary
     * @return array
     */
    static public function getResolutions()
    {
        return [
            'wont_fix' => 'Won\'t fix',
            'fix' => 'Fix',
            'duplicate' => 'Duplicate',
            'done' => 'Done',
            'incomplete' =>'Incomplete'
        ];
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->collaborators = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set summary
     *
     * @param string $summary
     * @return Issue
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
     * @return Issue
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
     * Set description
     *
     * @param string $description
     * @return Issue
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Issue
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
     * Set priority
     *
     * @param string $priority
     * @return Issue
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get priority
     *
     * @return string 
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set resolution
     *
     * @param string $resolution
     * @return Issue
     */
    public function setResolution($resolution)
    {
        $this->resolution = $resolution;

        return $this;
    }

    /**
     * Get resolution
     *
     * @return string 
     */
    public function getResolution()
    {
        return $this->resolution;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Issue
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set reporter
     *
     * @param integer $reporter
     * @return Issue
     */
    public function setReporter($reporter)
    {
        $this->reporter = $reporter;

        return $this;
    }

    /**
     * Get reporter
     *
     * @return integer 
     */
    public function getReporter()
    {
        return $this->reporter;
    }

    /**
     * Set assignee
     *
     * @param integer $assignee
     * @return Issue
     */
    public function setAssignee($assignee)
    {
        $this->assignee = $assignee;

        return $this;
    }

    /**
     * Get assignee
     *
     * @return integer 
     */
    public function getAssignee()
    {
        return $this->assignee;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Issue
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Issue
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Add collaborators
     *
     * @param \BugTrackerBundle\Entity\User $collaborator
     * @return Issue
     */
    public function addCollaborator(\BugTrackerBundle\Entity\User $collaborator)
    {
        $this->collaborators[] = $collaborator;

        return $this;
    }

    /**
     * Remove collaborators
     *
     * @param \BugTrackerBundle\Entity\User $collaborator
     */
    public function removeCollaborator(\BugTrackerBundle\Entity\User $collaborator)
    {
        $this->collaborators->removeElement($collaborator);
    }

    /**
     * Get collaborators
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCollaborators()
    {
        return $this->collaborators;
    }

    /**
     * Add children
     *
     * @param \BugTrackerBundle\Entity\Issue $children
     * @return Issue
     */
    public function addChild(\BugTrackerBundle\Entity\Issue $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \BugTrackerBundle\Entity\Issue $children
     */
    public function removeChild(\BugTrackerBundle\Entity\Issue $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param \BugTrackerBundle\Entity\Issue $parent
     * @return Issue
     */
    public function setParent(\BugTrackerBundle\Entity\Issue $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \BugTrackerBundle\Entity\Issue 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set project
     *
     * @param \BugTrackerBundle\Entity\Project $project
     * @return Issue
     */
    public function setProject(\BugTrackerBundle\Entity\Project $project = null)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project
     *
     * @return \BugTrackerBundle\Entity\Project 
     */
    public function getProject()
    {
        return $this->project;
    }
}
