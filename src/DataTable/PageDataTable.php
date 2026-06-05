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
    private $translatableListener;
    private $parameterBag;
    private $csrfTokenManager;
    private $defaultLocale;

    public function __construct(
        PageRepository $repository,
        TranslatableListener $translatableListener,
        ParameterBagInterface $parameterBag,
        CsrfTokenManagerInterface $csrfTokenManager
    ) {
        $this->translatableListener = $translatableListener;
        $this->parameterBag = $parameterBag;
        $this->defaultLocale = $parameterBag->get('language')['default'] ?? 'vi';
        $this->csrfTokenManager = $csrfTokenManager;
        parent::__construct($repository);
    }

    protected function createBaseQueryBuilder(): QueryBuilder
    {
        return $this->repository->createQueryBuilder('page');
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
                'index'       => $index + 1,
                'id'          => $page->getId(),
                'name'        => $page->getName(),
                'slug'        => $page->getSlug(),
                'published'   => PostStatusType::getNameByPublishType((int)$page->getPublished()),
                'created_at'  => $page->getCreatedAt() ? $page->getCreatedAt()->format('Y-m-d H:i:s') : null,
                'updated_at'  => $page->getUpdatedAt() ? $page->getUpdatedAt()->format('Y-m-d H:i:s') : null,
                '_csrf_token' => $this->csrfTokenManager->getToken('delete-page-' . $page->getId())->getValue(),
            ];
        }
        return $data;
    }
}