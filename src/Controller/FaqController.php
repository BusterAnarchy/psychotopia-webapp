<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FaqController extends AbstractController
{
    #[Route('/faq', name: 'app_faq')]
    public function appFaq(): Response
    {
        return $this->render('pages/page_faq.html.twig', [
            'controller_name' => 'FAQ',
        ]);
    }
}
