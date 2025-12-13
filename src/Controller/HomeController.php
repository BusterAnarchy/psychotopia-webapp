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
        return $this->render('pages/page_home.html.twig', [
            'controller_name' => 'Home',
        ]);
    }
}
