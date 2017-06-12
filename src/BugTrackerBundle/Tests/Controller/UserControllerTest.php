<?php

namespace Appbundle\Tests\Controller;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use BugTrackerBundle\Entity\User as UserEntity;
use BugTrackerBundle\Entity\Project as ProjectEntity;
use BugTrackerBundle\Entity\Issue as IssueEntity;
use BugTrackerBundle\Entity\Activity as ActivityEntity;
use BugTrackerBundle\Helper\Issue as IssueHelper;

class DefaultControllerTest extends WebTestCase
{
    private $client;
    private $doctrine;
    private $encoder;

    private $entitiesToRemove;

    public function setUp()
    {
        $this->client = static::createClient();
        $this->doctrine = $this->client->getContainer()->get('doctrine');
        $this->encoder = $this->client->getContainer()->get('security.password_encoder');
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
     * Unauthorized user redirect test
     * @dataProvider loginFormRedirectForUsersDataProvider
     */
    public function testLoginFormRedirectForUnregiteredUsers($uri)
    {
        $this->client->request('GET', $uri);
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $crawler = $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertGreaterThan(0, $crawler->filter('form#loginForm')->count());
    }

    /**
     * @return array
     */
    public function loginFormRedirectForUsersDataProvider()
    {
        return [
            ['/'],
            ['/user/list'],
            ['/issue/list']
        ];
    }

    public function testUserDashboardView()
    {
        $data = $this->userPagesFixture();
        $user = $data[0];
        $issue = $data[2];

        $this->login($user);

        $crawler = $this->client->request('GET', '/');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $content = $this->client->getResponse()->getContent();
        $this->assertContains('Dashboard', $content);
        $this->assertContains('Activity', $content);
        $this->assertEquals(1, $crawler->filter('.activities a.issue-link')->count());
        $this->assertContains('Opened/Reopened Issues', $content);
        $this->assertEquals(1, $crawler->filter('.issues a.issue-link')->count());
        $this->assertGreaterThan(0, $crawler->filter('html:contains("'. $issue->getCode() .'")')->count());
    }

    public function testUserProfileView()
    {
        $data = $this->userPagesFixture();
        $user = $data[0];
        $issue = $data[2];

        $this->login($user);

        $crawler = $this->client->request('GET', '/user/' . $user->getId());
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $content = $this->client->getResponse()->getContent();
    }

    /**
     * @return array
     */
    private function userPagesFixture()
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
        $type = current($this->issueHelper->getIssueTypesToChange($issue));
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

        return [$user, $project, $issue, $activity];
    }

    /**
     * @param UserEntity $user
     */
    private function login($user)
    {
        $session = $this->client->getContainer()->get('session');

        $firewall = 'secured_area';
        $token = new UsernamePasswordToken($user, $user->getPassword(), "secured_area", $user->getRoles());
        $session->set('_security_' . $firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }
}
