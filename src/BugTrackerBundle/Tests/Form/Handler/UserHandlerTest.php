<?php

namespace BugTrackerBundle\Tests\Form\Handler;

use PHPUnit\Framework\TestCase;
use BugTrackerBundle\Form\Handler\UserHandler;
use BugTrackerBundle\Entity\User as UserEntity;
use Symfony\Component\Config\Definition\Exception\Exception;

class UserHandlerTest extends TestCase
{
    protected $handler;
    protected $emMock;
    protected $requestStack;
    protected $formMock;
    protected $user;

    public function setUp()
    {
        $this->emMock = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $encoderMock = $this->getMockBuilder('Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formMock = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->user = new UserEntity();

        $this->handler = new UserHandler($this->emMock, $encoderMock, $this->requestStack);
    }

    public function testUserSuccessfulRegistration()
    {
        $this->user->setPlainPassword('12345678');
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(true);
        $this->formMock->method('isValid')->willReturn(true);
        $this->formMock->method('getData')->willReturn($this->user);
        $this->assertTrue($this->handler->handleRegisterForm($this->formMock));
    }

    public function testFailedUserSubmition()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(true);
        $this->assertFalse($this->handler->handleRegisterForm($this->formMock));
    }

    /**
     * @expectedException Exception
     * @expectExceptionMessage From has no user entity set
     */
    public function testFailedUserEntitySetup()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(true);
        $this->formMock->method('isValid')->willReturn(true);
        $this->handler->handleRegisterForm($this->formMock);
    }

    /**
     * @expectedException Exception
     * @expectExceptionMessage From has no user entity set
     */
    public function testFailedUserEntitySetup2()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(true);
        $this->formMock->method('isValid')->willReturn(true);
        $this->handler->handleEditForm($this->formMock);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage No HTTP request has been initialized
     */
    public function testEmptyRequest()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(null);
        $this->formMock->method('isValid')->willReturn(true);
        $this->handler->handleRegisterForm($this->formMock);
    }
}
