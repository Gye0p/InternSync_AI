<?php

namespace App\Service;

use App\Entity\Certificate;
use App\Entity\OjtAssignment;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class CertificateGeneratorService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Generates a PDF certificate for a completed OJT assignment.
     */
    public function generate(OjtAssignment $assignment): Certificate
    {
        // Render HTML from Twig template
        $html = $this->twig->render('certificate/certificate.html.twig', [
            'assignment' => $assignment,
            'student' => $assignment->getStudent(),
            'supervisor' => $assignment->getSupervisor(),
            'generatedAt' => new \DateTimeImmutable(),
        ]);

        // Configure DomPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Ensure output directory exists
        $outputDir = $this->projectDir . '/var/certificates';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        // Generate unique filename
        $filename = sprintf(
            'certificate_%d_%s.pdf',
            $assignment->getId(),
            (new \DateTimeImmutable())->format('Ymd_His')
        );
        $filePath = $outputDir . '/' . $filename;

        // Write PDF to disk
        file_put_contents($filePath, $dompdf->output());

        // Create Certificate entity
        $certificate = new Certificate();
        $certificate->setAssignment($assignment);
        $certificate->setFilePath('var/certificates/' . $filename);
        $certificate->setGeneratedAt(new \DateTimeImmutable());

        $this->entityManager->persist($certificate);
        $this->entityManager->flush();

        return $certificate;
    }
}
