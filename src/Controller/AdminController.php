<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Image;
use App\Service\File;
use App\Service\FileUploader;
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

    /**
     * @throws \Safe\Exceptions\FilesystemException
     * @throws \Safe\Exceptions\PcreException
     */
    #[Route('/upload', name: 'upload')]
    public function upload(Request $request): Response
    {
        // create new file
        // Upload blob
        // merge blob with new file
        // if all uploaded, move new file to gallery

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
        // TODO: Cannot Move on null
        $file->move($uploadDir, $chunk . '_' . md5($filename));

        if ((int)$chunk !== $chunks - 1) {
            return new Response();
        }

        dd((int)$chunk, $chunks - 1);

        $newFile = $galleryDir . '/' . uniqid('', false) . '.' . \Safe\preg_replace('#\?.*#', '', pathinfo($filename, PATHINFO_EXTENSION));
        \Safe\touch($newFile);
        \Safe\chmod($newFile, 0777);

        $tmpFile = \Safe\fopen($newFile, 'ab');
        foreach (\Safe\glob("*_".md5($filename)) as $filepath) {
            \Safe\fwrite($tmpFile, \Safe\file_get_contents($filepath));
            \Safe\unlink($filepath);
        }

        \Safe\fclose($tmpFile);

        die;

        /** @var https://stackoverflow.com/questions/27457921/php-unable-to-create-file $tmpFile */
        $tmpFile = fopen($upload_dir . $file_name . '.tmp', $chunk === 0 ? 'wb' : 'ab');
        if ($tmpFile === false) {
            die('Something went wrong. Couln\'t open or create file.');
        }
        chmod($upload_dir . $file_name . '.tmp', 0777);


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
            $file_extension = preg_replace('#\?.*#', '', pathinfo($file_name, PATHINFO_EXTENSION));
            $unique_file_name = Uuid::uuid4() . '.' . $file_extension;

            $rename = rename(
                $upload_dir . $file_name . '.tmp',
                $gallery_dir . $unique_file_name
            );

            /**
             * @link https://gist.github.com/philBrown/880506
             * @link https://stackoverflow.com/questions/27350770/crop-center-square-of-image-using-imagecopyresampled/27351634
             */

            $this->MakeThumb($gallery_dir . $unique_file_name, 200, 200, $gallery_dir . '200x200_' . $unique_file_name);
            $this->MakeThumb($gallery_dir . $unique_file_name, 400, 400, $gallery_dir . '400x400_' . $unique_file_name);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist(new Image($unique_file_name));
            $entityManager->flush();
        }

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
