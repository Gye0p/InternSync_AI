<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = strtoupper(trim($data['role'] ?? User::ROLE_STUDENT));

        // Validate required fields
        if ($name === '' || $email === '' || $password === '') {
            return $this->json(['error' => 'Name, email and password are required.'], Response::HTTP_BAD_REQUEST);
        }

        // Validate role
        $validRoles = [User::ROLE_STUDENT, User::ROLE_SUPERVISOR, User::ROLE_COORDINATOR];
        if (!in_array($role, $validRoles, true)) {
            return $this->json(['error' => 'Invalid role. Must be STUDENT, SUPERVISOR, or COORDINATOR.'], Response::HTTP_BAD_REQUEST);
        }

        // Check for existing user
        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return $this->json(['error' => 'Email is already registered.'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setRole($role);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        // Validate entity constraints
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'User registered successfully.',
            'user' => $user->toArray(),
        ], Response::HTTP_CREATED);
    }

    /**
     * Login is handled by LexikJWTAuthenticationBundle's json_login authenticator.
     * This route exists only so that the path is explicitly defined for documentation.
     * The security layer intercepts /api/login before this controller method executes.
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // This should never be reached — the JWT authenticator intercepts it.
        // If we land here, something is misconfigured.
        return $this->json(['error' => 'Authentication not configured properly.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(['user' => $user->toArray()]);
    }
}
