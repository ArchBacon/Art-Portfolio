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
        return $this->render('admin/index.html.twig');
    }

    /** @link https://www.plupload.com/docs/v2/Chunking */
    #[Route('/upload', name: 'upload')]
    public function upload(Request $request): Response
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('file');
        $fileName = $request->get('name');
        $chunk = $request->get('chunk');
        $chunks = $request->get('chunks');
        $upload_dir = $this->getParameter('upload_dir');
        $gallery = $this->getParameter('gallery');

        $tmpFile = fopen($upload_dir . $fileName . '.blob_' . $chunk, 'wb');
        if ($tmpFile === false) {
            // TODO: Something went wrong. Couln't open or create file.
        }

        fwrite(realpath($tmpFile), fopen($file->getRealPath(), 'rb'));
        fclose($tmpFile);

        return new Response();

        rename($file->getRealPath(), $upload_dir . $fileName . '.blob.' . $chunk);

        /** @var UploadedFile $file */
        foreach ($request->files as $file) {
            $out = fopen("{$upload_dir}{$request->get('name')}.part", $chunk == 0 ? 'wb' : 'ab');
            if ($out !== false) {
                $in = fopen($file->getRealPath(), 'rb');
                if ($in !== false) {
                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }
                } else {
                    die('{"OK": 0, "info": "Failed to open input stream."}');
                }

                fclose($in);
                fclose($out);

                unlink($file->getRealPath());
            } else {
                die('{"OK": 0, "info": "Failed to open output stream."}');
            }

            if ($chunk === $chunks - 1) {
                rename(
                    "{$upload_dir}{$request->get('name')}.part",
                    $upload_dir . $request->get('name'));
            }
        }

        return new Response();
    }
}
