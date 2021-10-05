<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Image;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\PcreException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Safe\{chmod, fclose, fopen, fwrite, glob, preg_replace, touch, unlink};

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    /**
     * @throws FilesystemException
     * @throws PcreException
     */
    #[Route('/upload', name: 'upload')]
    public function upload(Request $request): Response
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('file');
        $filename = $request->get('name');
        $chunk = $request->get('chunk');
        $chunks = $request->get('chunks');
        /** @var string $uploadDir */
        $uploadDir = $this->getParameter('upload_directory');
        /** @var string $galleryDir */
        $galleryDir = $this->getParameter('gallery_directory');

        // TODO: Slug filename
        $file->move($uploadDir, $chunk . '_' . md5($filename));

        if ((int)$chunk !== $chunks - 1) {
            return new Response();
        }

        $newFile = $galleryDir . '/' . uniqid('', false) . '.' . preg_replace('#\?.*#', '', pathinfo($filename, PATHINFO_EXTENSION));
        touch($newFile);
        chmod($newFile, 0777);

        $tmpFile = fopen($newFile, 'ab');
        foreach (glob($uploadDir . "/*_" . md5($filename)) as $filepath) {
            fwrite($tmpFile, \Safe\file_get_contents($filepath));
            unlink($filepath);
        }

        fclose($tmpFile);

        return new Response();

        /**
         * @link https://gist.github.com/philBrown/880506
         * @link https://stackoverflow.com/questions/27350770/crop-center-square-of-image-using-imagecopyresampled/27351634
         */

        $this->MakeThumb($gallery_dir . $unique_file_name, 200, 200, $gallery_dir . '200x200_' . $unique_file_name);
        $this->MakeThumb($gallery_dir . $unique_file_name, 400, 400, $gallery_dir . '400x400_' . $unique_file_name);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist(new Image($unique_file_name));
        $entityManager->flush();

        return new Response();
    }

    public function MakeThumb($thumb_target = '', $width = 60, $height = 60, $SetFileName = false, $quality = 8): void
    {
        $thumb_img = imagecreatefrompng($thumb_target);

        // size from
        list($w, $h) = getimagesize($thumb_target);

        if ($w > $h) {
            $new_height = $height;
            $new_width = floor($w * ($new_height / $h));
            $crop_x = ceil(($w - $h) / 2);
            $crop_y = 0;
        } else {
            $new_width = $width;
            $new_height = floor($h * ($new_width / $w));
            $crop_x = 0;
            $crop_y = ceil(($h - $w) / 2);
        }

        // I think this is where you are mainly going wrong
        $tmp_img = imagecreatetruecolor($width, $height);
        imagealphablending($tmp_img, false);
        imagesavealpha($tmp_img, true);
        imagecopyresampled($tmp_img, $thumb_img, 0, 0, (int)$crop_x, (int)$crop_y, (int)$new_width, (int)$new_height, $w, $h);

        if ($SetFileName == false) {
            header('Content-Type: image/png');
            imagepng($tmp_img);
        } else
            imagepng($tmp_img, $SetFileName, $quality);

        imagedestroy($tmp_img);
    }
}
