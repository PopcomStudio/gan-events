<?php

namespace App\Form\Field;

use App\Validator\PasswordConstraint;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RepeatedPasswordType extends RepeatedType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $firstOptions = $options['first_options'];

        $options['first_options'] = array_merge($this->getFirstOptions(), $options['first_options']);

        if ($firstOptions['help'] !== $options['first_options']['help']) {

            $options['first_options']['help'] = $firstOptions['help'].' '.$this->getFirstOptions()['help'];
        }

        parent::buildForm($builder, $options);
    }

    private function getFirstOptions(): array
    {
        return [
            'label' => 'Mot de passe',
            'help'  => 'Minimum 8 caractères dont une minuscule, une majuscule, un chiffre et un caractère spécial.',
            'attr'  => ['autocomplete' => 'new-password'],
            'constraints' => [
                new PasswordConstraint(),
            ]
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'type' => PasswordType::class,
            'options' => [],
            'first_options' => $this->getFirstOptions(),
            'second_options' => [
                'attr' => ['autocomplete' => 'new-password'],
                'label' => 'Répéter le mot de passe'
            ],
            'first_name' => 'first',
            'second_name' => 'second',
            'error_bubbling' => false,
            'invalid_message' => 'Les mots de passe ne correspondent pas.',
        ]);

        $resolver->setAllowedTypes('options', 'array');
        $resolver->setAllowedTypes('first_options', 'array');
        $resolver->setAllowedTypes('second_options', 'array');
    }
}