<?php

namespace App\Form;

use App\Entity\Anime;
use App\Entity\Genre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnimeType extends AbstractType
{
    private EntityManagerInterface $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Entrez le nom de l\'anime']
            ])
            ->add('descrition', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['placeholder' => 'Entrez la description de l\'anime', 'rows' => 5]
            ])
            ->add('statut', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'label' => 'Status',
                'required' => true,
                'choices' => [
                    'Open' => 'open',
                    'In Progress' => 'in-progress',
                    'Resolved' => 'resolved'
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank(['message' => 'Le statut est obligatoire.']),
                    new \Symfony\Component\Validator\Constraints\Choice([
                        'choices' => ['open', 'in-progress', 'resolved'],
                        'message' => 'Le statut doit être l\'un des suivants : open, in-progress, resolved'
                    ])
                ]
            ])
            ->add('trailerurl', UrlType::class, [
                'label' => 'URL de la bande-annonce',
                'attr' => ['placeholder' => 'https://example.com/trailer'],
                'required' => false
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image de l\'anime',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG, GIF)',
                    ])
                ],
            ])
            // Utiliser 'genre' au lieu de 'genre_id' pour le formulaire
            ->add('genre', EntityType::class, [
                'class' => Genre::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisissez un genre',
                'label' => 'Genre',
                'required' => true,
                'mapped' => false // On gérera manuellement le mapping
            ])
            ->add('age', ChoiceType::class, [
                'label' => 'Classification d\'âge',
                'choices' => [
                    'Tous publics' => 'Tous publics',
                    '7+' => '7+',
                    '12+' => '12+',
                    '16+' => '16+',
                    '18+' => '18+'
                ],
                'placeholder' => 'Choisissez une classification d\'âge'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Anime::class,
        ]);
    }
}
