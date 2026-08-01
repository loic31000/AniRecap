<?php

namespace App\Controller;

use App\Dto\TomeInput;
use App\Entity\Tome;
use App\Entity\User;
use App\Form\TomeType;
use App\Repository\MangaRepository;
use App\Repository\TomeRepository;
use App\Repository\SummaryRepository;
use App\Service\SynopsisImageUploader;
use App\Service\OwnedContentDeletionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class TomeFormController extends AbstractController
{
    #[Route('/formulaires/mangas/{mangaId}/tomes/ajouter', name: 'app_forms_tome_create', methods: ['GET', 'POST'], requirements: ['mangaId' => '\d+'])]
    public function create(
        int $mangaId,
        Request $request,
        MangaRepository $mangaRepository,
        TomeRepository $tomeRepository,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
        SummaryRepository $summaryRepository,
    ): Response {
        $user = $this->requireUser();
        $manga = $mangaRepository->findOneOwnedPrivate($mangaId, $user);
        if ($manga === null) {
            throw $this->createNotFoundException();
        }

        $input = new TomeInput();
        $form = $this->createForm(TomeType::class, $input, [
            'validation_groups' => ['Default', 'create'],
            'csrf_token_id' => 'tome_create_' . $manga->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($tomeRepository->numberExistsForManga($manga, $input->number)) {
                $form->get('number')->addError(new FormError('Ce numéro de tome existe déjà dans ce manga.'));
            } else {
                $filename = null;
                $tome = new Tome();

                try {
                    if (!$input->image instanceof UploadedFile) {
                        throw new \RuntimeException('La miniature est obligatoire.');
                    }

                    $filename = $imageUploader->store($input->image);
                    $coverUrl = $this->generateUrl('app_forms_synopsis_image', ['filename' => $filename]);
                    $entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($tome, $manga, $user, $input, $coverUrl, $summaryRepository): void {
                        $tome->setManga($manga)->setUser($user);
                        $this->applyInput($tome, $input, $coverUrl);
                        $entityManager->persist($tome);
                        $summaryRepository->synchronizeOrCreateChild('tome', $tome, $user, $input->title, $input->description, $input->spoilerLevel);
                    });
                } catch (\Throwable) {
                    if ($filename !== null) {
                        $imageUploader->remove($filename);
                    }
                    $this->addFlash('error', 'Le tome n’a pas pu être enregistré. Veuillez réessayer.');

                    return $this->redirectToRoute('app_forms_tome_create', ['mangaId' => $manga->getId()]);
                }

                $this->addFlash('success', 'Le tome a été créé avec succès.');

                return $this->redirectToRoute('app_manga_show', ['id' => $manga->getId(), '_fragment' => 'tomes']);
            }
        }

        return $this->render('forms/tome.html.twig', ['form' => $form, 'manga' => $manga, 'is_edit' => false, 'current_cover_url' => null]);
    }

    #[Route('/formulaires/tomes/{id}/modifier', name: 'app_forms_tome_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        Request $request,
        TomeRepository $tomeRepository,
        SummaryRepository $summaryRepository,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $user = $this->requireUser();
        $tome = $tomeRepository->findOneOwned($id, $user);
        if ($tome === null || $tome->getManga() === null) {
            throw $this->createNotFoundException();
        }

        $manga = $tome->getManga();
        $input = $this->createInput($tome);
        $form = $this->createForm(TomeType::class, $input, ['is_edit' => true, 'csrf_token_id' => 'tome_edit_' . $tome->getId()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($tomeRepository->numberExistsForManga($manga, $input->number, $tome->getId())) {
                $form->get('number')->addError(new FormError('Ce numéro de tome existe déjà dans ce manga.'));
            } else {
                $newFilename = null;
                $oldCoverUrl = $tome->getCoverTomeUrl();
                $coverUrl = $oldCoverUrl;

                try {
                    if ($input->image instanceof UploadedFile) {
                        $newFilename = $imageUploader->store($input->image);
                        $coverUrl = $this->generateUrl('app_forms_synopsis_image', ['filename' => $newFilename]);
                    }
                    $entityManager->wrapInTransaction(function () use ($tome, $manga, $user, $input, $coverUrl, $summaryRepository): void {
                        $tome->setManga($manga)->setUser($user);
                        $this->applyInput($tome, $input, $coverUrl);
                        $summaryRepository->synchronizeOrCreateChild('tome', $tome, $user, $input->title, $input->description, $input->spoilerLevel);
                    });
                } catch (\Throwable) {
                    if ($newFilename !== null) {
                        $imageUploader->remove($newFilename);
                    }
                    $this->addFlash('error', 'Les modifications n’ont pas pu être enregistrées. Veuillez réessayer.');

                    return $this->redirectToRoute('app_forms_tome_edit', ['id' => $tome->getId()]);
                }

                if ($newFilename !== null && $oldCoverUrl !== null) {
                    $imageUploader->remove(basename((string) parse_url($oldCoverUrl, PHP_URL_PATH)));
                }
                $this->addFlash('success', 'Le tome a été modifié avec succès.');

                return $this->redirectToRoute('app_manga_show', ['id' => $manga->getId(), '_fragment' => 'tomes']);
            }
        }

        return $this->render('forms/tome.html.twig', ['form' => $form, 'manga' => $manga, 'is_edit' => true, 'current_cover_url' => $tome->getCoverTomeUrl()]);
    }

    #[Route('/formulaires/tomes/{id}/supprimer', name: 'app_forms_tome_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request, TomeRepository $tomeRepository, OwnedContentDeletionService $deletionService): Response
    {
        $user = $this->requireUser();
        $tome = $tomeRepository->findOneOwned($id, $user);
        if ($tome === null || $tome->getManga() === null) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('tome_delete_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $mangaId = $tome->getManga()->getId();
        $deletionService->deleteTome($tome);
        $this->addFlash('success', 'Le tome a été supprimé.');

        return $this->redirectToRoute('app_manga_show', ['id' => $mangaId, '_fragment' => 'tomes']);
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function applyInput(Tome $tome, TomeInput $input, ?string $coverUrl): void
    {
        $tome->setTitle($input->title)->setNumber($input->number)->setSynopsis($input->description)->setCoverTomeUrl($coverUrl)
            ->setType($input->type)->setStatus($input->status)->setAuthor($input->author)->setTomeDate($input->releaseYear)->setSpoilerLevel($input->spoilerLevel);

        foreach ($tome->getCategorie()->toArray() as $category) {
            if (!in_array($category, $input->categories, true)) {
                $tome->removeCategorie($category);
            }
        }
        foreach ($input->categories as $category) {
            $tome->addCategorie($category);
        }
    }

    private function createInput(Tome $tome): TomeInput
    {
        $input = new TomeInput();
        $input->title = $tome->getTitle();
        $input->number = $tome->getNumber();
        $input->description = $tome->getSynopsis();
        $input->type = $tome->getType();
        $input->status = $tome->getStatus();
        $input->author = $tome->getAuthor();
        $input->releaseYear = $tome->getTomeDate();
        $input->spoilerLevel = $tome->getSpoilerLevel();
        $input->categories = $tome->getCategorie()->toArray();

        return $input;
    }
}
