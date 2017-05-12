<?php

namespace BugTrackerBundle\Mailer;

use BugTrackerBundle\Entity\Activity as ActivityEntity;

interface ActivityInterface
{
    function isActivityValid(ActivityEntity $activity);
}
