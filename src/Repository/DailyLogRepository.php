<?php

namespace App\Repository;

use App\Entity\DailyLog;
use App\Entity\OjtAssignment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DailyLog>
 */
class DailyLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyLog::class);
    }

    public function findByAssignment(OjtAssignment $assignment): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.assignment = :assignment')
            ->setParameter('assignment', $assignment)
            ->orderBy('l.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param OjtAssignment[] $assignments
     */
    public function findByAssignments(array $assignments): array
    {
        if (empty($assignments)) {
            return [];
        }

        return $this->createQueryBuilder('l')
            ->andWhere('l.assignment IN (:assignments)')
            ->setParameter('assignments', $assignments)
            ->orderBy('l.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStudent(User $user): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.assignment', 'a')
            ->andWhere('a.student = :student')
            ->setParameter('student', $user)
            ->orderBy('l.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBySupervisor(User $user): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.assignment', 'a')
            ->andWhere('a.supervisor = :supervisor')
            ->setParameter('supervisor', $user)
            ->orderBy('l.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getAverageClarityScore(): ?float
    {
        $result = $this->createQueryBuilder('l')
            ->select('AVG(l.clarityScore)')
            ->andWhere('l.clarityScore IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? round((float) $result, 2) : null;
    }

    /**
     * Returns aggregated skill tag frequency across all logs.
     */
    public function getAllSkillTags(): array
    {
        $logs = $this->createQueryBuilder('l')
            ->select('l.skillTags')
            ->andWhere('l.skillTags IS NOT NULL')
            ->getQuery()
            ->getResult();

        $frequency = [];
        foreach ($logs as $row) {
            if (is_array($row['skillTags'])) {
                foreach ($row['skillTags'] as $tag) {
                    $tag = strtolower(trim($tag));
                    if ($tag !== '') {
                        $frequency[$tag] = ($frequency[$tag] ?? 0) + 1;
                    }
                }
            }
        }

        arsort($frequency);
        return $frequency;
    }
}
