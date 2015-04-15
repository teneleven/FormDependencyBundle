<?php

namespace Teneleven\Bundle\FormDependencyBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
     * Resolves dependencies between fields.
     *
     * @param FormEvent $event
     */
    public function handleDependencies(FormEvent $event)
    {
        $form = $event->getForm();

        if (!$form->getConfig()->getOption('compound')) {
            return;
        }

        foreach ($form as $field) { /** @var FormInterface $field */
            if ($field->getConfig()->hasOption('depends_on')) {
                $this->processDependency($field, $event->getData());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'handleDependencies',
        ];
    }

    /**
     * Process a dependency and add/remove validation NotBlank constraints.
     */
    protected function processDependency(FormInterface $widget, $data)
    {
        $dependency = $widget->getConfig()->getOption('depends_on'); /** @var Dependency $dependency */
        if ($dependency->isRequired() && $this->dependencyMatches($dependency, (array) $data)) {
            $this->addConstraint($widget);
        } else {
            $this->removeConstraint($widget);
        }
    }

    /**
     * @param Dependency $dependency
     * @param array      $data
     *
     * @return bool
     */
    protected function dependencyMatches(Dependency $dependency, array $data)
    {
        return array_key_exists($dependency->getField(), $data) && $dependency->matches($data[$dependency->getField()]);
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
        if ($this->isWidgetRequired($widget)) {
            return; // don't duplicate constraints
        }

        $options = $widget->getConfig()->getOptions();
        $options['required'] = true;
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

        foreach ($options['constraints'] as $key => $existingConstraint) {
            if ($existingConstraint instanceof NotBlank) {
                unset($options['constraints'][$key]);
                break;
            }
        }

        $widget = $this->changeWidgetOptions($widget, $options); // change current form field

        // un-require children
        foreach ($widget->all() as $field) { /** @var FormInterface $field */
            if ($dependency = $field->getConfig()->getOption('depends_on')) { /* @var Dependency $dependency */
                $dependency->setRequired(false); // ensure that next listener doesn't require this field again.
            }
            $this->removeConstraint($field);
        }
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
        foreach ($children as $child) {
            $widget->add($child);
        }

        return $widget;
    }

    /**
     * @param FormInterface $widget
     *
     * @return bool
     */
    protected function isWidgetRequired(FormInterface $widget)
    {
        foreach ($widget->getConfig()->getOptions()['constraints'] as $existingConstraint) {
            if ($existingConstraint instanceof NotBlank) {
                return true;
            }
        }

        return false;
    }
}
