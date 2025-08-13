<?php

namespace App\Form\Field;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class PhoneType extends TextType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'phone';
    }
}