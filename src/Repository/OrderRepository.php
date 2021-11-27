<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function filter(?int $id, int $limit, int $page): array
    {
        return $this
            ->getFiltrationQuery($id)
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPages( ?int $id, int $limit): int
    {
        $amountOfOrders = $this
            ->getFiltrationQuery($id)
            ->select("COUNT(u)")
            ->getQuery()
            ->getSingleScalarResult();

        return ceil($amountOfOrders / $limit);
    }

    public function countOrders($id):int {
        $query = $this->createQueryBuilder('u');
        if($id){
            $query = $query->andWhere('LOWER(u.id) LIKE :id')
                ->setParameter('id', strtolower($id.'%'));
        }

        $query->add('select', $query->expr()->count('u'));
        $q = $query->getQuery();
        return $q->getSingleScalarResult();
    }

    /**
     * @param string|null $userEmail
     * @return QueryBuilder
     */
    protected function getFiltrationQuery( ?int $id): QueryBuilder
    {
        $query = $this->createQueryBuilder('u');

        if ($id) {
            $query
                ->andWhere('LOWER(u.id) LIKE :id')
                ->setParameter('id', $id . "%" );
        }

        $query->orderBy('u.id', 'DESC');
        return $query;
    }
}

