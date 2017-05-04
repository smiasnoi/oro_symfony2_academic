<?php
namespace BugTrackerBundle\Form\Issue;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use BugTrackerBundle\Entity\Issue as IssueEntity;
use BugTrackerBundle\Entity\User as UserEntity;

class EditType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('summary', TextType::class)
            ->add('description', TextareaType::class)
            ->add('type', ChoiceType::class, [
                'choices' => array_flip(IssueEntity::getTypes()),
                'choices_as_values' => true
            ])->add('priority', ChoiceType::class, [
                'choices' => array_flip(IssueEntity::getPriorities()),
                'choices_as_values' => true
            ])
        ;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
                function (FormEvent $event) {
                    $form = $event->getForm();

                    $data = $event->getData();
                    $assignee = null === $data->getAssignee() ? [] : [$data->getAssignee()];

                    $form->add('assignee', EntityType::class, array(
                        'class' => UserEntity::class,
                        'choice_value' => 'id',
                        'choice_label' => 'fullname',
                        'choices' => $assignee,
                    ));
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
