<?php

namespace App\Form;

use App\Entity\Photo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;
use Vich\UploaderBundle\Form\Type\VichFileType;

class PhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'multiple' => true,
                'required' => false,
                'mapped' => true,
                'constraints' => [
                    new All([
                        'constraints' =>[
                            new Count(['min' => 4]),
                            new File([
                                'maxSize' => '125M',
                                'mimeTypes' => ['image/jpeg', 'image/png'],
                                'mimeTypesMessage' => '',
                            ]),
                        ]
                    ]),
                ]
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Photo::class,
            'allow_extra_fields' => false,
        ]);
    }
}
