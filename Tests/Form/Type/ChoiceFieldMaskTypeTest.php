<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Tests\Form\Type;

use Sonata\AdminBundle\Form\Type\ChoiceFieldMaskType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceFieldMaskTypeTest extends TypeTestCase
{
    public function testGetDefaultOptions()
    {
        $type = new ChoiceFieldMaskType();

        $optionResolver = new OptionsResolver();

        $type->setDefaultOptions($optionResolver);
        $options = $optionResolver->resolve(
            array(
                'map' => array(
                    'foo' => array('field1', 'field2'),
                    'bar' => array('field3'),
            ),
        ));

        $this->assertSame(array('foo' => array('field1', 'field2'), 'bar' => array('field3')), $options['map']);
    }

    public function testGetName()
    {
        $type = new ChoiceFieldMaskType();
        $this->assertEquals('sonata_type_choice_field_mask', $type->getName());
    }

    public function testGetParent()
    {
        $type = new ChoiceFieldMaskType();
        $this->assertEquals('choice', $type->getParent());
    }
}
