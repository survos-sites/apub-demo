<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

final class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('aboutUrl', UrlType::class, [
                'label' => 'Link to a museado item',
                'constraints' => [new NotBlank(), new Url()],
                'attr' => ['placeholder' => 'https://mus.survos.com/...'],
            ])
            ->add('aboutLabel', TextType::class, [
                'label' => 'Item title (optional caption)',
                'required' => false,
            ])
            ->add('title', TextType::class, [
                'label' => 'Post title',
                'constraints' => [new NotBlank()],
            ])
            ->add('body', TextareaType::class, [
                'label' => 'Commentary',
                'constraints' => [new NotBlank()],
                'attr' => ['rows' => 6],
            ])
            ->add('publish', SubmitType::class, [
                'label' => 'Publish',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
