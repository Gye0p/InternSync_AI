<?php

namespace App\EventListener;

use App\Entity\DailyLog;
use App\Entity\OjtAssignment;
use App\Service\CertificateGeneratorService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postUpdate, entity: DailyLog::class)]
#[AsDoctrineListener(event: Events::postFlush)]
class LogApprovalListener
{
    /** @var int[] Assignment IDs queued for certificate generation after flush */
    private array $assignmentsPendingCertificate = [];

    public function __construct(
        private readonly CertificateGeneratorService $certificateGenerator,
    ) {
    }

    public function postUpdate(DailyLog $dailyLog, PostUpdateEventArgs $event): void
    {
        if ($dailyLog->getStatus() !== DailyLog::STATUS_APPROVED) {
            return;
        }

        $em = $event->getObjectManager();
        $unitOfWork = $em->getUnitOfWork();
        $changeSet = $unitOfWork->getEntityChangeSet($dailyLog);

        if (!isset($changeSet['status'])) {
            return;
        }

        $oldStatus = $changeSet['status'][0] ?? null;
        if ($oldStatus === DailyLog::STATUS_APPROVED) {
            return;
        }

        $assignment = $dailyLog->getAssignment();
        if (!$assignment) {
            return;
        }

        $newHoursCompleted = $assignment->getHoursCompleted() + $dailyLog->getHoursWorked();
        $assignment->setHoursCompleted($newHoursCompleted);

        if ($newHoursCompleted >= $assignment->getRequiredHours()) {
            if ($assignment->getStatus() !== OjtAssignment::STATUS_COMPLETED) {
                $assignment->setStatus(OjtAssignment::STATUS_COMPLETED);
                $assignment->setEndDate(new \DateTime());
            }

            if ($assignment->getCertificate() === null) {
                $this->assignmentsPendingCertificate[$assignment->getId()] = $assignment->getId();
            }
        }

        $em->persist($assignment);
        $unitOfWork->recomputeSingleEntityChangeSet(
            $em->getClassMetadata(OjtAssignment::class),
            $assignment
        );
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if ($this->assignmentsPendingCertificate === []) {
            return;
        }

        $pendingIds = $this->assignmentsPendingCertificate;
        $this->assignmentsPendingCertificate = [];

        $em = $event->getObjectManager();
        foreach ($pendingIds as $assignmentId) {
            $assignment = $em->find(OjtAssignment::class, $assignmentId);
            if ($assignment && $assignment->getCertificate() === null) {
                $this->certificateGenerator->generate($assignment);
            }
        }
    }
}
