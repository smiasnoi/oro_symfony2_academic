<?php

namespace Appbundle\Tests\Controller;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class DefaultControllerTest extends WebTestCase
{
    private $client = null;

    public function setUp()
    {
        $this->client = static::createClient();
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

    public function loginFormRedirectForUsersDataProvider()
    {
        return [
            ['/'],
            ['/user/list'],
            ['/issue/list']
        ];
    }

    private function login($username = 'testuser')
    {
        //$session = $this->client->getContainer()->get('session');
    }
}
