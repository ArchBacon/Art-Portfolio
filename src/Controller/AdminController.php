<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/upload', name: 'upload')]
    public function upload(Request $request): Response
    {
        $upload_dir = $this->getParameter('upload_dir');

        // TODO: make chunked file upload

        /** @var UploadedFile $file */
        foreach ($request->files as $file) {
            move_uploaded_file($file->getRealPath(), $upload_dir . uniqid() . '.' . $file->getClientOriginalExtension());
        }

        return new Response();
    }
}
