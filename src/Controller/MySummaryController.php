<?php

namespace App\Controller;

use App\Dto\DiaporamaEditInput;
use App\Dto\SummaryEditInput;
use App\Entity\Diaporama;
use App\Entity\Summary;
use App\Entity\User;
use App\Enum\SpoilerLevel;
use App\Form\DiaporamaEditType;
use App\Form\SummaryEditType;
use App\Repository\DiaporamaRepository;
use App\Repository\SlideRepository;
use App\Repository\SummaryRepository;
use App\Service\SynopsisImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class MySummaryController extends AbstractController
{
    #[Route('/mes-resumes', name: 'app_my_summaries', methods: ['GET'])]
    public function index(
        Request $request,
        SummaryRepository $summaryRepository,
        DiaporamaRepository $diaporamaRepository,
    ): Response {
        $user = $this->requireUser();
        $summaries = $summaryRepository->findByUser($user);
        $filterType = (string) $request->query->get('type', '');
        $filterId = $request->query->getInt('id');
        $isRootFilter = in_array($filterType, ['anime', 'manga'], true) && $filterId > 0;
        if ($isRootFilter) {
            $summaries = array_values(array_filter(
                $summaries,
                fn (Summary $summary): bool => $this->summaryRootId($summary, $filterType) === $filterId,
            ));
        }
        $diaporamas = $isRootFilter ? [] : $diaporamaRepository->findOwnedByUserWithSlides($user);

        return $this->render('my_summary/index.html.twig', [
            'summary_cards' => array_map($this->summaryCard(...), $summaries),
            'diaporama_cards' => array_map($this->diaporamaCard(...), $diaporamas),
            'root_filter' => $isRootFilter ? ['type' => $filterType, 'id' => $filterId] : null,
        ]);
    }

    #[Route('/mes-resumes/resumes/{id}', name: 'app_summary_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function showSummary(int $id, SummaryRepository $summaryRepository): Response
    {
        $summary = $this->ownedSummary($id, $summaryRepository);

        return $this->render('my_summary/show.html.twig', [
            'summary' => $summary,
            'parent_label' => $this->summaryParentLabel($summary),
        ]);
    }

    #[Route('/mes-resumes/resumes/{id}/modifier', name: 'app_summary_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function editSummary(
        int $id,
        Request $request,
        SummaryRepository $summaryRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $summary = $this->ownedSummary($id, $summaryRepository);
        $input = new SummaryEditInput();
        $input->title = $summary->getTitle();
        $input->content = $summary->getContent();
        $input->spoilerLevel = SpoilerLevel::tryFrom($summary->getSpoilerLevel() ?? '') ?? SpoilerLevel::Aucun;
        $form = $this->createForm(SummaryEditType::class, $input, [
            'csrf_token_id' => 'summary_edit_' . $summary->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->wrapInTransaction(function () use ($summary, $input): void {
                    $summary
                        ->setTitle($input->title)
                        ->setContent($input->content)
                        ->setSpoilerLevel($input->spoilerLevel->value);
                });
            } catch (\Throwable) {
                $this->addFlash('error', 'Le résumé n’a pas pu être modifié. Veuillez réessayer.');

                return $this->redirectToRoute('app_summary_edit', ['id' => $id]);
            }

            $this->addFlash('success', 'Le résumé a été modifié avec succès.');

            return $this->redirectToRoute('app_my_summaries');
        }

        return $this->render('my_summary/edit.html.twig', [
            'form' => $form,
            'content_type' => 'Résumé',
            'content_title' => $summary->getTitle(),
        ]);
    }

    #[Route('/mes-resumes/diaporamas/{id}/modifier', name: 'app_diaporama_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function editDiaporama(
        int $id,
        Request $request,
        DiaporamaRepository $diaporamaRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $diaporama = $this->ownedDiaporama($id, $diaporamaRepository);
        $input = new DiaporamaEditInput();
        $input->title = $diaporama->getTitle();
        $input->content = $diaporama->getContent();
        $form = $this->createForm(DiaporamaEditType::class, $input, [
            'csrf_token_id' => 'diaporama_edit_' . $diaporama->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->wrapInTransaction(function () use ($diaporama, $input): void {
                    $diaporama->setTitle($input->title)->setContent($input->content);
                });
            } catch (\Throwable) {
                $this->addFlash('error', 'Le diaporama n’a pas pu être modifié. Veuillez réessayer.');

                return $this->redirectToRoute('app_diaporama_edit', ['id' => $id]);
            }

            $this->addFlash('success', 'Le diaporama a été modifié avec succès.');

            return $this->redirectToRoute('app_my_summaries');
        }

        return $this->render('my_summary/edit.html.twig', [
            'form' => $form,
            'content_type' => 'Diaporama',
            'content_title' => $diaporama->getTitle(),
        ]);
    }

    #[Route('/mes-resumes/resumes/{id}/supprimer', name: 'app_summary_delete_confirm', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function confirmSummaryDelete(int $id, Request $request, SummaryRepository $summaryRepository): Response
    {
        $summary = $this->ownedSummary($id, $summaryRepository);

        return $this->render('my_summary/delete_confirmation.html.twig', [
            'content_type' => 'Résumé',
            'content_title' => $summary->getTitle(),
            'delete_route' => 'app_summary_delete',
            'content_id' => $summary->getId(),
            'csrf_id' => 'summary_delete_' . $summary->getId(),
            'return_to' => $this->safeReturnPath((string) $request->query->get('return_to', '')),
        ]);
    }

    #[Route('/mes-resumes/resumes/{id}/supprimer', name: 'app_summary_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function deleteSummary(
        int $id,
        Request $request,
        SummaryRepository $summaryRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $summary = $this->ownedSummary($id, $summaryRepository);
        if (!$this->isCsrfTokenValid('summary_delete_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $entityManager->wrapInTransaction(static function (EntityManagerInterface $entityManager) use ($summary): void {
                $entityManager->remove($summary);
            });
        } catch (\Throwable) {
            $this->addFlash('error', 'Le résumé n’a pas pu être supprimé.');

            return $this->redirect($this->safeReturnPath((string) $request->request->get('return_to', '')));
        }

        $this->addFlash('success', 'Le résumé a été supprimé définitivement.');

        return $this->redirect($this->safeReturnPath((string) $request->request->get('return_to', '')));
    }

    #[Route('/mes-resumes/diaporamas/{id}/supprimer', name: 'app_diaporama_delete_confirm', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function confirmDiaporamaDelete(int $id, DiaporamaRepository $diaporamaRepository): Response
    {
        $diaporama = $this->ownedDiaporama($id, $diaporamaRepository);

        return $this->render('my_summary/delete_confirmation.html.twig', [
            'content_type' => 'Diaporama',
            'content_title' => $diaporama->getTitle(),
            'delete_route' => 'app_diaporama_delete',
            'content_id' => $diaporama->getId(),
            'csrf_id' => 'diaporama_delete_' . $diaporama->getId(),
        ]);
    }

    #[Route('/mes-resumes/diaporamas/{id}/supprimer', name: 'app_diaporama_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function deleteDiaporama(
        int $id,
        Request $request,
        DiaporamaRepository $diaporamaRepository,
        SlideRepository $slideRepository,
        EntityManagerInterface $entityManager,
        SynopsisImageUploader $imageUploader,
    ): Response {
        $diaporama = $this->ownedDiaporama($id, $diaporamaRepository);
        if (!$this->isCsrfTokenValid('diaporama_delete_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $filenames = array_filter([
            $diaporama->getCoverImageFilename(),
            ...array_map(static fn ($slide): ?string => $slide->getImageFilename(), $diaporama->getSlides()->toArray()),
        ]);
        $filenames = array_values(array_unique($filenames));

        try {
            $entityManager->wrapInTransaction(static function (EntityManagerInterface $entityManager) use ($diaporama): void {
                $entityManager->remove($diaporama);
            });
        } catch (\Throwable) {
            $this->addFlash('error', 'Le diaporama n’a pas pu être supprimé.');

            return $this->redirectToRoute('app_my_summaries');
        }

        foreach ($filenames as $filename) {
            if ($diaporamaRepository->countFilenameReferences($filename) === 0
                && $slideRepository->countFilenameReferences($filename) === 0
            ) {
                $imageUploader->remove($filename);
            }
        }

        $this->addFlash('success', 'Le diaporama et ses scènes ont été supprimés définitivement.');

        return $this->redirectToRoute('app_my_summaries');
    }

    private function ownedSummary(int $id, SummaryRepository $repository): Summary
    {
        $summary = $repository->findOneOwned($id, $this->requireUser());
        if ($summary === null) {
            throw $this->createNotFoundException();
        }

        return $summary;
    }

    private function ownedDiaporama(int $id, DiaporamaRepository $repository): Diaporama
    {
        $diaporama = $repository->findOneOwnedWithSlides($id, $this->requireUser());
        if ($diaporama === null) {
            throw $this->createNotFoundException();
        }

        return $diaporama;
    }

    /** @return array<string, mixed> */
    private function summaryCard(Summary $summary): array
    {
        [$editRoute, $editId] = match (true) {
            $summary->getAnime() !== null => ['app_forms_anime_edit', $summary->getAnime()->getId()],
            $summary->getManga() !== null => ['app_forms_manga_edit', $summary->getManga()->getId()],
            $summary->getSeason() !== null => ['app_forms_season_edit', $summary->getSeason()->getId()],
            $summary->getEpisode() !== null => ['app_forms_episode_edit', $summary->getEpisode()->getId()],
            $summary->getTome() !== null => ['app_forms_tome_edit', $summary->getTome()->getId()],
            $summary->getChapitre() !== null => ['app_forms_chapitre_edit', $summary->getChapitre()->getId()],
            default => ['app_summary_edit', $summary->getId()],
        };

        return [
            'id' => $summary->getId(),
            'kind' => 'summary',
            'type' => 'Résumé',
            'title' => $summary->getTitle(),
            'description' => $summary->getContent(),
            'meta' => $this->summaryParentLabel($summary),
            'image' => $summary->getAnime()?->getCoverAnimeUrl()
                ?? $summary->getManga()?->getCoverMangaUrl()
                ?? $summary->getSeason()?->getCoverSeasonUrl()
                ?? $summary->getEpisode()?->getCoverEpisodeUrl()
                ?? $summary->getTome()?->getCoverTomeUrl()
                ?? $summary->getChapitre()?->getCoverChapitreUrl(),
            'openRoute' => 'app_summary_show',
            'editRoute' => $editRoute,
            'editId' => $editId,
            'deleteRoute' => 'app_summary_delete_confirm',
        ];
    }

    /** @return array<string, mixed> */
    private function diaporamaCard(Diaporama $diaporama): array
    {
        $firstSlide = $diaporama->getSlides()->first() ?: null;

        return [
            'id' => $diaporama->getId(),
            'kind' => 'diaporama',
            'type' => $diaporama->getSourceType() === Diaporama::SOURCE_ANIME ? 'Diaporama Anime' : 'Diaporama Manga',
            'title' => $diaporama->getTitle(),
            'description' => $diaporama->getContent(),
            'meta' => sprintf('%d scène%s', $diaporama->getSlides()->count(), $diaporama->getSlides()->count() > 1 ? 's' : ''),
            'imageRouteParameters' => $firstSlide?->getImageFilename() !== null ? [
                'diaporamaId' => $diaporama->getId(),
                'slideId' => $firstSlide->getId(),
            ] : null,
            'openRoute' => 'app_diaporama_show',
            'editRoute' => 'app_diaporama_edit',
            'editId' => $diaporama->getId(),
            'deleteRoute' => 'app_diaporama_delete_confirm',
        ];
    }

    private function summaryParentLabel(Summary $summary): string
    {
        return $summary->getAnime()?->getTitle()
            ?? $summary->getManga()?->getTitle()
            ?? $summary->getSeason()?->getTitle()
            ?? $summary->getEpisode()?->getTitle()
            ?? $summary->getTome()?->getTitle()
            ?? $summary->getChapitre()?->getTitle()
            ?? 'Aucun parent renseigné';
    }

    private function summaryRootId(Summary $summary, string $type): ?int
    {
        return $type === 'anime'
            ? ($summary->getAnime()
                ?? $summary->getSeason()?->getAnime()
                ?? $summary->getEpisode()?->getSeason()?->getAnime())?->getId()
            : ($summary->getManga()
                ?? $summary->getTome()?->getManga()
                ?? $summary->getChapitre()?->getManga())?->getId();
    }

    private function safeReturnPath(string $target): string
    {
        if ($target === '' || !str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return $this->generateUrl('app_my_summaries');
        }

        $parts = parse_url($target);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            return $this->generateUrl('app_my_summaries');
        }

        $allowedPaths = ['/home', '/catalogue', '/favoris', '/profile', '/mes-resumes'];
        if (!in_array($parts['path'] ?? '', $allowedPaths, true)) {
            return $this->generateUrl('app_my_summaries');
        }

        return $target;
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
