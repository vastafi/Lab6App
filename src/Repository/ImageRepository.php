<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @method Image|null find($id, $lockMode = null, $lockVersion = null)
 * @method Image|null findOneBy(array $criteria, array $orderBy = null)
 * @method Image[]    findAll()
 * @method Image[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    public function filter(?string $tag, int $limit, int $page): array
    {
        return $this
            ->getFiltrationQuery($tag)
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPages( ?string $tag, int $limit): int
    {
        $amountOfImages = $this
            ->getFiltrationQuery($tag)
            ->select("COUNT(i)")
            ->getQuery()
            ->getSingleScalarResult();

        return ceil($amountOfImages / $limit);
    }

     public function countImages($tag):int {
        $query = $this->createQueryBuilder('i');
        if($tag){
            $query = $query->andWhere('LOWER(i.tag) LIKE :tag')
                ->setParameter('tag', strtolower($tag .'%'));
        }

        $query->add('select', $query->expr()->count('i'));
        $q = $query->getQuery();
        return $q->getSingleScalarResult();
    }

    /**
     * @param string|null $tag
     * @return QueryBuilder
     */
    protected function getFiltrationQuery( ?string $tag): QueryBuilder
    {
        $query = $this->createQueryBuilder('i');

        if ($tag) {
            $query->andWhere('i.tag LIKE :tag')
                ->setParameter('tag',  strtolower("%".$tag . "%"));
        }

        $query->orderBy('i.id', 'ASC');
        return $query;
    }


    // /**
    //  * @return Image[] Returns an array of Image objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Image
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
