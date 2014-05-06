<?php
namespace Topxia\WebBundle\Form\Common;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Topxia\WebBundle\Util\ClassroomBuilder;

abstract class AbstractClassroomType extends AbstractType
{

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $builder = new ClassroomBuilder();
        $resolver->setDefaults(array(
            'choices' => $builder->buildChoices(),
        ));
    }

    public function getParent()
    {
        return 'choice';
    }

    public function getName()
    {
        return 'classroom';
    }
}