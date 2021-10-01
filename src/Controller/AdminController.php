<?php

declare(strict_types=1);

namespace App\Controller;

use Ramsey\Uuid\Uuid;
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
        return $this->render('admin/index.html.twig');
    }

    /** @link https://www.plupload.com/docs/v2/Chunking */
    #[Route('/upload', name: 'upload')]
    public function upload(Request $request): Response
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('file');
        $file_name = $request->get('name');
        $chunk = $request->get('chunk');
        $chunks = $request->get('chunks');
        $upload_dir = $this->getParameter('upload_dir');
        $gallery_dir = $this->getParameter('gallery_dir');

        $tmpFile = fopen($upload_dir . $file_name . '.tmp', $chunk === 0 ? "wb" : 'ab');
        if ($tmpFile === false) {
            die('Something went wrong. Couln\'t open or create file.');
        }

        $blob = fopen($file->getRealPath(), 'rb');
        if ($blob === false) {
            die('Something went wrong. Couln\'t open file.');
        }

        while ($buff = fread($blob, 4096)) {
            fwrite($tmpFile, $buff);
        }

        fclose($tmpFile);
        fclose($blob);

        unlink($file->getRealPath());

        if ((int)$chunk === $chunks - 1) {

            rename(
                $upload_dir . $file_name . '.tmp',
                $gallery_dir . Uuid::uuid4() . '.' . preg_replace("#\?.*#", "", pathinfo($file_name, PATHINFO_EXTENSION))
            );
        }

        return new Response();
    }
}
