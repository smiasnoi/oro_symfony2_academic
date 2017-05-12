<?php
namespace BugTrackerBundle\Form\Project;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class EditType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('label', TextType::class)
            ->add('summary', TextType::class);

        $validationGroups = isset($options['validation_groups']) ? $options['validation_groups'] : [];
        if (in_array('project_create', $validationGroups)) {
            $builder->add('code', TextType::class);
        }
    }
}
