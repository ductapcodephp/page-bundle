<?php

namespace AmzsCMS\PageBundle\DataTable;

use App\Core\DataType\LanguageDataType;
use App\Core\DataType\MenuDataType;
use App\Core\DataType\PostStatusType;
use App\Core\DataType\RoleDataType;
use App\Core\Entity\Menu;
use App\Core\Entity\Page;
use App\Core\Entity\User;
use App\Core\Repository\PageRepository;
use App\Repository\UserRepository;
use App\Service\DataTable\BaseDataTable;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class PageDataTable extends BaseDataTable
{
    protected $entityAlias = 'page';
    public function __construct(PageRepository $repository)
    {
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

    protected function applyCustomFilters(QueryBuilder $qb, Request $request): void
    {

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
                'lang_code'  => $page->getLanguage(),

                'language'   => LanguageDataType::getNameByCode($page->getLanguage()),
                'published'  => PostStatusType::getNameByPublishType($page->getPost()->getPublished()),
                'created_at' => $page->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $page->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
        }
        return $data;
    }
}