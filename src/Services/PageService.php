<?php

namespace AmzsCMS\PageBundle\Services;

use AmzsCMS\PageBundle\Entity\Page;
use AmzsCMS\PageBundle\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PageService extends AbstractController
{
    private $entityManager;
    private $pageRepository;

    public function __construct(EntityManagerInterface $entityManager, PageRepository $pageRepository)
    {
        $this->entityManager = $entityManager;
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