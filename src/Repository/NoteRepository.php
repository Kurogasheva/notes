<?php

namespace App\Repository;

use App\Entity\Note;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Note|null find($id, $lockMode = null, $lockVersion = null)
 * @method Note|null findOneBy(array $criteria, array $orderBy = null)
 * @method Note[]    findAll()
 * @method Note[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    public function getCountByUser($user)
    {
        return $this->createQueryBuilder('n')
                    ->select('count(n.id)')
                    ->andWhere('n.user = :user')
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->getSingleScalarResult();
    }

    public function getCountByTag($id)
    {
        return $this->createQueryBuilder('n')
                    ->leftJoin('n.tags', 't')
                    ->select('count(n.id)')
                    ->andWhere('t.id = :tagId')
                    ->setParameter('tagId', $id)
                    ->getQuery()
                    ->getSingleScalarResult();
    }

    public function getQB($user)
    {
        return $this->createQueryBuilder('n')
                    ->andWhere('n.user = :user')
                    ->setParameter('user', $user)
                    ->orderBy('n.id', 'ASC');
    }

    public function getIdsQB($user)
    {
        return $this->createQueryBuilder('n')
                    ->select('n.id')
                    ->andWhere('n.user = :user')
                    ->setParameter('user', $user)
                    ->orderBy('n.id', 'ASC');
    }

    public function getByTagQB($id)
    {
        return $this->createQueryBuilder('n')
                    ->leftJoin('n.tags', 't')
                    ->andWhere('t.id = :tagId')
                    ->setParameter('tagId', $id)
                    ->orderBy('n.id', 'ASC');
    }

    public function findById($user, $id): ?Note
    {
        return $this->createQueryBuilder('n')
                    ->andWhere('n.user = :user')
                    ->andWhere('n.id = :id')
                    ->setParameter('user', $user)
                    ->setParameter('id', $id)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    public function delByIds($ids)
    {
        return $this->createQueryBuilder('n')
                    ->delete()
                    ->andWhere('n.id in (:ids)')
                    ->setParameter('ids', $ids)
                    ->getQuery()
                    ->getResult();
    }

    public function delByUser($user)
    {
        return $this->createQueryBuilder('n')
                    ->delete()
                    ->andWhere('n.user = :user')
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->getResult();
    }
}
