<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves page routes for the SPA-style frontend.
 * Each route checks for a JWT cookie and passes user context to Twig templates.
 * These are server-rendered page shells; actual data comes from the API endpoints.
 */
class PageController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('app_login');
    }

    #[Route('/login', name: 'app_login', methods: ['GET'])]
    public function login(Request $request): Response
    {
        $userData = $this->getUserDataFromCookie($request);
        if ($userData) {
            return $this->redirectToDashboard($userData['role'] ?? '');
        }

        return $this->render('auth/login.html.twig');
    }

    #[Route('/register', name: 'app_register', methods: ['GET'])]
    public function register(Request $request): Response
    {
        $userData = $this->getUserDataFromCookie($request);
        if ($userData) {
            return $this->redirectToDashboard($userData['role'] ?? '');
        }

        return $this->render('auth/register.html.twig');
    }

    #[Route('/student/dashboard', name: 'app_student_dashboard', methods: ['GET'])]
    public function studentDashboard(Request $request): Response
    {
        $userData = $this->getUserDataFromCookie($request);
        if (!$userData) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('student/dashboard.html.twig', [
            'app_user' => $userData,
        ]);
    }

    #[Route('/student/logs', name: 'app_student_logs', methods: ['GET'])]
    public function studentLogs(Request $request): Response
    {
        $userData = $this->getUserDataFromCookie($request);
        if (!$userData) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('student/logs.html.twig', [
            'app_user' => $userData,
        ]);
    }

    #[Route('/supervisor/dashboard', name: 'app_supervisor_dashboard', methods: ['GET'])]
    public function supervisorDashboard(Request $request): Response
    {
        $userData = $this->getUserDataFromCookie($request);
        if (!$userData) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('supervisor/dashboard.html.twig', [
            'app_user' => $userData,
        ]);
    }

    #[Route('/supervisor/approvals', name: 'app_supervisor_approvals', methods: ['GET'])]
    public function supervisorApprovals(Request $request): Response
    {
        $userData = $this->getUserDataFromCookie($request);
        if (!$userData) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('supervisor/approvals.html.twig', [
            'app_user' => $userData,
        ]);
    }

    #[Route('/coordinator/dashboard', name: 'app_coordinator_dashboard', methods: ['GET'])]
    public function coordinatorDashboard(Request $request): Response
    {
        $userData = $this->getUserDataFromCookie($request);
        if (!$userData) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('coordinator/dashboard.html.twig', [
            'app_user' => $userData,
        ]);
    }

    /**
     * Decodes the JWT token stored in a cookie to extract user data.
     * This is a lightweight decode for rendering purposes only — actual
     * authentication is handled by the API firewall with Lexik JWT.
     */
    private function getUserDataFromCookie(Request $request): ?array
    {
        $token = $request->cookies->get('jwt_token');
        if (!$token) {
            return null;
        }

        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            if (!is_array($payload)) {
                return null;
            }

            // Check token expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return null;
            }

            return [
                'email' => $payload['username'] ?? $payload['email'] ?? null,
                'role' => $this->extractRoleFromPayload($payload),
                'name' => $payload['name'] ?? null,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractRoleFromPayload(array $payload): ?string
    {
        $role = $payload['role'] ?? null;
        if (is_string($role) && $role !== '') {
            return str_starts_with($role, 'ROLE_') ? substr($role, 5) : $role;
        }

        $roles = $payload['roles'] ?? null;
        if (is_array($roles) && isset($roles[0]) && is_string($roles[0])) {
            return str_starts_with($roles[0], 'ROLE_') ? substr($roles[0], 5) : $roles[0];
        }

        return null;
    }

    private function redirectToDashboard(string $role): RedirectResponse
    {
        return match ($role) {
            'SUPERVISOR' => $this->redirectToRoute('app_supervisor_dashboard'),
            'COORDINATOR' => $this->redirectToRoute('app_coordinator_dashboard'),
            default => $this->redirectToRoute('app_student_dashboard'),
        };
    }
}
