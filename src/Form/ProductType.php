<?php

namespace App\Form;

use App\Entity\Product;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit:',
                'required' => true
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence:',
                'required' => true,
            ])
            ->add('product', TextType::class, [
                'label' => 'Produit:',
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégories associées:',
                'required' => true,
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'Digitale' => Product::CATEGORY_DIGITAL,
                    'Print' => Product::CATEGORY_PRINT,
                    'Rédaction' => Product::CATEGORY_EDITORIAL,
                    'Développement' => Product::CATEGORY_DEV,
                    'Vidéo' => Product::CATEGORY_VIDEO,
                    'Référencement' => Product::CATEGORY_REFERENCING,
                ],
            ])
            ->add('credit', TextType::class, [
                'label' => 'Prix:',
                'required' => true,
            ])
            ->add('euro', TextType::class, [
                'label' => null,
                'required' => true,
            ])
            ->add('hour', TextType::class, [
                'label' => null,
                'required' => true,
            ])
            ->add('photoFile', VichImageType::class, [
                'required' => false,
                'label' => 'Importer des images:',
                'attr' => [
                    'accept' => '.png, .jpg, .jpeg',
                ],
            ])
            ->add('processFile', VichImageType::class, [
                'required' => false,
                'label' => 'Importer la procédure:',
                'attr' => [
                    'accept' => '.png, .jpg, .jpeg, .pdf',
                ],
            ])
            ->add('paperFile', VichImageType::class, [
                'required' => false,
                'label' => 'Importer le livre blanc:',
                'attr' => [
                    'accept' => '.png, .jpg, .jpeg, .pdf',
                ],
            ])
            ->add('description', CKEditorType::class, [
                'label' => 'Description:'
            ])
            ->add('associatedGant', ChoiceType::class, [
                'label' => 'Gant associé',
                'required' => true,
                'choices' => [
                    'Process bannière classique' => Product::GANT_CLASSIC,
                    'Process bannière intermédiaire' => Product::GANT_INTERMEDIATE,
                    'Process bannière compliqué' => Product::GANT_COMPLICATED,
                ],
                'placeholder' => 'Choisissez',
            ])
            ->add('workingDay', TextType::class, [
                'label' => 'En jour ouvré:',
                'required' => true,
            ])
            ->add('tagRemarketing', CKEditorType::class, [
                'label' => 'Intégrer votre code:',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
