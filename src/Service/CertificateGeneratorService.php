<?php

namespace App\Service;

use App\Entity\Certificate;
use App\Entity\OjtAssignment;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Twig\Environment;

class CertificateGeneratorService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $projectDir,
        private readonly string $mailerFrom,
    ) {
    }

    /**
     * Generates a PDF certificate for a completed OJT assignment.
     */
    public function generate(OjtAssignment $assignment): Certificate
    {
        if ($assignment->getCertificate() !== null) {
            return $assignment->getCertificate();
        }

        // Persist first so the certificate has an ID for the verification footer
        $certificate = new Certificate();
        $certificate->setAssignment($assignment);
        $certificate->setGeneratedAt(new \DateTimeImmutable());
        $certificate->setFilePath('pending');

        $this->entityManager->persist($certificate);
        $this->entityManager->flush();

        $html = $this->twig->render('certificate/certificate.html.twig', [
            'assignment' => $assignment,
            'student' => $assignment->getStudent(),
            'supervisor' => $assignment->getSupervisor(),
            'certificate' => $certificate,
            'generatedAt' => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $outputDir = $this->projectDir . '/var/certificates';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $filename = sprintf(
            'certificate_%d_%s.pdf',
            $assignment->getId(),
            (new \DateTimeImmutable())->format('Ymd_His')
        );
        $filePath = $outputDir . '/' . $filename;

        file_put_contents($filePath, $dompdf->output());

        $certificate->setFilePath('var/certificates/' . $filename);
        $this->entityManager->flush();

        $this->emailCertificate($assignment, $filePath, $filename);

        return $certificate;
    }

    private function emailCertificate(OjtAssignment $assignment, string $filePath, string $filename): void
    {
        $student = $assignment->getStudent();
        if (!$student || !$student->getEmail()) {
            $this->logger->warning('Certificate email skipped because the assignment has no student email.', [
                'assignmentId' => $assignment->getId(),
            ]);

            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFrom, 'InternSync AI'))
            ->to(new Address($student->getEmail(), $student->getName() ?? $student->getEmail()))
            ->subject('Your OJT Completion Certificate')
            ->htmlTemplate('certificate/email.html.twig')
            ->context([
                'assignment' => $assignment,
                'student' => $student,
                'supervisor' => $assignment->getSupervisor(),
            ])
            ->attachFromPath($filePath, $filename, 'application/pdf');

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Certificate was generated, but the email could not be sent.', [
                'assignmentId' => $assignment->getId(),
                'studentEmail' => $student->getEmail(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
