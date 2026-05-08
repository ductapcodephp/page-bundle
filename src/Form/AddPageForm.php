<?php

declare(strict_types=1);

namespace AmzsCMS\PageBundle\Form;

use AmzsCMS\PageBundle\Entity\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddPageForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Page $page */
        $page = $options['data'];
        $builder->add('name', TextType::class);
        $builder->add('post', AddPostType::class, $page instanceof Page ? [
            'data' => $page->getPost()
        ]: null);
        $builder->add('css', TextareaType::class, ['required' => false]);
        $builder->add('customCss', TextareaType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }
}
