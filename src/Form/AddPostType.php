<?php

declare(strict_types=1);

namespace AmzsCMS\PageBundle\Form;

use AmzsCMS\ArticleBundle\Entity\Post;
use AmzsCMS\ArticleBundle\Form\Common\PublishedChoiceType;
use AmzsCMS\ArticleBundle\Form\Common\SocialSharingType;
use AmzsCMS\PageBundle\DataType\PostStatusType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddPostType extends AbstractType
{
//    private $pictureService;
//    public function __construct(PictureService $pictureService)
//    {
//        $this->pictureService = $pictureService;
//    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Post $post */
        $post = $options['data'];
        $builder->add('title');
        $builder->add('subTitle', TextType::class, ['required' => false]);
        $builder->add('description', TextareaType::class, [
            'required' => false,
        ]);
        $builder->add('thumbnail', HiddenType::class, ['required' => false]);
        $builder->add('published', PublishedChoiceType::class, [
            'data-select2-dropdown-parent-value' => '#amz_post_add',
            'data-select2-hidden-search-value' => 'true',
            'data' => $post instanceof Post ? $post->getPublished() : PostStatusType::PUBLISH_TYPE_DRAFT,
        ]);
        $builder->add('arrTags', TextType::class, [
            'label' => 'Tags',
            'attr' => [
                'placeholder' => 'Enter tags',
                'class' => 'form-control form-control-sm form-control-solid mb-2',
                'data-controller' => 'tagify',
            ]
        ]);
        if($post instanceof Post)
            $builder->add('socialSharing', SocialSharingType::class);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $post = $event->getData();
            $form = $event->getForm();
            if ($post instanceof Post) {
                $form->add('isHot', HiddenType::class, ['required' => false, 'mapped' => false]);
                $form->add('isNew', HiddenType::class, ['required' => false, 'mapped' => false]);
                $form->add('content', TextareaType::class, [
                    'required' => false,
                ]);
            }
        });

//        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
//            /** @var Post $data */
//            $post = $event->getData();
//            $thumbnail = $post->getThumbnail();
//            if(is_int($thumbnail)){
//                $picture = $this->pictureService->findById($thumbnail);
//                $post->setThumbnail($picture->getImage());
//            }
//        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
