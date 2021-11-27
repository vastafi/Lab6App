<?php

namespace App\Repository;

use App\Entity\Image;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @param $category
     * @param $name
     * @param $limit
     * @param $page
     * @return Product[] Returns an array of Product objects
     */

    public function filter(?string $category, ?string $name, int $limit, int $page): array
    {
        return $this
            ->getFiltrationQuery($category, $name)
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPages(?string $category, ?string $name, int $limit): int
    {
        $amountOfProducts = $this
            ->getFiltrationQuery($category, $name)
            ->select("COUNT(p)")
            ->getQuery()
            ->getSingleScalarResult();

        return ceil($amountOfProducts / $limit);
    }

    /**
     * @param string|null $category
     * @param string|null $name
     * @return QueryBuilder
     */
    protected function getFiltrationQuery(?string $category, ?string $name): QueryBuilder
    {
        $query = $this->createQueryBuilder('p');

        if ($category) {
            $query->andWhere('LOWER(p.category ) = :category')
                ->setParameter('category', strtolower($category));
        }
        if ($name) {
            $query->andWhere('LOWER(p.name) LIKE :name')
                ->setParameter('name',  strtolower($name . "%"));
        }

        $query->orderBy('p.code', 'ASC');
        return $query;
    }

    public function getCategories(): array
    {
        $categories = $this
            ->createQueryBuilder('product')
            ->select("DISTINCT product.category")
            ->getQuery()
            ->getResult();

        return array_column($categories, 'category');
    }

    public function findByImage(Image $image): array
    {
        return $this
            ->createQueryBuilder('p')
            ->where('p.productImages LIKE :productImage')
            ->setParameter('productImage', '%"' .$image->getPath() .'"%')
            ->getQuery()
            ->getResult();
    }
}
