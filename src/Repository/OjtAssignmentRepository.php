<?php

namespace App\Repository;

use App\Entity\OjtAssignment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OjtAssignment>
 */
class OjtAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OjtAssignment::class);
    }

    public function findByStudent(User $student): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.student = :student')
            ->setParameter('student', $student)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBySupervisor(User $supervisor): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.supervisor = :supervisor')
            ->setParameter('supervisor', $supervisor)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
