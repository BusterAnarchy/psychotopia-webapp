<?php

namespace App\Controller;

use App\Service\RRunner;
use App\Service\RRunnerCached;
use DateTimeImmutable;
use IntlDateFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(private readonly RRunnerCached $runner)
    {
    }

    #[Route('/', name: 'app_home')]
    public function appHome(): Response
    {
        $results = $this->runner->run(
            RRunner::builder()
                ->addAnalysis('describe')
        );

        $lastUpdateRaw = $results['describe']['temporal_coverage']['last_sample_date'] ?? null;

        $dataSummary = [
            'samples_count' => $results['describe']['sample_count'] ?? 0,
            'molecules_count' => $results['describe']['molecules']['distinct_count'] ?? 0,
            'last_update' => $this->formatDateForDisplay($lastUpdateRaw),
        ];

        return $this->render('pages/page_home.html.twig', [
            'controller_name' => 'Home',
            'data_summary' => $dataSummary,
        ]);
    }

    private function formatDateForDisplay(?string $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        $dateTime = new DateTimeImmutable($date);
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'Europe/Paris', null, 'd MMMM y');
       
        return $formatter->format($dateTime);
    }
}
