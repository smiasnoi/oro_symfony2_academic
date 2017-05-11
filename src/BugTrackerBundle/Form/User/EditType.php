<?php
namespace BugTrackerBundle\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
            ->add('password', PasswordType::class)
            ->add('cpassword', PasswordType::class, ['label' => 'Repeat password']);

        if (in_array('user_edit', $options['validation_groups'])) {
            $user = $options['data'] ?: new UserEntity();
            $role = current($user->getRoles());

            $builder->add('roles', ChoiceType::class, [
                'choices' => array_flip(UserEntity::availableRoles()),
                'data' => $role,
                'choices_as_values' => true
            ]);
        }
    }
}
