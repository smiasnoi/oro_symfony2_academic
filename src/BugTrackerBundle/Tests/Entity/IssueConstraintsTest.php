<?php

namespace BugTrackerBundle\Tests\Entity;

use BugTrackerBundle\Tests\WebTestCaseAbstract as WebTestCaseAbstract;
use BugTrackerBundle\Entity\Issue as IssueEntity;

class IssueConstraintsTest extends WebTestCaseAbstract
{
    public function testTypeConstraint()
    {
        $data = $this->allEntitiesChainFixture();
        $issue = $data['issue'];

        $em = $this->doctrine->getManager();
        $allTypes = $this->issueHelper->getAllTypes();
        foreach ($allTypes as $_type => $label) {
            if ($issue->getType() != $_type) {
                $issue->setType($_type);
                $em->persist($issue);
                $em->flush();
            }
            // dummy parent set
            if ($_type == $this->issueHelper->getSubtaskType()) {
                $issue->setParent(new IssueEntity());
            }

            //walking through all allowed types
            $allowedTypes = $this->issueHelper->getAllowedTypesToChange($issue->getType(), false);
            foreach ($allowedTypes as $type) {
                $issue->setType($type);
                $errors = $this->validator->validate($issue);
                $this->assertTrue(!(bool)count($errors), (string)$errors);
            }

            //false value test
            $issue->setType(uniqid());
            $errors = $this->validator->validate($issue);
            $this->assertTrue((bool)count($errors), (string)$errors);

            $issue->setParent(null);
        }
    }

    public function testStatusConstraint()
    {
        $data = $this->allEntitiesChainFixture();
        $issue = $data['issue'];

        $em = $this->doctrine->getManager();
        $allStatuses = $this->issueHelper->getAllStatuses();
        foreach ($allStatuses as $_status => $label) {
            if ($issue->getStatus() != $_status) {
                $issue->setStatus($_status);
                $em->persist($issue);
                $em->flush();
            }

            //walking through all allowed statuses
            $allowedStatuses = $this->issueHelper->getAllowedStatusesToChange($issue->getStatus());
            foreach ($allowedStatuses as $status) {
                $issue->setStatus($status);
                $errors = $this->validator->validate($issue);
                $this->assertTrue(!(bool)count($errors), (string)$errors);
            }

            if (empty($status)) {
                continue;
            }

            //false value test
            $issue->setStatus(uniqid());
            $errors = $this->validator->validate($issue);
            $this->assertTrue((bool)count($errors), (string)$errors);
        }
    }
}
