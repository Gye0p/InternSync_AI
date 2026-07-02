<div align="center">

# 🎓 InternSync_AI

**Stop chasing paper logbooks. Let AI help run your OJT program.**

A Symfony-powered internship management system that keeps students, supervisors, and coordinators in sync — from daily logs to the final certificate.

[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-8.1-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![Doctrine](https://img.shields.io/badge/Doctrine-ORM-FC6A31?logo=doctrine&logoColor=white)](https://www.doctrine-project.org/)
[![JWT](https://img.shields.io/badge/Auth-Lexik%20JWT-blue)](https://github.com/lexik/LexikJWTAuthenticationBundle)
[![Gemini](https://img.shields.io/badge/AI-Google%20Gemini-4285F4?logo=googlegemini&logoColor=white)](https://ai.google.dev/)
[![License](https://img.shields.io/badge/license-Unspecified-lightgrey)]()

</div>

---

## 📌 What is InternSync_AI?

Running an On-the-Job Training (OJT) or internship program usually means juggling spreadsheets, email threads, and physical logbooks just to answer one question: *"Is this student actually progressing?"*

**InternSync_AI** replaces that mess with a single web app. Students submit daily logs, supervisors review and approve them, coordinators oversee the whole pipeline — and along the way, **Google Gemini quietly reviews each log entry**, suggesting grammar fixes, tagging relevant skills, and scoring clarity, so supervisors spend less time editing and more time evaluating. Once an assignment wraps up, the system auto-generates a completion certificate as a downloadable PDF.

Think of it as **one login, three roles, zero paperwork.**

---

## ✨ Features

| | |
|---|---|
| 🔐 **JWT Authentication** | Secure register/login flow backed by LexikJWTAuthenticationBundle |
| 🧭 **Role-Based Dashboards** | Students, supervisors, and coordinators each get a purpose-built view |
| 📝 **Daily Log Workflow** | Submit → review → approve/reject, all tracked in one place |
| 🤖 **AI-Assisted Review** | Gemini suggests grammar fixes, skill tags, and clarity scores per log |
| 📄 **Auto-Generated Certificates** | PDF completion certificates via Dompdf, no manual formatting needed |
| 🌱 **Ready-Made Seed Data** | Fixtures for sample users, assignments, and logs so you can demo instantly |

---

## 🧑‍🤝‍🧑 How the Roles Work

InternSync_AI is built around three perspectives on the same data:

- **🎓 Student** — logs in, writes daily OJT entries, sees AI feedback before submitting, and tracks approval status.
- **🧑‍🏫 Supervisor** — reviews incoming logs (with AI suggestions already attached), approves or rejects them, and monitors intern progress.
- **🗂️ Coordinator** — oversees the whole program: assignments, users, and overall completion, including certificate issuance.

Each role is routed to its own dashboard automatically after login — no separate apps, no manual permission juggling.

---

## 🛠️ Tech Stack

- **Backend:** PHP 8.4, Symfony 8.1
- **Database:** Doctrine ORM + Doctrine Migrations
- **Auth:** Lexik JWT Authentication Bundle
- **Frontend:** Twig templates + Stimulus
- **PDF Generation:** Dompdf
- **AI Layer:** Google Gemini API (log review, feedback, scoring)

---

## 🗺️ Where Things Live

| Area | Files |
|---|---|
| Auth & role routing | [`src/Controller/AuthController.php`](src/Controller/AuthController.php), [`src/Controller/PageController.php`](src/Controller/PageController.php) |
| API & dashboard logic | [`src/Controller/DashboardApiController.php`](src/Controller/DashboardApiController.php) |
| AI log review | [`src/Service/AiLogReviewerService.php`](src/Service/AiLogReviewerService.php) |
| Certificate generation | [`src/Service/CertificateGeneratorService.php`](src/Service/CertificateGeneratorService.php) |
| Demo seed data | [`src/DataFixtures/AppFixtures.php`](src/DataFixtures/AppFixtures.php) |

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.4+
- Composer
- A database supported by Doctrine (e.g. MySQL/PostgreSQL)
- Symfony CLI (optional, but makes local dev easier)
- A Google Gemini API key *(optional — AI review is skipped gracefully without one)*

### Setup

```bash
# 1. Install dependencies
composer install

# 2. Configure your environment
cp .env .env.local
# then edit .env.local with your DB credentials and (optionally) your Gemini API key

# 3. Set up the database
php bin/console doctrine:migrations:migrate

# 4. Load demo data (sample users, assignments, logs)
php bin/console doctrine:fixtures:load

# 5. Generate JWT keys (if not already present)
php bin/console lexik:jwt:generate-keypair

# 6. Run the app
symfony server:start
```

Then open the login page and sign in with one of the seeded demo accounts to explore the student, supervisor, and coordinator dashboards.

---

## 💡 Good to Know

- **No Gemini key? No problem.** AI feedback is entirely optional — the app functions normally without it, minus the smart suggestions.
- **Certificates** are generated as PDFs and saved to `var/certificates/`.
- **Fixtures included** — you don't need real data to try the app; sample dashboards come pre-loaded for all three roles.

---

## 🤝 Contributing

Issues, feature ideas, and pull requests are welcome. If you're extending this project, the AI review service and certificate generator are the two most self-contained places to start.

---

<div align="center">

Built to make OJT coordination a little less painful, one approved log at a time.

</div>
