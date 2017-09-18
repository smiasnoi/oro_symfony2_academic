<?php

namespace BugTrackerBundle\Tests\Form\Handler;

use PHPUnit\Framework\TestCase;
use BugTrackerBundle\Form\Handler\UserHandler;
use BugTrackerBundle\Entity\User as User;
use Symfony\Component\Config\Definition\Exception\Exception;

class UserHandlerTest extends TestCase
{
    protected $handler;
    protected $emMock;
    protected $formMock;
    protected $userMock;
    protected $userRepoMock;

    public function setUp()
    {
        $this->emMock = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $encoderMock = $this->getMockBuilder('Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStock = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formMock = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->user = new User();

        $this->userRepoMock = $this->getMockBuilder('BugTrackerBundle\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emMock->expects($this->any())->method('getRepository')->with('BugTrackerBundle:User')->willReturn($this->userRepoMock);
        $this->handler = new UserHandler($this->emMock, $encoderMock, $this->requestStock);
    }

    public function testUserSuccessfulRegistration()
    {
        $this->user->setPlainPassword('12345678');
        $this->requestStock->expects($this->once())->method('getCurrentRequest')->willReturn(true);
        $this->formMock->method('isValid')->willReturn(true);
        $this->formMock->method('getData')->willReturn($this->user);
        $this->assertTrue($this->handler->handleRegisterForm($this->formMock));
    }

    public function testFailedUserSubmition()
    {
        $this->requestStock->expects($this->once())->method('getCurrentRequest')->willReturn(true);
        $this->assertFalse($this->handler->handleRegisterForm($this->formMock));
    }

    /**
     * @expectedException Exception
     * @expectExceptionMessage From has no user entity set
     */
    public function testFailedUserEntitySetup()
    {
        $this->requestStock->expects($this->once())->method('getCurrentRequest')->willReturn(true);
        $this->formMock->method('isValid')->willReturn(true);
        $this->handler->handleRegisterForm($this->formMock);
    }

    /**
     * @expectedException Exception
     * @expectExceptionMessage From has no user entity set
     */
    public function testFailedUserEntitySetup2()
    {
        $this->requestStock->expects($this->once())->method('getCurrentRequest')->willReturn(true);
        $this->formMock->method('isValid')->willReturn(true);
        $this->handler->handleEditForm($this->formMock);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage No HTTP request has been initialized
     */
    public function testEmptyRequest()
    {
        $this->requestStock->expects($this->once())->method('getCurrentRequest')->willReturn(null);
        $this->formMock->method('isValid')->willReturn(true);
        $this->handler->handleRegisterForm($this->formMock);
    }
}
