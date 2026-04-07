<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RedirectController extends AbstractController
{
    #[Route('/', name: 'app_redirect_to_api_doc')]
    public function index(): Response
    {
        return $this->redirectToRoute('app.swagger_ui');
    }
}
