<?php

namespace App\Form;

use App\Entity\User;
use App\Form\Field\RepeatedPasswordType;
use App\Form\Field\PhoneType;
use App\Validator\PasswordConstraint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class UserType extends AbstractType
{
    private Security $security;
    private User $user;

    public function __construct(Security $security)
    {
        $this->security = $security;
        $this->user = $security->getUser();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User $user */
        $user = $builder->getData();

        // Ajout des champs génériques (communs à tous les utilisateurs)
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email *',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => false,
            ])
            ->add('phone', PhoneType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
        ;

        // Ajout des champs mots de passe lors de l'édition seulement
        if ( $user->getId() ) {

            $builder
                ->add('plainPassword', RepeatedPasswordType::class, [
                    'first_options' => [
                        'help'  => 'Laisser vide pour ne pas modifier.',
                    ],
                    'required' => false,
                ]);
        }

        // Ajout des champs spécifiques (uniquement pour l'édition des "autres" utilisateurs)
        if ( $user !== $this->user ) {

            $builder
                ->add('admin', CheckboxType::class, [
                    'label' => 'Administrateur',
                    'required' => false,
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
