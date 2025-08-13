<?php

namespace App\Form\Extension;

use App\Form\Field\RepeatedPasswordType;
use Symfony\Component\Form\Extension\Validator\Type\RepeatedTypeValidatorExtension;

class RepeatedPasswordTypeValidatorExtension extends RepeatedTypeValidatorExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [RepeatedPasswordType::class];
    }
}