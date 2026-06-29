<?php

namespace App\DataFixtures;

use App\Entity\DailyLog;
use App\Entity\OjtAssignment;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // ── Create Users ──

        // 1 Coordinator
        $coordinator = $this->createUser($manager, 'OJT Coordinator', 'coordinator@internsync.ai', User::ROLE_COORDINATOR);

        // 2 Supervisors
        $supervisor1 = $this->createUser($manager, 'Maria Santos', 'supervisor1@internsync.ai', User::ROLE_SUPERVISOR);
        $supervisor2 = $this->createUser($manager, 'Carlos Reyes', 'supervisor2@internsync.ai', User::ROLE_SUPERVISOR);

        // 5 Students
        $student1 = $this->createUser($manager, 'Juan Dela Cruz', 'student1@internsync.ai', User::ROLE_STUDENT);
        $student2 = $this->createUser($manager, 'Ana Garcia', 'student2@internsync.ai', User::ROLE_STUDENT);
        $student3 = $this->createUser($manager, 'Mark Villanueva', 'student3@internsync.ai', User::ROLE_STUDENT);
        $student4 = $this->createUser($manager, 'Rose Mendoza', 'student4@internsync.ai', User::ROLE_STUDENT);
        $student5 = $this->createUser($manager, 'Luis Bautista', 'student5@internsync.ai', User::ROLE_STUDENT);

        $manager->flush();

        // ── Create OJT Assignments ──

        $assignment1 = $this->createAssignment(
            $manager,
            $student1,
            $supervisor1,
            'Accenture Philippines',
            480,
            new \DateTime('2026-01-13'),
        );

        $assignment2 = $this->createAssignment(
            $manager,
            $student2,
            $supervisor1,
            'Globe Telecom Inc.',
            600,
            new \DateTime('2026-02-03'),
        );

        $assignment3 = $this->createAssignment(
            $manager,
            $student3,
            $supervisor2,
            'Jollibee Foods Corporation',
            480,
            new \DateTime('2026-01-20'),
        );

        $manager->flush();

        // ── Create Daily Logs ──

        $logEntries = [
            // Assignment 1 - Student 1 (Juan) - English entries
            [
                'assignment' => $assignment1,
                'date' => '2026-01-13',
                'content' => 'Today was my first day at Accenture Philippines. I attended the company orientation and was introduced to the development team. I set up my development environment including VS Code, Git, and Node.js. My supervisor explained the agile methodology they follow and I was given access to the project management board on Jira.',
                'hours' => 8,
                'status' => DailyLog::STATUS_APPROVED,
            ],
            [
                'assignment' => $assignment1,
                'date' => '2026-01-14',
                'content' => 'Started learning the codebase of the internal HR management system. The project uses React for the frontend and Laravel for the backend API. I reviewed the database schema and understood the relationships between employees, departments, and leave management modules. Attended a daily standup meeting for the first time.',
                'hours' => 8,
                'status' => DailyLog::STATUS_APPROVED,
            ],
            [
                'assignment' => $assignment1,
                'date' => '2026-01-15',
                'content' => 'Fixed a minor CSS bug on the employee profile page where the avatar image was not properly centered on mobile devices. Submitted my first pull request and it was approved after one round of code review. I also started reading the API documentation for the leave management endpoint.',
                'hours' => 7.5,
                'status' => DailyLog::STATUS_PENDING,
            ],
            // Assignment 1 - Mixed Filipino-English entries
            [
                'assignment' => $assignment1,
                'date' => '2026-01-16',
                'content' => 'Nag-attend ako ng training session about unit testing gamit ang PHPUnit. Na-realize ko na ang importance ng automated testing para sa code quality. Gumawa rin ako ng dalawang test cases para sa leave balance computation. Medyo challenging pero naintindihan ko na ang basic assertions at mock objects.',
                'hours' => 8,
                'status' => DailyLog::STATUS_PENDING,
            ],
            // Assignment 2 - Student 2 (Ana) - English entries
            [
                'assignment' => $assignment2,
                'date' => '2026-02-03',
                'content' => 'First day at Globe Telecom. Completed the onboarding process including security clearance and NDA signing. Was assigned to the Network Operations Center (NOC) team. Got a tour of the data center and learned about the monitoring tools they use like Grafana and Nagios.',
                'hours' => 8,
                'status' => DailyLog::STATUS_APPROVED,
            ],
            [
                'assignment' => $assignment2,
                'date' => '2026-02-04',
                'content' => 'Learned how to monitor network traffic using the company\'s proprietary dashboard. Observed the team handle a minor service disruption in Visayas region. Documented the incident response procedure as part of my learning assignment. My supervisor reviewed my documentation and gave positive feedback.',
                'hours' => 9,
                'status' => DailyLog::STATUS_APPROVED,
            ],
            // Assignment 2 - Mixed Filipino-English
            [
                'assignment' => $assignment2,
                'date' => '2026-02-05',
                'content' => 'Nag-assist sa pag-troubleshoot ng network connectivity issue sa isang corporate client. Natutunan ko yung basic network diagnostics tulad ng traceroute, ping tests, at DNS lookup. Na-resolve din yung issue, na related sa misconfigured VLAN settings. Happy ako kasi first time ko ma-experience ang real-world networking problem.',
                'hours' => 8,
                'status' => DailyLog::STATUS_PENDING,
            ],
            // Assignment 3 - Student 3 (Mark)
            [
                'assignment' => $assignment3,
                'date' => '2026-01-20',
                'content' => 'Started my OJT at Jollibee Foods Corporation in the IT department. The team primarily handles the point-of-sale system maintenance and inventory management software. I was given an overview of the tech stack: Java Spring Boot for backend services and Angular for the management portal.',
                'hours' => 8,
                'status' => DailyLog::STATUS_APPROVED,
            ],
            [
                'assignment' => $assignment3,
                'date' => '2026-01-21',
                'content' => 'Helped with database maintenance tasks today. Learned how to run SQL queries on the production reporting database (read-only access). Generated sales reports for the NCR region branches. Also attended a meeting about the upcoming POS system upgrade that will integrate online delivery orders.',
                'hours' => 8,
                'status' => DailyLog::STATUS_PENDING,
            ],
            [
                'assignment' => $assignment3,
                'date' => '2026-01-22',
                'content' => 'Nag-develop ng small utility script gamit Python para i-automate ang daily sales report generation na dati manual pa ginagawa. Sinubukan ko gumamit ng pandas library para sa data processing at openpyxl para sa Excel export. Pinresent ko sa team at gustong i-deploy sa staging server para ma-test.',
                'hours' => 8,
                'status' => DailyLog::STATUS_REJECTED,
                'comment' => 'Good work on the script, but please add more details about the specific functions you implemented and the data validation steps. Resubmit with technical specifics.',
            ],
        ];

        foreach ($logEntries as $entry) {
            $log = new DailyLog();
            $log->setAssignment($entry['assignment']);
            $log->setDate(new \DateTime($entry['date']));
            $log->setContent($entry['content']);
            $log->setHoursWorked($entry['hours']);
            $log->setStatus($entry['status']);

            if (isset($entry['comment'])) {
                $log->setSupervisorComment($entry['comment']);
            }

            // Simulate AI feedback for some logs
            if (in_array($entry['status'], [DailyLog::STATUS_APPROVED, DailyLog::STATUS_PENDING], true)) {
                $feedback = $this->generateMockAiFeedback($entry['content']);
                $log->setAiFeedback($feedback);
                $log->setSkillTags($feedback['skill_tags']);
                $log->setClarityScore($feedback['clarity_score']);
            }

            $manager->persist($log);
        }

        // Update hours for approved logs
        $assignment1->setHoursCompleted(16); // 8 + 8 from two approved logs
        $assignment2->setHoursCompleted(17); // 8 + 9 from two approved logs
        $assignment3->setHoursCompleted(8);  // 8 from one approved log

        $manager->flush();
    }

    private function createUser(ObjectManager $manager, string $name, string $email, string $role): User
    {
        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setRole($role);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $user->setEmailVerifiedAt(new \DateTimeImmutable());
        $manager->persist($user);

        return $user;
    }

    private function createAssignment(
        ObjectManager $manager,
        User $student,
        User $supervisor,
        string $companyName,
        int $requiredHours,
        \DateTime $startDate,
    ): OjtAssignment {
        $assignment = new OjtAssignment();
        $assignment->setStudent($student);
        $assignment->setSupervisor($supervisor);
        $assignment->setCompanyName($companyName);
        $assignment->setRequiredHours($requiredHours);
        $assignment->setStartDate($startDate);

        $manager->persist($assignment);
        return $assignment;
    }

    /**
     * Generates mock AI feedback for fixture data.
     * In production, this comes from AiLogReviewerService → Gemini API.
     */
    private function generateMockAiFeedback(string $content): array
    {
        $allTags = [
            'communication', 'programming', 'teamwork', 'networking', 'database',
            'problem-solving', 'agile', 'testing', 'documentation', 'python',
            'javascript', 'sql', 'devops', 'troubleshooting', 'code-review',
            'data-analysis', 'automation', 'project-management',
        ];

        // Pick 2-4 random skill tags
        $shuffled = $allTags;
        shuffle($shuffled);
        $selectedTags = array_slice($shuffled, 0, random_int(2, 4));

        // Assign clarity score based on content length (simple heuristic for fixtures)
        $wordCount = str_word_count($content);
        $clarityScore = match (true) {
            $wordCount >= 60 => random_int(4, 5),
            $wordCount >= 30 => random_int(3, 4),
            default => random_int(2, 3),
        };

        $grammarSuggestion = $wordCount > 40
            ? ''
            : 'Consider expanding your log entry with more specific technical details about the tasks completed.';

        return [
            'grammar_suggestions' => $grammarSuggestion,
            'skill_tags' => $selectedTags,
            'clarity_score' => $clarityScore,
        ];
    }
}
