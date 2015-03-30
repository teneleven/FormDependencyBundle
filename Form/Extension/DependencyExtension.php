<?php

namespace Teneleven\Bundle\FormDependencyBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Teneleven\Bundle\FormDependencyBundle\Form\Dependency;
use Teneleven\Bundle\FormDependencyBundle\Form\EventListener\DependencyListener;

/**
 * Adds 'depends_on' option for form fields which depend on others. Most of the
 * work is done in the DependencyListener event subscriber.
 */
final class DependencyExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new DependencyListener());
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['depends_on'] = isset($options['depends_on']) ? $options['depends_on'] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        foreach ($form as $key => $field) { /** @var FormInterface $field */
            if ($field->getConfig()->hasOption('depends_on')) {
                $dependency = $field->getConfig()->getOption('depends_on'); /** @var Dependency $dependency */
                $dependentView = $view->children[$dependency->getField()];

                $dependentName = $dependentView->vars['full_name'];
                if (!empty($dependentView->vars['multiple']) && $dependentView->vars['expanded']) {
                    $dependentName .= '[]';
                }

                $view->children[$key]->vars['depends_on'] = [
                    'field' => $dependentName, 'value' => $dependency->getValue(), 'match_type' => $dependency->getMatchType()
                ];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional([
            'depends_on',
        ]);

        $resolver->setAllowedTypes([
            'depends_on' => Dependency::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }
}
