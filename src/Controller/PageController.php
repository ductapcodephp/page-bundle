<?php

declare(strict_types=1);

namespace AmzsCMS\PageBundle\Controller;

use AmzsCMS\PageBundle\Constant\PageRoute;
use AmzsCMS\PageBundle\DataTable\PageDataTable;
use AmzsCMS\PageBundle\DataType\PostStatusType;
use AmzsCMS\PageBundle\Entity\Page;
use AmzsCMS\PageBundle\Entity\SocialSharing;
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

    private function loadTranslations(Page $page): array
    {
        $translationRepo = $this->entityManager->getRepository(Translation::class);
        $allTranslations = $translationRepo->findTranslations($page);

        $translations = [];
        foreach ($this->locales as $locale) {
            $translations[$locale] = [
                'title'       => $allTranslations[$locale]['title'] ?? $page->getTitle(),
                'description' => $allTranslations[$locale]['description'] ?? $page->getDescription(),
                'content'     => $allTranslations[$locale]['content'] ?? $page->getContent(),
            ];
        }

        return $translations;
    }

    private function saveTranslations(Page $page, $form): void
    {
        $translationRepo = $this->entityManager->getRepository(Translation::class);
        $defaultLocale = $this->locales[0];

        foreach ($this->locales as $locale) {
            $title = $form->get("title_$locale")->getData();
            $description = $form->get("description_$locale")->getData();
            $content = $form->get("content_$locale")->getData();

            if ($locale === $defaultLocale) {
                $page->setTitle($title ?? '');
                $page->setDescription($description);
                $page->setContent($content);
            }
            $translationRepo->translate($page, 'title', $locale, $title);
            $translationRepo->translate($page, 'description', $locale, $description);
            $translationRepo->translate($page, 'content', $locale, $content);
        }

        $this->entityManager->persist($page);
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

        $socialSharing = new SocialSharing();
        $socialSharing->setPage($page);
        $page->setSocialSharing($socialSharing);

        $translations = [];
        foreach ($this->locales as $locale) {
            $translations[$locale] = [
                'title'       => null,
                'description' => null,
                'content'     => null,
            ];
        }

        $form = $this->createForm(AddPageForm::class, $page, [
            'locales'      => $this->locales,
            'translations' => $translations,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->saveTranslations($page, $form);

            $slugify = new Slugify();
            $slug = $slugify->slugify($page->getName());
            $page->setSlug($slug);

            $this->entityManager->persist($page);
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

        if ($page->getSocialSharing() === null) {
            $socialSharing = new SocialSharing();
            $socialSharing->setPage($page);
            $page->setSocialSharing($socialSharing);
        }

        $translations = $this->loadTranslations($page);

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
            $isHot = $form->get('isHot')->getData();
            $isNew = $form->get('isNew')->getData();
            $isKeepSlug = $request->request->get('keep_slug');

            if (is_string($isHot) && $isHot == 'on') {
                $page->setIsHot(PostStatusType::HOT_TYPE_HOT);
            }

            if (is_string($isNew) && $isNew == 'on') {
                $page->setIsNew(PostStatusType::NEW_TYPE_NEW);
            }

            if (is_null($isKeepSlug)) {
                $slugify = new Slugify();
                $slug = $slugify->slugify($page->getName());
                $page->setSlug($slug);
            }

            $this->saveTranslations($page, $form);

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
            throw $this->createNotFoundException('Page not found');
        }

        $csrfToken = $request->query->get('_csrf_token');

        if (!$this->isCsrfTokenValid('delete-page-' . $id, $csrfToken)) {
            throw new AccessDeniedHttpException();
        }

        $this->entityManager->remove($page);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Page deleted successfully'
        ]);
    }
}