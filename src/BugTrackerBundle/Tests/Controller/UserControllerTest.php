<?php
namespace BugTrackerBundle\Tests\Controller;

use BugTrackerBundle\Tests\WebTestCaseAbstract;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use BugTrackerBundle\Entity\User as UserEntity;

class UserControllerTest extends WebTestCaseAbstract
{
    /**
     * Unauthorized user redirect test. Checks firewall configuration
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
        $data = $this->allEntitiesChainFixture();
        $user = $data['user'];
        $issue = $data['issue'];

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

    /*public function testUserProfileView()
    {
        $data = $this->userPagesFixture();
        $user = $data[0];
        $issue = $data[2];

        $this->login($user);

        $crawler = $this->client->request('GET', '/user/' . $user->getId());
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $content = $this->client->getResponse()->getContent();
    }*/

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
