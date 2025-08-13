<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\RegexValidator;

class PasswordConstraint extends Regex
{
    public $message = 'Le mot de passe doit contenir au moins 8 caractères dont 1 minuscule, 1 majuscule, 1 chiffre et 1 caractère spécial.';
    public $pattern = '@^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])(?=\S*[\W])\S*$@';

    public function __construct()
    {
        parent::__construct($this->pattern, $this->message);
    }

    public function getRequiredOptions(): array
    {
        return [];
    }

    public function validatedBy(): string
    {
        return RegexValidator::class;
    }
}