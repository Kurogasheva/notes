<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * @return Tag[] Returns an array of Tag objects
     */
    public function findByUser($user)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findById($user, $id): ?Tag
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.id = :id')
            ->setParameter('user', $user)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByBody($user, $body): ?Tag
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.body = :body')
            ->setParameter('user', $user)
            ->setParameter('body', $body)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function delByUser($user)
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
        ;
    }
}
