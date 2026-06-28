<?php

namespace App\Controller;

use App\Entity\DailyLog;
use App\Entity\OjtAssignment;
use App\Entity\User;
use App\Repository\DailyLogRepository;
use App\Repository\OjtAssignmentRepository;
use App\Service\AiLogReviewerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/logs')]
class DailyLogController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DailyLogRepository $dailyLogRepository,
        private readonly OjtAssignmentRepository $assignmentRepository,
        private readonly AiLogReviewerService $aiReviewer,
    ) {
    }

    /**
     * POST /api/logs — Student submits a daily log.
     * Calls AI reviewer, saves feedback alongside the log.
     */
    #[Route('', name: 'api_logs_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        $assignmentId = $data['assignment_id'] ?? null;
        $content = trim($data['content'] ?? '');
        $dateStr = $data['date'] ?? null;
        $hoursWorked = (float) ($data['hours_worked'] ?? 8);

        if (!$assignmentId || $content === '') {
            return $this->json(['error' => 'assignment_id and content are required.'], Response::HTTP_BAD_REQUEST);
        }

        $assignment = $this->assignmentRepository->find($assignmentId);
        if (!$assignment) {
            return $this->json(['error' => 'Assignment not found.'], Response::HTTP_NOT_FOUND);
        }

        // Students can only submit logs for their own assignments
        if ($user->getRole() === User::ROLE_STUDENT && $assignment->getStudent()->getId() !== $user->getId()) {
            return $this->json(['error' => 'You can only submit logs for your own assignments.'], Response::HTTP_FORBIDDEN);
        }

        if ($assignment->getStatus() === OjtAssignment::STATUS_COMPLETED) {
            return $this->json(['error' => 'Cannot submit logs for a completed assignment.'], Response::HTTP_BAD_REQUEST);
        }

        // Parse date
        try {
            $date = $dateStr ? new \DateTime($dateStr) : new \DateTime();
        } catch (\Exception) {
            return $this->json(['error' => 'Invalid date format.'], Response::HTTP_BAD_REQUEST);
        }

        // Call AI reviewer (non-blocking — returns null on failure)
        $aiFeedback = $this->aiReviewer->review($content);

        $log = new DailyLog();
        $log->setAssignment($assignment);
        $log->setDate($date);
        $log->setContent($content);
        $log->setHoursWorked($hoursWorked);
        $log->setAiFeedback($aiFeedback);

        if ($aiFeedback) {
            $log->setSkillTags($aiFeedback['skill_tags'] ?? null);
            $log->setClarityScore($aiFeedback['clarity_score'] ?? null);
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Daily log submitted successfully.',
            'log' => $log->toArray(),
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/logs — List logs filtered by role.
     * Students see their own logs; supervisors see assigned students' logs;
     * coordinators see everything.
     */
    #[Route('', name: 'api_logs_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $logs = match ($user->getRole()) {
            User::ROLE_STUDENT => $this->getStudentLogs($user),
            User::ROLE_SUPERVISOR => $this->getSupervisorLogs($user),
            User::ROLE_COORDINATOR => $this->getAllLogs(),
            default => [],
        };

        return $this->json([
            'logs' => array_map(fn(DailyLog $log) => $log->toArray(), $logs),
        ]);
    }

    /**
     * GET /api/logs/{id} — Single log detail.
     */
    #[Route('/{id}', name: 'api_logs_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $log = $this->dailyLogRepository->find($id);
        if (!$log) {
            return $this->json(['error' => 'Log not found.'], Response::HTTP_NOT_FOUND);
        }

        // Access check
        if (!$this->canAccessLog($user, $log)) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        return $this->json(['log' => $log->toArray()]);
    }

    /**
     * PATCH /api/logs/{id}/approve — Supervisor approves log.
     * Recomputes hours on the assignment.
     */
    #[Route('/{id}/approve', name: 'api_logs_approve', methods: ['PATCH'])]
    public function approve(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!in_array($user->getRole(), [User::ROLE_SUPERVISOR, User::ROLE_COORDINATOR], true)) {
            return $this->json(['error' => 'Only supervisors and coordinators can approve logs.'], Response::HTTP_FORBIDDEN);
        }

        $log = $this->dailyLogRepository->find($id);
        if (!$log) {
            return $this->json(['error' => 'Log not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($log->getStatus() !== DailyLog::STATUS_PENDING) {
            return $this->json(['error' => 'Only pending logs can be approved.'], Response::HTTP_BAD_REQUEST);
        }

        // Supervisor can only approve logs for their assigned students
        $assignment = $log->getAssignment();
        if ($user->getRole() === User::ROLE_SUPERVISOR && $assignment->getSupervisor()->getId() !== $user->getId()) {
            return $this->json(['error' => 'You can only approve logs for your assigned students.'], Response::HTTP_FORBIDDEN);
        }

        $log->setStatus(DailyLog::STATUS_APPROVED);

        // The postUpdate event listener handles hours recomputation and certificate generation
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Log approved successfully.',
            'log' => $log->toArray(),
        ]);
    }

    /**
     * PATCH /api/logs/{id}/reject — Supervisor rejects log with comment.
     */
    #[Route('/{id}/reject', name: 'api_logs_reject', methods: ['PATCH'])]
    public function reject(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!in_array($user->getRole(), [User::ROLE_SUPERVISOR, User::ROLE_COORDINATOR], true)) {
            return $this->json(['error' => 'Only supervisors and coordinators can reject logs.'], Response::HTTP_FORBIDDEN);
        }

        $log = $this->dailyLogRepository->find($id);
        if (!$log) {
            return $this->json(['error' => 'Log not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($log->getStatus() !== DailyLog::STATUS_PENDING) {
            return $this->json(['error' => 'Only pending logs can be rejected.'], Response::HTTP_BAD_REQUEST);
        }

        $assignment = $log->getAssignment();
        if ($user->getRole() === User::ROLE_SUPERVISOR && $assignment->getSupervisor()->getId() !== $user->getId()) {
            return $this->json(['error' => 'You can only reject logs for your assigned students.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $comment = trim($data['comment'] ?? '');

        $log->setStatus(DailyLog::STATUS_REJECTED);
        if ($comment !== '') {
            $log->setSupervisorComment($comment);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Log rejected.',
            'log' => $log->toArray(),
        ]);
    }

    // ── Private helpers ──

    private function getStudentLogs(User $student): array
    {
        $assignments = $this->assignmentRepository->findByStudent($student);
        return $this->dailyLogRepository->findByAssignments($assignments);
    }

    private function getSupervisorLogs(User $supervisor): array
    {
        $assignments = $this->assignmentRepository->findBySupervisor($supervisor);
        return $this->dailyLogRepository->findByAssignments($assignments);
    }

    private function getAllLogs(): array
    {
        return $this->dailyLogRepository->findBy([], ['date' => 'DESC']);
    }

    private function canAccessLog(User $user, DailyLog $log): bool
    {
        $assignment = $log->getAssignment();
        return match ($user->getRole()) {
            User::ROLE_COORDINATOR => true,
            User::ROLE_SUPERVISOR => $assignment->getSupervisor()->getId() === $user->getId(),
            User::ROLE_STUDENT => $assignment->getStudent()->getId() === $user->getId(),
            default => false,
        };
    }
}
