<?php

namespace BugTrackerBundle\Mailer;

use BugTrackerBundle\Entity\Activity as ActivityEntity;

class Activity implements ActivityInterface
{
    protected $mailer;
    protected $twig;

    public function __construct(\Swift_Mailer $mailer, \Twig_Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function notifyCollaborators(ActivityEntity $activity)
    {
        if (!$this->isActivityValid($activity)) {
            return;
        }

        $snappedData = $activity->getSnappedData();
        $subject = !empty($snappedData['issue_code']) ? $snappedData['issue_code'] : 'Bugtracker';
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setBody(
                $this->twig->render(
                    "BugTrackerBundle:activity:email.html.twig",
                    ['activity' => $activity]
                ),
                'text/html'
            );
        $collaborators = $activity->getIssue()->getCollaborators();
        foreach ($collaborators as $user) {
            if (!$message->getTo()) {
                $message->setTo($user->getEmail(), $user->getFullname());
            } else {
                $message->addCc($user->getEmail(), $user->getFullname());
            }
        }

        $this->mailer->send($message);
    }

    public function isActivityValid(ActivityEntity $activity)
    {
        return $activity->getIssue() !== null;
    }
}
