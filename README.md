# InternSync_AI

InternSync_AI is a Symfony-based internship management system for coordinating students, supervisors, and coordinators during OJT. It combines JWT-backed authentication, role-based dashboards, daily log submission and approval, AI-assisted log review, and automated certificate generation into one workflow.

## What this project does

- Lets users register and sign in with JWT authentication.
- Routes users to role-specific areas for students, supervisors, and coordinators.
- Supports daily OJT log submission, review, approval, and rejection.
- Uses Gemini-powered AI feedback to suggest grammar improvements, skill tags, and clarity scores for log entries.
- Generates completion certificates as PDF files when an assignment is finished.
- Provides fixtures and seed data for sample users, assignments, and logs.

## Main Tech Stack

- PHP 8.4
- Symfony 8.1
- Doctrine ORM and Migrations
- Lexik JWT Authentication Bundle
- Twig templates and Stimulus
- Dompdf for PDF certificate generation
- Google Gemini API for AI log review

## Key Areas

- Authentication and role routing: [src/Controller/AuthController.php](src/Controller/AuthController.php), [src/Controller/PageController.php](src/Controller/PageController.php)
- API and dashboard logic: [src/Controller/DashboardApiController.php](src/Controller/DashboardApiController.php)
- AI review service: [src/Service/AiLogReviewerService.php](src/Service/AiLogReviewerService.php)
- Certificate generation: [src/Service/CertificateGeneratorService.php](src/Service/CertificateGeneratorService.php)
- Demo seed data: [src/DataFixtures/AppFixtures.php](src/DataFixtures/AppFixtures.php)

## Getting Started

1. Install dependencies.
2. Configure your environment in `.env` or `.env.local`.
3. Set up the database and run migrations.
4. Generate the JWT keys if they are not already present.
5. Start the Symfony app and open the login page.

Example local commands:

```bash
composer install
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
symfony server:start
```

## Notes

- AI feedback is optional and will be skipped if the Gemini API key is missing.
- Certificates are written to `var/certificates/`.
- The repository includes sample dashboards for student, supervisor, and coordinator roles.