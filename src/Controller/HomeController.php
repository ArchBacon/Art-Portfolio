<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ImageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'portfolio')]
    public function index(ImageRepository $imageRepository): Response
    {
        $images = $imageRepository->findPublicImages();

        return $this->render('home/index.html.twig', [
            'images' => $images,
        ]);
    }
}
