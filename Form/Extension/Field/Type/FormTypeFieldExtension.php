<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Form\Extension\Field\Type;

use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Exception\NoValueException;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FormTypeFieldExtension.
 *
 * @author  Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class FormTypeFieldExtension extends AbstractTypeExtension
{
    protected $defaultClasses = array();
    protected $options;

    /**
     * @param array $defaultClasses
     */
    public function __construct(array $defaultClasses, array $options)
    {
        $this->defaultClasses = $defaultClasses;
        $this->options = $options;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sonataAdmin = array(
            'name'              => null,
            'admin'             => null,
            'value'             => null,
            'edit'              => 'standard',
            'inline'            => 'natural',
            'field_description' => null,
            'block_name'        => false,
            'options'           => $this->options,
        );

        $builder->setAttribute('sonata_admin_enabled', false);
        $builder->setAttribute('sonata_help', false);

        if ($options['sonata_field_description'] instanceof FieldDescriptionInterface) {
            $fieldDescription = $options['sonata_field_description'];

            $sonataAdmin['admin']             = $fieldDescription->getAdmin();
            $sonataAdmin['field_description'] = $fieldDescription;
            $sonataAdmin['name']              = $fieldDescription->getName();
            $sonataAdmin['edit']              = $fieldDescription->getOption('edit', 'standard');
            $sonataAdmin['inline']            = $fieldDescription->getOption('inline', 'natural');
            $sonataAdmin['block_name']        = $fieldDescription->getOption('block_name', false);
            $sonataAdmin['class']             = $this->getClass($builder);

            $builder->setAttribute('sonata_admin_enabled', true);
        }

        $builder->setAttribute('sonata_admin', $sonataAdmin);
    }

    /**
     * @param FormBuilderInterface $formBuilder
     *
     * @return string
     */
    protected function getClass(FormBuilderInterface $formBuilder)
    {
        foreach ($this->getTypes($formBuilder) as $type) {
            if (isset($this->defaultClasses[$type->getName()])) {
                return $this->defaultClasses[$type->getName()];
            }
        }

        return '';
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $formBuilder
     *
     * @return array
     */
    protected function getTypes(FormBuilderInterface $formBuilder)
    {
        $types = array();

        for ($type = $formBuilder->getType(); null !== $type; $type = $type->getParent()) {
            array_unshift($types, $type->getInnerType());
        }

        return $types;
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $sonataAdmin = $form->getConfig()->getAttribute('sonata_admin');

        /*
         * We have a child, so we need to upgrade block prefix
         */
        if ($view->parent && $view->parent->vars['sonata_admin_enabled'] && !$sonataAdmin['admin']) {
            $blockPrefixes    = $view->vars['block_prefixes'];

            $baseName = str_replace('.', '_', $view->parent->vars['sonata_admin_code']);

            $baseType = $blockPrefixes[count($blockPrefixes) - 2];
            $blockSuffix = preg_replace('#^_([a-z0-9]{14})_(.++)$#', "\$2", array_pop($blockPrefixes));

            $blockPrefixes[] = sprintf('%s_%s', $baseName, $baseType);
            $blockPrefixes[] = sprintf('%s_%s_%s_%s', $baseName, $baseType, $view->parent->vars['name'], $view->vars['name']);
            $blockPrefixes[] = sprintf('%s_%s_%s_%s', $baseName, $baseType, $view->parent->vars['name'], $blockSuffix);

            $view->vars['block_prefixes']       = $blockPrefixes;
            $view->vars['sonata_admin_enabled'] = true;
            $view->vars['sonata_admin']         = array(
                'admin'             => false,
                'field_description' => false,
                'name'              => false,
                'edit'              => 'standard',
                'inline'            => 'natural',
                'block_name'        => false,
                'class'             => false,
                'options'           => $this->options,
            );
            $view->vars['sonata_admin_code']    = $view->parent->vars['sonata_admin_code'];

            return;
        }

        $sonataAdminHelp = isset($options['sonata_help']) ? $options['sonata_help'] : null;

        // avoid to add extra information not required by non admin field
        if ($sonataAdmin && $form->getConfig()->getAttribute('sonata_admin_enabled', true)) {
            $sonataAdmin['value'] = $form->getData();

            // add a new block types, so the Admin Form element can be tweaked based on the admin code
            $blockPrefixes    = $view->vars['block_prefixes'];
            $baseName = str_replace('.', '_', $sonataAdmin['admin']->getCode());
            $baseType = $blockPrefixes[count($blockPrefixes) - 2];
            $blockSuffix = preg_replace('#^_([a-z0-9]{14})_(.++)$#', "\$2", array_pop($blockPrefixes));

            $blockPrefixes[] = sprintf('%s_%s', $baseName, $baseType);
            $blockPrefixes[] = sprintf('%s_%s_%s', $baseName, $sonataAdmin['name'], $baseType);
            $blockPrefixes[] = sprintf('%s_%s_%s_%s', $baseName, $sonataAdmin['name'], $baseType, $blockSuffix);

            if (isset($sonataAdmin['block_name']) && $sonataAdmin['block_name'] !== false) {
                $blockPrefixes[] = $sonataAdmin['block_name'];
            }

            $view->vars['block_prefixes']       = $blockPrefixes;
            $view->vars['sonata_admin_enabled'] = true;
            $view->vars['sonata_admin']         = $sonataAdmin;
            $view->vars['sonata_admin_code']    = $sonataAdmin['admin']->getCode();

            $attr = $view->vars['attr'];

            if (!isset($attr['class']) && isset($sonataAdmin['class'])) {
                $attr['class'] = $sonataAdmin['class'];
            }

            $view->vars['attr'] = $attr;
        } else {
            $view->vars['sonata_admin_enabled'] = false;
        }

        $view->vars['sonata_help']  = $sonataAdminHelp;
        $view->vars['sonata_admin'] = $sonataAdmin;
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return 'field';
    }

    /**
     * {@inheritdoc}
     *
     * @todo Remove it when bumping requirements to SF 2.7+
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'sonata_admin'             => null,
            'sonata_field_description' => null,

            // be compatible with mopa if not installed, avoid generating an exception for invalid option
            'label_render'             => true,
            'sonata_help'              => null,
        ));
    }

    /**
     * return the value related to FieldDescription, if the associated object does no
     * exists => a temporary one is created.
     *
     * @param object                                              $object
     * @param \Sonata\AdminBundle\Admin\FieldDescriptionInterface $fieldDescription
     *
     * @return mixed
     */
    public function getValueFromFieldDescription($object, FieldDescriptionInterface $fieldDescription)
    {
        $value = null;

        if (!$object) {
            return $value;
        }

        try {
            $value = $fieldDescription->getValue($object);
        } catch (NoValueException $e) {
            if ($fieldDescription->getAssociationAdmin()) {
                $value = $fieldDescription->getAssociationAdmin()->getNewInstance();
            }
        }

        return $value;
    }
}
