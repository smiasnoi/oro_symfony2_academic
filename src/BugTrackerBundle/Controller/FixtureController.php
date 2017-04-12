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
        $issueTypes = ['Bug', 'Subtask', 'Task', 'Story'];
        $issueStatuses = ['Open', 'In progress', 'Closed'];
        $issuePriorities = ['Minor', 'Major', 'Low', 'Critical'];
        $issueResolutions = ['Won\'t fix', 'Fix', 'Duplicate', 'Done', 'Incomplete'];

        $em = $this->getDoctrine()->getManager();
        $parentIssue = null;
        for ($i = 0; $i < 5; $i++) {
            $project = new Entity\Project();
            $project->setSummary('Project summary. Content: ' . uniqid())
                ->setLabel('Label-' . uniqid())
                ->setCode('PACD-' . uniqid());
            $em->persist($project);
            $em->flush();

            $users = [];
            for ($j = 0; $j < 30; $j++) {
                $user = new Entity\User();
                $user->setEmail(uniqid() . '@oroinc.com')
                    ->setUsername(uniqid() . '_user')
                    ->setFullname(uniqid() . 'Some Doe')
                    ->setPasswordHash(md5('qwerty123'))
                    ->setRoles('Admin,Operator');
                $em->persist($user);
                $em->flush();

                $users[] = $user;

                //adding comments
                $commentsAmmount = rand(1, 3);
                for ($k = 0; $k < $commentsAmmount; $k++) {
                    $comment = new Entity\Comment();
                    $comment->setBody('Unique comment for user ' . $user->getFullname())
                        ->setAuthor($user)
                        ->setCreatedAt(new \DateTime());
                    $em->persist($comment);
                }
                $em->flush();

                // adding issues
                $issuesAmmount = rand(1, 6);
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
                        ->setCreatedAt(new \DateTime())
                        ->setUpdatedAt(new \DateTime());
                    $collaborators = array_slice($users, -4, rand(1, 3));
                    foreach ($collaborators as $collaborator) {
                        $issue->addCollaborator($collaborator);
                    }

                    if ($issueType == 'Subtask' && !$parentIssue) {
                        $issueType = 'Task';
                    }
                    if ($issueType == 'Task') {
                        $parentIssue = $issue;
                    }
                    if ($issueType == 'Subtask') {
                        $issue->setParent($parentIssue);
                    }

                    $em->persist($issue);
                }
                $em->flush();
            }

            foreach ($users as $user) {
                $project->addMember($user);
            }
            $em->flush();
        }

        echo "Test data has been generated";
        exit;
    }
}
