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
