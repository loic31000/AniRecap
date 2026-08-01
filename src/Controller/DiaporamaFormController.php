<?php

namespace App\Controller;

use App\Dto\DiaporamaInput;
use App\Entity\Diaporama;
use App\Entity\User;
use App\Form\DiaporamaType;
use App\Service\SynopsisImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class DiaporamaFormController extends AbstractController
{
    #[Route('/formulaires/diaporamas/ajouter', name: 'app_diaporama_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $user = $this->requireUser();
        $input = new DiaporamaInput();
        $form = $this->createForm(DiaporamaType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filename = null;

            try {
                if (!$input->image instanceof UploadedFile) {
                    throw new \RuntimeException('La miniature est obligatoire.');
                }

                $filename = $imageUploader->store($input->image);
                $diaporama = (new Diaporama())
                    ->setTitle($input->title)
                    ->setContent($input->content)
                    ->setSourceType($input->sourceType)
                    ->setCoverImageFilename($filename)
                    ->setUser($user);

                foreach ($input->categories as $category) {
                    $diaporama->addCategorie($category);
                }

                $entityManager->wrapInTransaction(
                    static function (EntityManagerInterface $entityManager) use ($diaporama): void {
                        $entityManager->persist($diaporama);
                    },
                );
            } catch (\Throwable) {
                if ($filename !== null) {
                    $imageUploader->remove($filename);
                }

                $this->addFlash('error', 'Le diaporama n’a pas pu être créé. Veuillez réessayer.');

                return $this->redirectToRoute('app_diaporama_create', array_filter([
                    'return_to' => $this->safeReturnTo($request),
                ]));
            }

            $this->addFlash('success', 'Le diaporama a été créé avec succès.');

            return $this->redirectToRoute(
                $input->sourceType === Diaporama::SOURCE_ANIME
                    ? 'app_diaporama_scene_anime_create'
                    : 'app_diaporama_scene_manga_create',
                array_filter([
                    'id' => $diaporama->getId(),
                    'return_to' => $this->safeReturnTo($request),
                ]),
            );
        }

        return $this->render('forms/diaporama.html.twig', ['form' => $form]);
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function safeReturnTo(Request $request): ?string
    {
        $returnTo = (string) $request->query->get('return_to', '');

        return str_starts_with($returnTo, '/') && !str_starts_with($returnTo, '//') ? $returnTo : null;
    }
}
