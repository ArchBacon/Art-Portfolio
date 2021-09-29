<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Message;
use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function index(Request $request): Response
    {
        $message = new Message();
        $form = $this->createForm(ContactType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($message);
            $entityManager->flush();

            return $this->redirectToRoute('contact.thankyou');
        }

        return $this->renderForm('contact/index.html.twig', [
            'contact_form' => $form,
        ]);
    }

    #[Route('/contact/thankyou', name: 'contact.thankyou')]
    public function thankyou(Request $request): Response
    {
        return $this->renderForm('contact/thankyou.html.twig');
    }
}
