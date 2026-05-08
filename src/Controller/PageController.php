<?php

declare(strict_types=1);

namespace AmzsCMS\PageBundle\Controller;


use AmzsCMS\PageBundle\Constant\PageRoute;
use AmzsCMS\PageBundle\DataTable\PageDataTable;
use AmzsCMS\PageBundle\DataType\PostStatusType;
use AmzsCMS\PageBundle\Entity\Page;
use AmzsCMS\PageBundle\Form\AddPageForm;
use AmzsCMS\PageBundle\Services\PageService;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
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
        //check permission
//        $this->denyAccessUnlessGranted(PageVoter::ADD, $page);

        $form = $this->createForm(AddPageForm::class, $page);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //logic here
            $this->entityManager->persist($page);
            $this->entityManager->persist($page->getPost());
            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'Page added successfully',
                'redirect' => $this->generateUrl(PageRoute::ROUTE_EDIT, ['id' => $page->getId()])
            ]);
        }
        return $this->render('@AmzsPage/page/add.html.twig', [
            'form' => $form->createView(),
            'page' => $page,
        ]);
    }
    public function edit(Request $request, int $id): Response
    {
        $page = $this->pageService->findOneById($id);
        if (!$page instanceof Page) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(AddPageForm::class, $page, [
            'action' => $this->generateUrl(PageRoute::ROUTE_EDIT,
                ['id' => $page->getId()]),
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
                $slug = $slugify->slugify($page->getPost()->getTitle());
                $page->getPost()->setSlug($slug);
            }

            $this->entityManager->flush();
            return new JsonResponse([
                'message' => 'Page edited successfully',
                'redirect' => $this->generateUrl(PageRoute::ROUTE_EDIT,
                    ['id' => $page->getId()])
            ]);
        }

        return $this->render('@AmzsPage/page/add.html.twig', [
            'form' => $form->createView(),
            'page' => $page
        ]);
    }

    public function delete(Request $request, int $id): Response
    {
        $csrfToken = $request->query->get('_csrf_token');
        if (!$this->isCsrfTokenValid('delete-page', $csrfToken))
            throw new AccessDeniedHttpException();

        $page = $this->pageService->findOneById($id);
        $page->setArchived(ArchivedDataType::ARCHIVED);

        $post = $page->getPost();
        $slug = $post->getSlug() .'-removed-' .Uuid::v4()->toBase32();
        $post->setSlug($slug);
        $this->entityManager->flush();

        return new JsonResponse(['message' => '"Deleted Page Successfully"']);
    }
}
