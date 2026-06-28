<?php

namespace App\EventListener;

use App\Entity\DailyLog;
use App\Entity\OjtAssignment;
use App\Service\CertificateGeneratorService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postUpdate, entity: DailyLog::class)]
class LogApprovalListener
{
    public function __construct(
        private readonly CertificateGeneratorService $certificateGenerator,
    ) {
    }

    public function postUpdate(DailyLog $dailyLog, PostUpdateEventArgs $event): void
    {
        // Only trigger on status change to APPROVED
        if ($dailyLog->getStatus() !== DailyLog::STATUS_APPROVED) {
            return;
        }

        // Check if the status field actually changed (avoid re-triggering)
        $em = $event->getObjectManager();
        $unitOfWork = $em->getUnitOfWork();
        $changeSet = $unitOfWork->getEntityChangeSet($dailyLog);

        if (!isset($changeSet['status'])) {
            return;
        }

        $oldStatus = $changeSet['status'][0] ?? null;
        if ($oldStatus === DailyLog::STATUS_APPROVED) {
            return; // Was already approved, no-op
        }

        $assignment = $dailyLog->getAssignment();
        if (!$assignment) {
            return;
        }

        // Add hours worked to the assignment
        $newHoursCompleted = $assignment->getHoursCompleted() + $dailyLog->getHoursWorked();
        $assignment->setHoursCompleted($newHoursCompleted);

        $em->persist($assignment);
        $em->flush();

        // Check if assignment is now complete
        if ($newHoursCompleted >= $assignment->getRequiredHours()) {
            $assignment->setStatus(OjtAssignment::STATUS_COMPLETED);
            $assignment->setEndDate(new \DateTime());

            // Generate certificate if not already generated
            if ($assignment->getCertificate() === null) {
                $this->certificateGenerator->generate($assignment);
            }

            $em->persist($assignment);
            $em->flush();
        }
    }
}
