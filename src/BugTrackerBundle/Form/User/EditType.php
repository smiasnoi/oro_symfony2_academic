<?php
namespace BugTrackerBundle\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use BugTrackerBundle\Entity\User as UserEntity;

class EditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class)
            ->add('username', TextType::class)
            ->add('fullname', TextType::class)
            ->add('plain_password', RepeatedType::class, array(
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options' => array('attr' => array('class' => 'password-field')),
                'required' => true,
                'first_options'  => array('label' => 'Password'),
                'second_options' => array('label' => 'Repeat Password'),
            ));

        $validationGroups = isset($options['validation_groups']) ? $options['validation_groups'] : [];
        if (in_array('user_edit', $validationGroups)) {
            $user = $options['data'] ?: new UserEntity();
            $role = current($user->getRoles());

            $builder->add('roles', ChoiceType::class, [
                'choices' => array_flip(UserEntity::availableRoles()),
                'data' => $role,
                'choices_as_values' => true
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'BugTrackerBundle\Entity\User',
        ));
    }
}
