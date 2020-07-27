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

    /**
     * @return Note[] Returns an array of Note objects
     */
    public function findByUser($user)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findById($user, $id): ?Note
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.id = :id')
            ->setParameter('user', $user)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function delByUser($user)
    {
        return $this->createQueryBuilder('n')
            ->delete()
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
        ;
    }
}
