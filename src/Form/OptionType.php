<?php

namespace App\Form;

use App\Entity\Option;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = $builder
            ->create('data', FormType::class, [
                'label' => false,
                'row_attr' => [
                    'class' => 'mb-0',
                ],
                'required' => true,
            ])
        ;

        $this->addData($data);
        $builder->add($data);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Option::class,
        ]);
    }

    private function addData(FormBuilderInterface $builder): void
    {
        $builder
            ->add('value', TextareaType::class, [
                'label' => 'Contenu à afficher',
                'attr' => [
                    'data-init' => 'wysiwyg',
                ],
                'row_attr' => [
                    'class' => 'mb-0',
                ],
                'required' => false,
            ])
        ;
    }
}
