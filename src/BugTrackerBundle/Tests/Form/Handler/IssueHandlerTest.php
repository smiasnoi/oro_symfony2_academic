<?php

namespace BugTrackerBundle\Tests\Form\Handler;

use BugTrackerBundle\Entity\Project;
use PHPUnit\Framework\TestCase;
use BugTrackerBundle\Form\Handler\IssueHandler;
use BugTrackerBundle\Entity\Issue as IssueEntity;
use BugTrackerBundle\Entity\Project as ProjectEntity;
use BugTrackerBundle\Entity\Activity as ActivityEntity;
use BugTrackerBundle\Entity\Comment as CommentEntity;
use Symfony\Component\Config\Definition\Exception\Exception;

class IssueHandlerTest extends TestCase
{
    protected $handler;
    protected $emMock;
    protected $requestStack;
    protected $formMock;
    protected $issue;

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
        $this->issue = new IssueEntity();
        $this->project = new ProjectEntity();
        $this->comment = new CommentEntity();

        $issueHelperMock = $this->getMockBuilder('BugTrackerBundle\Helper\Issue')
            ->disableOriginalConstructor()
            ->getMock();

        $activityMailerMock = $this->getMockBuilder('BugTrackerBundle\Mailer\Activity')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emMock->expects($this->any())->method('getRepository')->with('BugTrackerBundle:Issue')->willReturn($this->issueRepoMock);
        $this->handler = new IssueHandler($this->emMock, $this->requestStack, $issueHelperMock, $activityMailerMock);
    }

    /**
     * @expectedException Exception
     * @expectExceptionMessage Form has no issue entity set
     */
    public function testFailedIssueEntitySetup()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(true);
        $this->formMock->method('isValid')->willReturn(true);
        $this->handler->handleRegisterForm($this->formMock);
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
