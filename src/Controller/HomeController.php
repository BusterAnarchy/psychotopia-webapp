<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function appHome(): Response
    {
        return $this->render('pages/home.html.twig', [
            'controller_name' => 'Home',
        ]);
    }

    #[Route('/faq', name: 'app_faq')]
    public function appFaq(): Response
    {
        return $this->render('pages/faq.html.twig', [
            'controller_name' => 'FAQ',
        ]);
    }
}
