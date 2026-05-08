<?php

namespace App\Core\Services;

use App\Core\Entity\Page;
use App\Core\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PageService extends AbstractController
{
    private $entityManager;
    private $pageRepository;
    private $fileUploadService;

    public function __construct(EntityManagerInterface $entityManager, PageRepository $pageRepository, FileUploadService $fileUploadService)
    {
        $this->entityManager = $entityManager;
        $this->fileUploadService = $fileUploadService;
        $this->pageRepository = $pageRepository;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function findAllPaginated(): PaginationInterface
    {
        return $this->pageRepository->findAllPaginated();
    }


    public function findOneById($id): ?Page
    {
        return $this->pageRepository->find($id);
    }

    public function findOneBySlug(string $slug): ?Page
    {
        return $this->pageRepository->findOneBySlug($slug);
    }

}