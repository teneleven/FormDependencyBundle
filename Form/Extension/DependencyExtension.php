<?php

namespace Teneleven\Bundle\FormDependencyBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Teneleven\Bundle\FormDependencyBundle\Form\EventListener\DependencyListener;

/**
 * Adds 'depends_on' option for form fields who depend on others. Most of the
 * work is done in the DependencyListener event subscriber.
 */
class DependencyExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($builder->getCompound()) {
            $builder->addEventSubscriber(new DependencyListener());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['dependencies'] = $options['dependencies'];
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'dependencies' => [],
            'depends_on' => null,
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
