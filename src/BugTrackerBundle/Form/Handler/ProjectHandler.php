<?php

namespace BugTrackerBundle\Form\Handler;

use Doctrine\ORM\EntityManager;
use BugTrackerBundle\Entity\User;
use BugTrackerBundle\Entity\Project;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ProjectHandler
{
    protected $em;
    protected $request;

    public function __construct(EntityManager $entityManager, RequestStack $requestStack){
        $this->em = $entityManager;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @param FormInterface $form
     * @return bool
     */
    public function handleCreateForm(FormInterface $form)
    {
        $project = $this->getProject($form);

        $form->handleRequest($this->request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($project);
            $this->em->flush();

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param FormInterface $form
     * @return bool
     */
    public function handleEditForm(FormInterface $form)
    {
        return $this->handleCreateForm($form);
    }

    /**
     * @param FormInterface $form
     * @return User
     * @throws \Exception
     */
    protected function getProject(FormInterface $form)
    {
        $project = $form->getData();
        if (!is_object($project) && !($project instanceof Project)) {
            throw new \Exception("Form has no project entity set");
        }

        return $project;
    }
}
