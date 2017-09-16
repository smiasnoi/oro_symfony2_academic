<?php

namespace BugTrackerBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use BugTrackerBundle\Helper\Issue as IssueHelper;
use BugTrackerBundle\Entity\User as UserEntity;
use BugTrackerBundle\Entity\Project as ProjectEntity;
use BugTrackerBundle\Entity\Issue as IssueEntity;
use BugTrackerBundle\Entity\Activity as ActivityEntity;

abstract class WebTestCaseAbstract extends WebTestCase
{
    protected $client;
    protected $encoder;
    protected $doctrine;
    protected $validator;
    protected $issueHelper;

    public function setUp()
    {
        $this->client = static::createClient();
        $this->doctrine = $this->client->getContainer()->get('doctrine');
        $this->encoder = $this->client->getContainer()->get('security.password_encoder');
        $this->validator = $this->client->getContainer()->get('validator');
        $this->issueHelper = new IssueHelper();

        $connection = $this->doctrine->getManager()->getConnection();
        $connection->beginTransaction();
        $connection->setAutoCommit(false);
    }

    public function tearDown()
    {
        $this->doctrine->getManager()->getConnection()->rollBack();
    }

    /**
     * @return array
     */
    protected function allEntitiesChainFixture()
    {
        $em = $this->doctrine->getManager();
        $uniqPart = uniqid(mt_rand(), true);

        $user = new UserEntity();
        $encoded = $this->encoder->encodePassword($user, '12345678');
        $user->setEmail($uniqPart . '@oroinc.com')
            ->setUsername($uniqPart . '_user')
            ->setFullname($uniqPart . 'Some Doe')
            ->setPassword($encoded)
            ->setRoles(['ROLE_ADMIN']);
        $em->persist($user);
        $em->flush();

        $project = new ProjectEntity();
        $project->setSummary('Project summary. Content: ' . $uniqPart)
            ->setLabel('Label-' . $uniqPart)
            ->setCode('PACD-' . $uniqPart)
            ->addMember($user);
        $em->persist($project);
        $em->flush();

        $issue = new IssueEntity();
        $type = current($this->issueHelper->getAllowedTypesToChange(null, false));
        $priority = current(array_keys($this->issueHelper->getPriorities()));
        $issue->setCode('IACD-' . $uniqPart)
            ->setSummary('Issue summary. Content: ' . $uniqPart)
            ->setDescription('Issue description. Content: ' . $uniqPart)
            ->setType($type)
            ->setStatus($this->issueHelper->getNewStatus())
            ->setPriority($priority)
            ->setAssignee($user)
            ->setReporter($user)
            ->setProject($project)
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
            ->addCollaborator($user);
        $em->persist($issue);
        $em->flush();

        $activity = new ActivityEntity();
        $snappedData = ['issue_code' => $issue->getCode()];
        $activity->setProject($issue->getProject())
            ->setUser($user)
            ->setEntityId($issue->getId())
            ->setEntity('Issue')
            ->setSnappedData($snappedData)
            ->setType(ActivityEntity::NEW_ISSUE_TYPE)
            ->setCreatedAt(new \DateTime());
        $em->persist($activity);
        $em->flush();

        return [
            'user' => $user,
            'project' => $project,
            'issue' => $issue,
            'activity' => $activity
        ];
    }
}
