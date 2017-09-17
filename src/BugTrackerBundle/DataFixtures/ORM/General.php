<?php
namespace BugTrackerBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use BugTrackerBundle\Entity\User as UserEntity;
use BugTrackerBundle\Entity\Issue as IssueEntity;
use BugTrackerBundle\Entity\Project as ProjectEntity;
use BugTrackerBundle\Entity\Activity as ActivityEntity;
use BugTrackerBundle\Entity\Comment as CommentEntity;

class General extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $issueHelper = $this->container->get('bugtracker.issue.helper');
        $issueTypes = array_keys($issueHelper->getAllTypes());
        $issueStatuses = array_keys($issueHelper->getAllStatuses());
        $issuePriorities = array_keys($issueHelper->getPriorities());
        $issueResolutions = array_keys($issueHelper->getResolutions());
        $userRoles = [UserEntity::ADMIN_ROLE, UserEntity::MANAGER_ROLE, UserEntity::OPERATOR_ROLE];

        $encoder = $this->container->get('security.password_encoder');
        $parentIssue = null;
        $plainPassword = 'qwerty123';

        $project = new ProjectEntity();
        $project->setSummary('Project summary. Content: ' . uniqid())
            ->setLabel('Label-' . uniqid())
            ->setCode('PACD-' . uniqid());
        $manager->persist($project);
        $manager->flush();

        $users = [];
        for ($j = 0; $j < 15; $j++) {
            $user = new UserEntity();
            $encoded = $encoder->encodePassword($user, $plainPassword);
            $userRole = $userRoles[rand(0, count($userRoles) - 1)];
            $user->setEmail(uniqid() . '@oroinc.com')
                ->setUsername(uniqid() . '_user')
                ->setFullname(uniqid() . 'Some Doe')
                ->setPassword($encoded)
                ->setRoles([$userRole]);
            $manager->persist($user);
            $manager->flush();

            $users[] = $user;

            // adding issues
            $issuesAmmount = rand(1, 3);
            for ($k = 0; $k < $issuesAmmount; $k++) {
                $issue = new IssueEntity();
                $issueType = $issueTypes[rand(0, count($issueTypes) - 1)];
                $issueStatuse = $issueStatuses[rand(0, count($issueStatuses) - 1)];
                $issuePriority = $issuePriorities[rand(0, count($issuePriorities) - 1)];
                $issueResolution = $issueResolutions[rand(0, count($issueResolutions) - 1)];

                $issue->setCode('IACD-' . uniqid())
                    ->setSummary('Issue summary. Content: ' . uniqid())
                    ->setDescription('Issue description. Content: ' . uniqid())
                    ->setType($issueType)
                    ->setStatus($issueStatuse)
                    ->setPriority($issuePriority)
                    ->setResolution($issueResolution)
                    ->setAssignee($user)
                    ->setReporter($user)
                    ->setProject($project)
                    ->setCreatedAt(new \DateTime())
                    ->setUpdatedAt(new \DateTime())
                    ->addCollaborator($user);

                if ($issueType == 'subtask' && !$parentIssue) {
                    $issueType = 'task';
                }
                if ($issueType == 'task') {
                    $parentIssue = $issue;
                }
                if ($issueType == 'subtask') {
                    $issue->setParent($parentIssue);
                }
                $manager->persist($issue);
                $manager->flush();

                // adding 'new issue' activity
                $activity = new ActivityEntity();
                $snappedData = ['issue_code' => $issue->getCode()];
                $activity->setProject($issue->getProject())
                    ->setUser($user)
                    ->setEntityId($issue->getId())
                    ->setEntity('Issue')
                    ->setSnappedData($snappedData)
                    ->setType(ActivityEntity::NEW_ISSUE_TYPE)
                    ->setCreatedAt(new \DateTime());
                $manager->persist($activity);
                $manager->flush();

                if ($issue->getStatus() != 'new') {
                    $snappedData = [
                        'issue_code' => $issue->getCode(),
                        'old_status' => 'new',
                        'status' => $issue->getStatus()
                    ];
                    $activity = new ActivityEntity();
                    $activity->setIssue($issue)
                        ->setProject($issue->getProject())
                        ->setUser($user)
                        ->setEntityId($issue->getId())
                        ->setEntity('Issue')
                        ->setSnappedData($snappedData)
                        ->setType(ActivityEntity::ISSUE_STATUS_CHANGE_TYPE)
                        ->setCreatedAt(new \DateTime());
                    $manager->persist($activity);
                    $manager->flush();
                }

                //adding comments
                $commentsAmmount = rand(1, 3);
                for ($k = 0; $k < $commentsAmmount; $k++) {
                    $commentator = $users[rand(0, count($users) - 1)];
                    $comment = new CommentEntity();
                    $comment->setBody('Unique comment for user ' . $commentator->getFullname())
                        ->setAuthor($commentator)
                        ->setIssue($issue)
                        ->setCreatedAt(new \DateTime());
                    $manager->persist($comment);
                    $manager->flush();

                    // add 'post comment' issue activity
                    $activity = new ActivityEntity();
                    $snappedData = [
                        'issue_code' => $issue->getCode(),
                        'comment_body' => $comment->getBody()
                    ];

                    $activity->setIssue($issue)
                        ->setUser($commentator)
                        ->setProject($issue->getProject())
                        ->setEntityId($comment->getId())
                        ->setEntity('Comment')
                        ->setSnappedData($snappedData)
                        ->setType(ActivityEntity::COMMENT_POST_TYPE)
                        ->setCreatedAt(new \DateTime());
                    $manager->persist($activity);
                    $manager->flush();
                }
            }
            $manager->flush();
        }

        foreach ($users as $user) {
            $project->addMember($user);
        }
        $manager->flush();
    }
}