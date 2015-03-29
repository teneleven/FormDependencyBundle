<?php

namespace Teneleven\Bundle\FormDependencyBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
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
        // todo here we might want to do some pre-processing.
        $view->vars['depends_on'] = $options['depends_on'];
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'depends_on' => null,
        ]);

        $resolver->setNormalizers([
            'depends_on' => function($options, $value) {
                if (is_scalar($value)) {
                    return [$value => null];
                }

                return $value;
            }
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
