<?php

namespace BugTrackerBundle\Tests\Form\Handler;

use BugTrackerBundle\BugTrackerBundle;
use PHPUnit\Framework\TestCase;
use BugTrackerBundle\Form\Handler\UserHandler;
use BugTrackerBundle\Entity\User;
use Symfony\Component\Config\Definition\Exception\Exception;

class UserHandlerTest extends TestCase
{
    protected $handler;
    protected $emMock;
    protected $formMock;
    protected $userMock;
    protected $userRepoMock;

    public function setUpMocks()
    {
        $this->emMock = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $encoderMock = $this->getMockBuilder('Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formMock = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->user = new User();

        $this->userRepoMock = $this->getMockBuilder('BugTrackerBundle\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emMock->expects($this->once())->method('getRepository')->with('BugTrackerBundle:User')->willReturn($this->userRepoMock);
        $this->handler = new UserHandler($this->emMock, $encoderMock, $requestMock);
    }

    public function testHandleEditRegisterCases()
    {
        // new user case
        $this->setUpMocks();
        $this->user->setPassword('12345678')->setCpassword('12345678');
        $this->formMock->method('isValid')->willReturn(true);
        $this->formMock->method('isSubmitted')->willReturn(true);
        $this->formMock->method('getData')->willReturn($this->user);
        $this->userRepoMock->method('userExists')->willReturn(false);
        $this->assertTrue($this->handler->handleRegisterForm($this->formMock));

        // existing user case
        $this->setUpMocks();
        $this->user->setPassword('12345678')->setCpassword('12345678');
        $this->formMock->expects($this->once())->method('isValid')->willReturn(true);
        $this->formMock->expects($this->once())->method('isSubmitted')->willReturn(true);
        $this->formMock->expects($this->once())->method('getData')->willReturn($this->user);
        $this->userRepoMock->expects($this->once())->method('userExists')->willReturn(true);
        $this->assertFalse($this->handler->handleRegisterForm($this->formMock));

        // invalid submitted data case
        $this->setUpMocks();
        $this->formMock->expects($this->once())->method('getData')->willReturn($this->user);
        $this->assertFalse($this->handler->handleRegisterForm($this->formMock));

        // no user bound to form case
        $this->setUpMocks();
        $message = null;
        try {
            $this->handler->handleRegisterForm($this->formMock);
        } catch(\Exception $e) {
            $message = $e->getMessage();
        }
        $this->assertContains('From has no user entity set', $message);
    }
}
