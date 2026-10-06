<?php

namespace App\Controller;

use App\Entity\Figurine;
use App\Entity\User;
use App\Form\FigurineType;
use App\Repository\FigurineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/figurine', name: 'app_figurine_')]
class FigurineController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(FigurineRepository $repository): Response
    {
        return $this->render('figurine/index.html.twig', ['figurines' => $repository->findLatest(100)]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $figurine = new Figurine();
        $form = $this->createForm(FigurineType::class, $figurine, ['is_new' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user instanceof User) { throw $this->createAccessDeniedException(); }
            $figurine->setAuthor($user);
            $entityManager->persist($figurine);
            $entityManager->flush();
            $this->addFlash('success', 'La figurine a été créée avec succès.');
            return $this->redirectToRoute('app_figurine_show', ['id' => $figurine->getId()]);
        }
        return $this->render('figurine/create.html.twig', ['form' => $form]);
    }

    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(Figurine $figurine): Response
    {
        return $this->render('figurine/show.html.twig', ['figurine' => $figurine]);
    }

    #[Route('/{id<\d+>}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Figurine $figurine, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($figurine->getAuthor() !== $this->getUser()) { throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres figurines.'); }
        $form = $this->createForm(FigurineType::class, $figurine);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La figurine a été modifiée avec succès.');
            return $this->redirectToRoute('app_figurine_show', ['id' => $figurine->getId()]);
        }
        return $this->render('figurine/edit.html.twig', ['figurine' => $figurine, 'form' => $form]);
    }

    #[Route('/{id<\d+>}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Figurine $figurine, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($figurine->getAuthor() !== $this->getUser()) { throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres figurines.'); }
        if (!$this->isCsrfTokenValid('delete-figurine-'.$figurine->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        $entityManager->remove($figurine);
        $entityManager->flush();
        $this->addFlash('success', 'La figurine a été supprimée.');
        return $this->redirectToRoute('app_figurine_index');
    }
}
