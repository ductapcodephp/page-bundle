<?php

namespace AmzsCMS\PageBundle\Repository;

use AmzsCMS\PageBundle\DataType\PostStatusType;
use AmzsCMS\PageBundle\Entity\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Page>
 */
class PageRepository extends ServiceEntityRepository
{
    const ALIAS = 'page';

    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Page::class);
        $this->paginator = $paginator;
    }

    /**
     * Lấy tất cả bài post với phân trang
     */
    public function findAllPaginated($page = 1, $limit = 10): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder(self::ALIAS);

        $queryBuilder
            ->where(
                $queryBuilder->expr()->isNull(self::ALIAS . '.deletedAt')
            )
            ->orderBy(self::ALIAS . '.createdAt', 'DESC');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            $limit
        );
    }

    public function findOneBySlug(string $slug): ?Page
    {
        $qb = $this->createQueryBuilder('page');

        $qb->join('page.post', 'post');

        $qb->where(
            $qb->expr()->eq('post.slug', $qb->expr()->literal($slug)),
            $qb->expr()->isNull('post.deletedAt'),
            $qb->expr()->eq(
                'post.published',
                $qb->expr()->literal(PostStatusType::PUBLISH_TYPE_PUBLISHED)
            )
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    // custom
    public function findBySlug($slug)
    {
        $qb = $this->createQueryBuilder('page');

        $qb->join('page.post', 'post');

        $qb->where(
            $qb->expr()->eq('post.slug', $qb->expr()->literal($slug)),
            $qb->expr()->isNull('post.deletedAt'),
            $qb->expr()->eq(
                'post.published',
                $qb->expr()->literal(PostStatusType::PUBLISH_TYPE_PUBLISHED)
            )
        );

        return $qb;
    }
}