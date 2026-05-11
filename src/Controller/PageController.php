<?php

declare(strict_types=1);

namespace AmzsCMS\PageBundle\Controller;


use AmzsCMS\ArticleBundle\Entity\Post;
use AmzsCMS\ArticleBundle\Entity\SocialSharing;
use AmzsCMS\PageBundle\Constant\PageRoute;
use AmzsCMS\PageBundle\DataTable\PageDataTable;
use AmzsCMS\PageBundle\DataType\PostStatusType;
use AmzsCMS\PageBundle\Entity\Page;
use AmzsCMS\PageBundle\Form\AddPageForm;
use AmzsCMS\PageBundle\Services\PageService;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\Translation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends AbstractController
{
    private $pageService;
    private $entityManager;
    private array $locales;

    public function __construct(PageService $pageService, EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag)
    {
        $this->entityManager = $entityManager;
        $this->pageService = $pageService;
        $langConfig           = $parameterBag->get('language');
        $this->locales        = $langConfig['locales'] ?? ['vi'];
    }
    private function loadTranslations(Post $post): array
    {
        $translationRepo = $this->entityManager->getRepository(Translation::class);
        $allTranslations = $translationRepo->findTranslations($post);

        $translations = [];
        foreach ($this->locales as $locale) {
            $translations[$locale] = [
                'title'       => $allTranslations[$locale]['title'] ?? $post->getTitle(),
                'description' => $allTranslations[$locale]['description'] ?? $post->getDescription(),
                'content'     => $allTranslations[$locale]['content'] ?? $post->getContent(),
            ];
        }

        return $translations;
    }

    private function saveTranslations(Post $post, $postForm): void
    {
        $translationRepo = $this->entityManager->getRepository(Translation::class);

        $defaultLocale = $this->locales[0];

        foreach ($this->locales as $locale) {

            $title = $postForm->get("title_$locale")->getData();
            $description = $postForm->get("description_$locale")->getData();
            $content = $postForm->get("content_$locale")->getData();

            if ($locale === $defaultLocale) {

                $post->setTitle($title ?? '');
                $post->setDescription($description);
                $post->setContent($content);
            }
            $translationRepo->translate($post, 'title', $locale, $title);
            $translationRepo->translate($post, 'description', $locale, $description);
            $translationRepo->translate($post, 'content', $locale, $content);
        }

        $this->entityManager->persist($post);
    }
    public function index(Request $request): Response
    {
        return $this->render('@AmzsPage/page/index.html.twig',[
            'locales' => $this->locales,
        ]);
    }

    public function data(Request $request, PageDataTable $dataTable): Response
    {
        return $this->json($dataTable->getData($request));
    }

    public function add(Request $request): Response
    {
        $page = new Page();
        $post    = new Post();
        $page->setPost($post);
        $post->setPage($page);
        //check permission
//        $this->denyAccessUnlessGranted(PageVoter::ADD, $page);
        $socialSharing = new SocialSharing();
        $socialSharing->setPost($post);
        $post->setSocialSharing($socialSharing);

        $translations = [];
        foreach ($this->locales as $locale) {
            $translations[$locale] = [
                'title'       => null,
                'description' => null,
                'content'     => null,
            ];
        }

        $form = $this->createForm(AddPageForm::class, $page,[
            'locales'      => $this->locales,
            'translations' => $translations,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->saveTranslations($page->getPost(), $form->get('post'));
            $slugify = new Slugify();
            $slug = $slugify->slugify($page->getName());
            $page->getPost()->setSlug($slug);
            $this->entityManager->persist($page);
            $this->entityManager->persist($page->getPost());
            $this->entityManager->flush();
            return new JsonResponse([
                'message' => 'Page added successfully',
            ]);
        }
        return $this->render('@AmzsPage/page/add_or_edit.html.twig', [
            'form' => $form->createView(),
            'page' => $page,
            'locales' => $this->locales,
        ]);
    }
    public function edit(Request $request, int $id): Response
    {
        $page = $this->pageService->findOneById($id);

        if (!$page instanceof Page) {
            throw new NotFoundHttpException();
        }

        if (!$page->getPost()) {
            $post = new Post();
            $page->setPost($post);
            $post->setPage($page);
        }

        $post = $page->getPost();

        if ($post->getSocialSharing() === null) {
            $socialSharing = new SocialSharing();
            $socialSharing->setPost($post);
            $post->setSocialSharing($socialSharing);
        }

        $translations = $this->loadTranslations($post);

        $form = $this->createForm(AddPageForm::class, $page, [
            'action' => $this->generateUrl(
                PageRoute::ROUTE_EDIT,
                ['id' => $page->getId()]
            ),
            'locales'      => $this->locales,
            'translations' => $translations,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $isHot = $form->get('post')->get('isHot')->getData();
            $isNew = $form->get('post')->get('isNew')->getData();
            $isKeepSlug = $request->request->get('keep_slug');

            if (is_string($isHot) && $isHot == 'on') {
                $page->getPost()->setIsHot(PostStatusType::HOT_TYPE_HOT);
            }

            if (is_string($isNew) && $isNew == 'on') {
                $page->getPost()->setIsNew(PostStatusType::NEW_TYPE_NEW);
            }

            if (is_null($isKeepSlug)) {
                $slugify = new Slugify();
                $slug = $slugify->slugify($page->getName());
                $page->getPost()->setSlug($slug);
            }

            $this->saveTranslations(
                $page->getPost(),
                $form->get('post')
            );

            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'Page edited successfully',
                'redirect' => $this->generateUrl(
                    PageRoute::ROUTE_EDIT,
                    ['id' => $page->getId()]
                )
            ]);
        }

        return $this->render('@AmzsPage/page/add_or_edit.html.twig', [
            'form' => $form->createView(),
            'page' => $page,
            'locales' => $this->locales,
        ]);
    }

    public function delete(Request $request, int $id): Response
    {
        $page = $this->pageService->findOneById($id);

        if (!$page) {
            throw $this->createNotFoundException('Article not found');
        }

        $csrfToken = $request->query->get('_csrf_token');

        if (!$this->isCsrfTokenValid('delete-page-' . $id, $csrfToken)) {
            throw new AccessDeniedHttpException();
        }

        $this->entityManager->remove($page);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Article deleted successfully'
        ]);
    }
}
