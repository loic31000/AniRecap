<?php

namespace App\Controller;

use App\Dto\ChapitreInput;
use App\Entity\Chapitre;
use App\Entity\User;
use App\Form\ChapitreType;
use App\Repository\ChapitreRepository;
use App\Repository\MangaRepository;
use App\Repository\SummaryRepository;
use App\Service\SynopsisImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ChapitreFormController extends AbstractController
{
    #[Route('/formulaires/mangas/{mangaId}/chapitres/ajouter', name: 'app_forms_chapitre_create', methods: ['GET', 'POST'], requirements: ['mangaId' => '\d+'])]
    public function create(
        int $mangaId,
        Request $request,
        MangaRepository $mangaRepository,
        ChapitreRepository $chapitreRepository,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $user = $this->requireUser();
        $manga = $mangaRepository->findOneOwnedPrivate($mangaId, $user);
        if ($manga === null) {
            throw $this->createNotFoundException();
        }

        $input = new ChapitreInput();
        $form = $this->createForm(ChapitreType::class, $input, ['validation_groups' => ['Default', 'create'], 'csrf_token_id' => 'chapitre_create_' . $manga->getId()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($chapitreRepository->numberExistsForManga($manga, $input->number)) {
                $form->get('number')->addError(new FormError('Ce numéro de chapitre existe déjà dans ce manga.'));
            } else {
                $filename = null;
                $chapitre = new Chapitre();

                try {
                    if (!$input->image instanceof UploadedFile) {
                        throw new \RuntimeException('La miniature est obligatoire.');
                    }
                    $filename = $imageUploader->store($input->image);
                    $coverUrl = $this->generateUrl('app_forms_synopsis_image', ['filename' => $filename]);
                    $entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($chapitre, $manga, $user, $input, $coverUrl): void {
                        $chapitre->setManga($manga)->setUser($user);
                        $this->applyInput($chapitre, $input, $coverUrl);
                        $entityManager->persist($chapitre);
                    });
                } catch (\Throwable) {
                    if ($filename !== null) {
                        $imageUploader->remove($filename);
                    }
                    $this->addFlash('error', 'Le chapitre n’a pas pu être enregistré. Veuillez réessayer.');

                    return $this->redirectToRoute('app_forms_chapitre_create', ['mangaId' => $manga->getId()]);
                }

                $this->addFlash('success', 'Le chapitre a été créé avec succès.');

                return $this->redirectToRoute('app_manga_show', ['id' => $manga->getId(), '_fragment' => 'chapitres']);
            }
        }

        return $this->render('forms/chapitre.html.twig', ['form' => $form, 'manga' => $manga, 'is_edit' => false, 'current_cover_url' => null]);
    }

    #[Route('/formulaires/chapitres/{id}/modifier', name: 'app_forms_chapitre_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        Request $request,
        ChapitreRepository $chapitreRepository,
        SummaryRepository $summaryRepository,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $user = $this->requireUser();
        $chapitre = $chapitreRepository->findOneOwned($id, $user);
        if ($chapitre === null || $chapitre->getManga() === null) {
            throw $this->createNotFoundException();
        }

        $manga = $chapitre->getManga();
        $input = $this->createInput($chapitre);
        $form = $this->createForm(ChapitreType::class, $input, ['is_edit' => true, 'csrf_token_id' => 'chapitre_edit_' . $chapitre->getId()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($chapitreRepository->numberExistsForManga($manga, $input->number, $chapitre->getId())) {
                $form->get('number')->addError(new FormError('Ce numéro de chapitre existe déjà dans ce manga.'));
            } else {
                $newFilename = null;
                $oldCoverUrl = $chapitre->getCoverChapitreUrl();
                $coverUrl = $oldCoverUrl;

                try {
                    if ($input->image instanceof UploadedFile) {
                        $newFilename = $imageUploader->store($input->image);
                        $coverUrl = $this->generateUrl('app_forms_synopsis_image', ['filename' => $newFilename]);
                    }
                    $entityManager->wrapInTransaction(function () use ($chapitre, $manga, $user, $input, $coverUrl, $summaryRepository): void {
                        $chapitre->setManga($manga)->setUser($user);
                        $this->applyInput($chapitre, $input, $coverUrl);
                        $summaryRepository->synchronizeOwnedForParent(
                            'chapitre',
                            $chapitre,
                            $user,
                            $input->description,
                            spoilerLevel: $input->spoilerLevel->value,
                        );
                    });
                } catch (\Throwable) {
                    if ($newFilename !== null) {
                        $imageUploader->remove($newFilename);
                    }
                    $this->addFlash('error', 'Les modifications n’ont pas pu être enregistrées. Veuillez réessayer.');

                    return $this->redirectToRoute('app_forms_chapitre_edit', ['id' => $chapitre->getId()]);
                }

                if ($newFilename !== null && $oldCoverUrl !== null) {
                    $imageUploader->remove(basename((string) parse_url($oldCoverUrl, PHP_URL_PATH)));
                }
                $this->addFlash('success', 'Le chapitre a été modifié avec succès.');

                return $this->redirectToRoute('app_manga_show', ['id' => $manga->getId(), '_fragment' => 'chapitres']);
            }
        }

        return $this->render('forms/chapitre.html.twig', ['form' => $form, 'manga' => $manga, 'is_edit' => true, 'current_cover_url' => $chapitre->getCoverChapitreUrl()]);
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function applyInput(Chapitre $chapitre, ChapitreInput $input, ?string $coverUrl): void
    {
        $chapitre->setTitle($input->title)->setNumber($input->number)->setSynopsis($input->description)->setCoverChapitreUrl($coverUrl)
            ->setType($input->type)->setStatus($input->status)->setAuthor($input->author)->setChapitreDate($input->releaseYear)->setSpoilerLevel($input->spoilerLevel);

        foreach ($chapitre->getCategorie()->toArray() as $category) {
            if (!in_array($category, $input->categories, true)) {
                $chapitre->removeCategorie($category);
            }
        }
        foreach ($input->categories as $category) {
            $chapitre->addCategorie($category);
        }
    }

    private function createInput(Chapitre $chapitre): ChapitreInput
    {
        $input = new ChapitreInput();
        $input->title = $chapitre->getTitle();
        $input->number = $chapitre->getNumber();
        $input->description = $chapitre->getSynopsis();
        $input->type = $chapitre->getType();
        $input->status = $chapitre->getStatus();
        $input->author = $chapitre->getAuthor();
        $input->releaseYear = $chapitre->getChapitreDate();
        $input->spoilerLevel = $chapitre->getSpoilerLevel();
        $input->categories = $chapitre->getCategorie()->toArray();

        return $input;
    }
}
