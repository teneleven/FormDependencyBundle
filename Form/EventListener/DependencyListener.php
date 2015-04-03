<?php

namespace Teneleven\Bundle\FormDependencyBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Teneleven\Bundle\FormDependencyBundle\Form\Dependency;

/**
 * Listens to FormEvents to add validation constraints to dependencies.
 */
class DependencyListener implements EventSubscriberInterface
{
    /**
     * Add new validation constraints.
     *
     * @param FormEvent $event
     */
    public function handleDependencies(FormEvent $event)
    {
        $form = $event->getForm();
        $data = (array) $event->getData();

        foreach ($form as $field) { /** @var FormInterface $field */
            if ($field->getConfig()->hasOption('depends_on')) {
                $this->processDependency($field, $data);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'handleDependencies',
            FormEvents::PRE_SUBMIT => 'handleDependencies',
        ];
    }

    /**
     * Process a dependency and add/remove validation NotBlank constraints.
     */
    protected function processDependency(Form $widget, $data)
    {
        $dependency = $widget->getConfig()->getOption('depends_on'); /** @var Dependency $dependency */
        if ($dependency->isRequired() && is_array($data) && array_key_exists($dependency->getField(), $data) && $dependency->matches($data[$dependency->getField()])) {
            $this->addConstraint($widget);
        } else {
            $this->removeConstraint($widget);
        }
    }

    /**
     * Add required constraint to a form child.
     *
     * @param FormInterface $widget
     *
     * @return FormInterface
     */
    protected function addConstraint(FormInterface $widget)
    {
        $config = $widget->getConfig();
        $options = $config->getOptions();
        $options['required'] = true;

        // don't duplicate constraints
        if ($this->isWidgetRequired($widget)) {
            return;
        }

        $options['constraints'] = array_merge((array) $options['constraints'], [new NotBlank()]);

        return $this->changeWidgetOptions($widget, $options);
    }

    /**
     * Remove required constraint to a form child.
     *
     * @param FormInterface $widget
     *
     * @return FormInterface
     */
    protected function removeConstraint(FormInterface $widget)
    {
        $config = $widget->getConfig();
        $options = $config->getOptions();
        $options['required'] = false;

        // don't duplicate constraints
        foreach ($options['constraints'] as $key => $existingConstraint) {
            if ($existingConstraint instanceof NotBlank) {
                unset($options['constraints'][$key]);
                break;
            }
        }

        return $this->changeWidgetOptions($widget, $options);
    }

    /**
     * Override widget options as specified by $options param. Overwrites the
     * widget in the builder.
     *
     * @param FormInterface $widget
     * @param array         $options
     *
     * @return FormInterface
     */
    protected function changeWidgetOptions(FormInterface $widget, array $options)
    {
        $form = $widget->getParent();
        $config = $widget->getConfig();
        $options = array_merge($config->getOptions(), $options);
        $children = $widget->all();

        $form->add(
            $widget->getName(),
            $config->getType()->getName(),
            $options
        );

        $widget = $form->get($widget->getName()); /** @var FormInterface $child */
        $required = $this->isWidgetRequired($widget);

        foreach ($children as $child) {
            $widget->add($child);

            $child = $widget->get($child->getName());
            $type = $child->getConfig()->getType()->getName();

            if ($required && !in_array($type, ['radio', 'select', 'checkbox'])) {
                $this->addConstraint($child);
            } else {
                $this->removeConstraint($child);
            }
        }

        return $widget;
    }

    /**
     * @param FormInterface $widget
     *
     * @return boolean
     */
    public function isWidgetRequired(FormInterface $widget)
    {
        foreach ($widget->getConfig()->getOptions()['constraints'] as $existingConstraint) {
            if ($existingConstraint instanceof NotBlank) {
                return true;
            }
        }

        return false;
    }
}
