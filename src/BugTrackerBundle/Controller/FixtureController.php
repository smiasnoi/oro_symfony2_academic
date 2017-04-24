<?php

namespace BugTrackerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use BugTrackerBundle\Entity;

class FixtureController extends Controller
{
    /**
     * Fixture route to populate bug tracker data
     * (Doctrine entities relations checks)
     *
     * @Route("/fixture", name="bug_tracker_fixture")
     * @Method({"GET"})
     */
    public function generateDataAction()
    {
        $issueTypes = array_keys(Entity\Issue::getTypes());
        $issueStatuses = array_keys(Entity\Issue::getStatuses());
        $issuePriorities = array_keys(Entity\Issue::getPriorities());
        $issueResolutions = array_keys(Entity\Issue::getResolutions());
        $userRoles = [Entity\User::ADMIN_ROLE, Entity\User::MANAGER_ROLE, Entity\User::OPERATOR_ROLE];

        $em = $this->getDoctrine()->getManager();
        $encoder = $this->container->get('security.password_encoder');

        $parentIssue = null;
        $plainPassword = 'qwerty123';

        $project = new Entity\Project();
        $project->setSummary('Project summary. Content: ' . uniqid())
            ->setLabel('Label-' . uniqid())
            ->setCode('PACD-' . uniqid());
        $em->persist($project);
        $em->flush();

        $users = [];
        for ($j = 0; $j < 15; $j++) {
            $user = new Entity\User();
            $encoded = $encoder->encodePassword($user, $plainPassword);
            $userRole = $userRoles[rand(0, count($userRoles) - 1)];
            $user->setEmail(uniqid() . '@oroinc.com')
                ->setUsername(uniqid() . '_user')
                ->setFullname(uniqid() . 'Some Doe')
                ->setPassword($encoded)
                ->setRoles([$userRole]);
            $em->persist($user);
            $em->flush();

            $users[] = $user;

            // adding issues
            $issuesAmmount = rand(1, 3);
            for ($k = 0; $k < $issuesAmmount; $k++) {
                $issue = new Entity\Issue();
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
                $em->persist($issue);
                $em->flush();

                // adding 'new issue' activity
                $activity = new Entity\Activity();
                $snappedData = ['issue_code' => $issue->getCode()];
                $activity->setProject($issue->getProject())
                    ->setUser($user)
                    ->setEntityId($issue->getId())
                    ->setEntity('Issue')
                    ->setSnappedData($snappedData)
                    ->setType(Entity\Activity::NEW_ISSUE_TYPE)
                    ->setCreatedAt(new \DateTime());
                $em->persist($activity);
                $em->flush();

                if ($issue->getStatus() != 'new') {
                    $snappedData = [
                        'issue_code' => $issue->getCode(),
                        'old_status' => 'new',
                        'status' => $issue->getStatus()
                    ];
                    $activity = new Entity\Activity();
                    $activity->setIssue($issue)
                        ->setProject($issue->getProject())
                        ->setUser($user)
                        ->setEntityId($issue->getId())
                        ->setEntity('Issue')
                        ->setSnappedData($snappedData)
                        ->setType(Entity\Activity::ISSUE_STATUS_CHANGE_TYPE)
                        ->setCreatedAt(new \DateTime());
                    $em->persist($activity);
                    $em->flush();
                }

                //adding comments
                $commentsAmmount = rand(1, 3);
                for ($k = 0; $k < $commentsAmmount; $k++) {
                    $commentator = $users[rand(0, count($users) - 1)];
                    $comment = new Entity\Comment();
                    $comment->setBody('Unique comment for user ' . $commentator->getFullname())
                        ->setAuthor($commentator)
                        ->setIssue($issue)
                        ->setCreatedAt(new \DateTime());
                    $em->persist($comment);
                    $em->flush();

                    // add 'post comment' issue activity
                    $activity = new Entity\Activity();
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
                        ->setType(Entity\Activity::COMMENT_POST_TYPE)
                        ->setCreatedAt(new \DateTime());
                    $em->persist($activity);
                    $em->flush();
                }
            }
            $em->flush();
        }

        foreach ($users as $user) {
            $project->addMember($user);
        }
        $em->flush();

        echo "Test data has been generated";
        exit;
    }
}
