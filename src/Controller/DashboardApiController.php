<?php

namespace App\Controller;

use App\Entity\OjtAssignment;
use App\Entity\User;
use App\Repository\DailyLogRepository;
use App\Repository\OjtAssignmentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OjtAssignmentRepository $assignmentRepository,
        private readonly DailyLogRepository $dailyLogRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * GET /api/dashboard — Aggregated statistics.
     */
    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->getRole() !== User::ROLE_COORDINATOR) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $totalStudents = count($this->userRepository->findByRole(User::ROLE_STUDENT));
        $activeAssignments = $this->assignmentRepository->countByStatus(OjtAssignment::STATUS_ACTIVE);
        $completedAssignments = $this->assignmentRepository->countByStatus(OjtAssignment::STATUS_COMPLETED);
        $pendingLogs = $this->dailyLogRepository->countByStatus('PENDING');
        $approvedLogs = $this->dailyLogRepository->countByStatus('APPROVED');
        $rejectedLogs = $this->dailyLogRepository->countByStatus('REJECTED');
        $averageClarity = $this->dailyLogRepository->getAverageClarityScore();
        $skillTagFrequency = $this->dailyLogRepository->getAllSkillTags();
        $averageCompletion = $this->assignmentRepository->getAverageCompletionPercentage();

        return $this->json([
            'totalStudents' => $totalStudents,
            'activeAssignments' => $activeAssignments,
            'completedAssignments' => $completedAssignments,
            'pendingLogs' => $pendingLogs,
            'approvedLogs' => $approvedLogs,
            'rejectedLogs' => $rejectedLogs,
            'averageCompletionPercentage' => $averageCompletion,
            'averageClarityScore' => $averageClarity,
            'skillTagFrequency' => $skillTagFrequency,
        ]);
    }

    /**
     * GET /api/logs — List daily logs filtered by user role.
     */
    #[Route('/api/logs', name: 'api_logs_list', methods: ['GET'])]
    public function listLogs(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $logs = match ($user->getRole()) {
            User::ROLE_STUDENT => $this->dailyLogRepository->findByStudent($user),
            User::ROLE_SUPERVISOR => $this->dailyLogRepository->findBySupervisor($user),
            User::ROLE_COORDINATOR => $this->dailyLogRepository->findAll(),
            default => [],
        };

        // Convert logs to array format for JSON response
        $logArray = array_map(fn($log) => [
            'id' => $log->getId(),
            'date' => $log->getDate()?->format('Y-m-d'),
            'status' => $log->getStatus(),
            'studentId' => $log->getAssignment()?->getStudent()?->getId(),
            'studentName' => $log->getAssignment()?->getStudent()?->getEmail(),
            'description' => $log->getContent(),
            'clarityScore' => $log->getClarityScore(),
            'tasksPerformed' => $log->getContent(), // Using content as tasks
            'skillsTrained' => $log->getSkillTags(),
            'supervisorComment' => $log->getSupervisorComment(),
            'hoursWorked' => $log->getHoursWorked(),
        ], $logs);

        return $this->json($logArray);
    }


    /**
     * GET /api/assignments — List assignments filtered by user role.
     */
    #[Route('/api/assignments', name: 'api_assignments_list', methods: ['GET'])]
    public function listAssignments(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $assignments = match ($user->getRole()) {
            User::ROLE_STUDENT => $this->assignmentRepository->findByStudent($user),
            User::ROLE_SUPERVISOR => $this->assignmentRepository->findBySupervisor($user),
            User::ROLE_COORDINATOR => $this->assignmentRepository->findAll(),
            default => [],
        };

        return $this->json([
            'assignments' => array_map(fn(OjtAssignment $a) => $a->toArray(), $assignments),
        ]);
    }

    /**
     * POST /api/assignments — Coordinator creates a new assignment.
     */
    #[Route('/api/assignments', name: 'api_assignments_create', methods: ['POST'])]
    public function createAssignment(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->getRole() !== User::ROLE_COORDINATOR) {
            return $this->json(['error' => 'Only coordinators can create assignments.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        $studentId = $data['student_id'] ?? null;
        $supervisorId = $data['supervisor_id'] ?? null;
        $companyName = trim($data['company_name'] ?? '');
        $requiredHours = (int) ($data['required_hours'] ?? 0);
        $startDateStr = $data['start_date'] ?? null;
        $endDateStr = $data['end_date'] ?? null;

        if (!$studentId || !$supervisorId || $companyName === '' || $requiredHours <= 0 || !$startDateStr) {
            return $this->json([
                'error' => 'student_id, supervisor_id, company_name, required_hours, and start_date are required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $student = $this->userRepository->find($studentId);
        if (!$student || $student->getRole() !== User::ROLE_STUDENT) {
            return $this->json(['error' => 'Student not found or user is not a student.'], Response::HTTP_NOT_FOUND);
        }

        $supervisor = $this->userRepository->find($supervisorId);
        if (!$supervisor || $supervisor->getRole() !== User::ROLE_SUPERVISOR) {
            return $this->json(['error' => 'Supervisor not found or user is not a supervisor.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $startDate = new \DateTime($startDateStr);
        } catch (\Exception) {
            return $this->json(['error' => 'Invalid start_date format.'], Response::HTTP_BAD_REQUEST);
        }

        $endDate = null;
        if ($endDateStr) {
            try {
                $endDate = new \DateTime($endDateStr);
            } catch (\Exception) {
                return $this->json(['error' => 'Invalid end_date format.'], Response::HTTP_BAD_REQUEST);
            }
        }

        $assignment = new OjtAssignment();
        $assignment->setStudent($student);
        $assignment->setSupervisor($supervisor);
        $assignment->setCompanyName($companyName);
        $assignment->setRequiredHours($requiredHours);
        $assignment->setStartDate($startDate);
        $assignment->setEndDate($endDate);

        $this->entityManager->persist($assignment);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Assignment created successfully.',
            'assignment' => $assignment->toArray(),
        ], Response::HTTP_CREATED);
    }
}
