<?php

namespace Teneleven\Bundle\FormDependencyBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

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

        $this->processDependencies($form, $data);
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
     * Recursively loops through all widgets and those with 'depends_on' option
     * get consumed by self::processDependency.
     */
    protected function processDependencies($form, $data)
    {
        foreach ($form as $name => $widget) {
            // get reverse side of dependency
            if ($widget->getConfig()->getOption('depends_on')) {
                $this->processDependency($widget, $data);
            }

            if ($widget->getConfig()->getCompound()) {
                $this->processDependencies($widget, is_array($data) && isset($data[$name]) ? $data[$name] : null);
            }
        }
    }

    /**
     * Process a dependency and add/remove validation NotBlank constraints.
     */
    protected function processDependency($widget, $data)
    {
        $key = $widget->getConfig()->getOption('depends_on');
        $value = null;

        // split if array
        if (is_array($key)) {
            $value = current(array_values($key));
            $key = current(array_keys($key));
        }

        if (isset($data[$key]) && $this->dependencyMatches($data[$key], $value)) {
            $this->addConstraint($widget);
        } else {
            $this->removeConstraint($widget);
        }
    }

    /**
     * @param mixed $value
     * @param mixed $match
     *
     * @return bool
     */
    protected function dependencyMatches($value, $match = null)
    {
        if (is_array($value)) {
            return ($match === null && count($value))
                || ($match !== null && in_array($match, $value))
            ;
        }

        return ($match === null && $value !== '')
            || ($match !== null && $value == $match)
        ;
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
        foreach ($options['constraints'] as $existingConstraint) {
            if ($existingConstraint instanceof NotBlank) {
                return;
            }
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

        return $form->add(
            $widget->getName(),
            $config->getType()->getName(),
            $options
        );
    }
}
