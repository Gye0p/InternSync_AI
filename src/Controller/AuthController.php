<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $mailerFrom,
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

        // Public registration is student-only. Staff accounts must be provisioned by an admin/fixture.
        if ($role !== User::ROLE_STUDENT) {
            return $this->json(['error' => 'Only student self-registration is allowed.'], Response::HTTP_FORBIDDEN);
        }

        // Check for existing user
        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return $this->json(['error' => 'Email is already registered.'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setRole(User::ROLE_STUDENT);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setEmailVerificationToken(bin2hex(random_bytes(32)));
        $user->setEmailVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));

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

        $emailSent = $this->sendVerificationEmail($user);

        return $this->json([
            'message' => $emailSent
                ? 'User registered successfully. Please verify your email before signing in.'
                : 'User registered successfully, but the verification email could not be sent. Please contact the coordinator.',
            'user' => $user->toArray(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/verify-email', name: 'api_verify_email', methods: ['GET'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $token = trim((string) $request->query->get('token', ''));
        if ($token === '') {
            return $this->json(['error' => 'Verification token is required.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'emailVerificationToken' => $token,
        ]);

        if (!$user) {
            return $this->json(['error' => 'Invalid verification token.'], Response::HTTP_BAD_REQUEST);
        }

        $expiresAt = $user->getEmailVerificationTokenExpiresAt();
        if ($expiresAt !== null && $expiresAt < new \DateTimeImmutable()) {
            return $this->json(['error' => 'Verification token has expired.'], Response::HTTP_BAD_REQUEST);
        }

        $user->setEmailVerifiedAt(new \DateTimeImmutable());
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationTokenExpiresAt(null);

        $this->entityManager->flush();

        return $this->json(['message' => 'Email verified successfully. You may now sign in.']);
    }

    #[Route('/api/resend-verification', name: 'api_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email === '') {
            return $this->json(['error' => 'Email is required.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return $this->json(['error' => 'No account found for that email.'], Response::HTTP_NOT_FOUND);
        }

        if ($user->isEmailVerified()) {
            return $this->json(['message' => 'This email is already verified. You may sign in.']);
        }

        $user->setEmailVerificationToken(bin2hex(random_bytes(32)));
        $user->setEmailVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));
        $this->entityManager->flush();

        if (!$this->sendVerificationEmail($user)) {
            return $this->json([
                'error' => 'Verification email could not be sent. Please try again later.',
            ], Response::HTTP_BAD_GATEWAY);
        }

        return $this->json(['message' => 'Verification email sent. Please check your inbox or spam folder.']);
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

    private function sendVerificationEmail(User $user): bool
    {
        $token = $user->getEmailVerificationToken();
        if (!$token) {
            return false;
        }

        $verificationUrl = $this->generateUrl(
            'api_verify_email',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFrom, 'InternSync AI'))
            ->to(new Address($user->getEmail(), $user->getName() ?? $user->getEmail()))
            ->subject('Verify your InternSync AI account')
            ->htmlTemplate('auth/verification_email.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
            ]);

        try {
            $this->mailer->send($email);

            return true;
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Registration verification email could not be sent.', [
                'userId' => $user->getId(),
                'email' => $user->getEmail(),
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
