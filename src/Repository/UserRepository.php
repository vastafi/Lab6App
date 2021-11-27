<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use function get_class;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     * @param UserInterface $user
     * @param string $newEncodedPassword
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function filter(?string $email, int $limit, int $page): array
    {
        return $this
            ->getFiltrationQuery($email)
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPages( ?string $email, int $limit): int
    {
        $amountOfUser = $this
            ->getFiltrationQuery($email)
            ->select("COUNT(u)")
            ->getQuery()
            ->getSingleScalarResult();

        return ceil($amountOfUser / $limit);
    }

    public function countUser($email):int {
        $query = $this->createQueryBuilder('u');
        if($email){
            $query = $query->andWhere('LOWER(u.email) LIKE :email')
                ->setParameter('email', strtolower($email.'%'));
        }

        $query->add('select', $query->expr()->count('u'));
        $q = $query->getQuery();
        return $q->getSingleScalarResult();
    }

    /**
     * @param string|null $email
        * @return QueryBuilder
     */
    protected function getFiltrationQuery( ?string $email): QueryBuilder
    {
        $query = $this->createQueryBuilder('u');


        if ($email) {
            $query->andWhere('LOWER(u.email) LIKE :email')
                ->setParameter('email',  strtolower($email . "%"));
        }

        $query->orderBy('u.id', 'ASC');
        return $query;
    }
}
