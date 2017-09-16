<?php
namespace BugTrackerBundle\Form\Issue;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use BugTrackerBundle\Entity\User as UserEntity;
use BugTrackerBundle\Helper\Issue as IssueHelper;

class EditType extends AbstractType
{
    protected $helper;

    public function __construct(IssueHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('summary', TextType::class)
            ->add('description', TextareaType::class)
            ->add('priority', ChoiceType::class, [
                'choices' => array_flip($this->helper->getPriorities()),
                'choices_as_values' => true
            ])
        ;

        $helper = $this->helper;
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($helper) {
                    $form = $event->getForm();
                    $issue = $event->getData();

                    $assignee = null === $issue->getAssignee() ? [] : [$issue->getAssignee()];
                    $form->add('assignee', EntityType::class, [
                        'class' => UserEntity::class,
                        'choice_value' => 'id',
                        'choice_label' => 'fullname',
                        'choices' => $assignee,
                    ]);

                    $choices = $helper->getIssueTypesToChange($issue);
                    if (count($choices) > 1) {
                        $form->add('type', ChoiceType::class, [
                            'choices' => array_flip($choices),
                            'choices_as_values' => true
                        ]);
                    } else {
                        $form->add('type', HiddenType::class, ['data' => key($choices)]);
                    }
                }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();

                $data = $event->getData();
                $assignee = !empty($data['assignee']) ? $data['assignee'] : -1;

                $form->add('assignee', EntityType::class, [
                    'class' => UserEntity::class,
                    'choice_value' => 'id',
                    'choice_label' => 'fullname',
                    'query_builder' => function (EntityRepository $er) use ($assignee) {
                        return $er->createQueryBuilder('u')
                            ->where('u.id=:assignee')
                            ->setParameter('assignee', $assignee);
                    },
                ]);
            }
        );
    }
}
