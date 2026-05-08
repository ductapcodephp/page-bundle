<?php

namespace AmzsCMS\PageBundle\DataTable;

use AmzsCMS\CoreBundle\Service\Datatable\BaseDataTable;
use AmzsCMS\PageBundle\DataType\PostStatusType;
use AmzsCMS\PageBundle\Entity\Page;
use AmzsCMS\PageBundle\Repository\PageRepository;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class PageDataTable extends BaseDataTable
{
    protected $entityAlias = 'page';
    private  $translatableListener;
    private  $parameterBag;
    private $csrfTokenManager;
    private $defaultLocale;
    public function __construct(PageRepository $repository ,
        TranslatableListener $translatableListener,
        ParameterBagInterface $parameterBag,
        CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->translatableListener = $translatableListener;
        $this->parameterBag = $parameterBag;
        $this->defaultLocale = $parameterBag->get('language')['default'];
        $this->csrfTokenManager = $csrfTokenManager;
        parent::__construct($repository);
    }

    // ================== Tùy chỉnh QueryBuilder từ đầu (nếu cần JOIN) ==================
    protected function createBaseQueryBuilder(): QueryBuilder
    {
        return $this->repository->createQueryBuilder('page')
//            ->where('page')
//            ->setParameter('username', 'root')
            // ->leftJoin('e.category', 'c')
            // ->addSelect('c');
            ;
    }

    protected function applyDefaultFilters(QueryBuilder $qb, Request $request): void
    {
    }
    protected function applyCustomFilters(QueryBuilder $qb, Request $request): void
    {
        $locale = $request->query->get('language');

        if (empty($locale)) {
            return;
        }
        $this->translatableListener->setTranslatableLocale($locale);
        if ($locale === $this->defaultLocale) {
            return;
        }
    }

    protected function getColumnMap(): array
    {
        return [
            0 => 'createdAt',
//            1 => 'name',
//            2 => 'url',
//            3 => 'language',
        ];
    }

    protected function getSearchableFields(): array
    {
        return ['name'];
    }

    protected function formatData(array $entities): array
    {
        $data = [];
        /** @var Page $page */
        foreach ($entities as $index => $page) {
            $data[] = [
                'index'      => $index + 1,
                'id'         => $page->getId(),
                'name'       => $page->getName(),
                'slug'       => $page->getPost()->getSlug(),
                'published'  => PostStatusType::getNameByPublishType($page->getPost()->getPublished()),

                'created_at' => $page->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $page->getUpdatedAt()->format('Y-m-d H:i:s'),
                '_csrf_token' => $this->csrfTokenManager->getToken('delete-article-'.$page->getId())->getValue(),

            ];
        }
        return $data;
    }
}