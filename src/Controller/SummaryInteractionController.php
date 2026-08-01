<?php

namespace App\Controller;

use App\Entity\SummaryLike;
use App\Entity\User;
use App\Repository\SummaryLikeRepository;
use App\Repository\SummaryRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class SummaryInteractionController extends AbstractController
{
    #[Route('/resumes/{id}/aimer', name: 'app_summary_like', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function like(int $id, Request $request, SummaryRepository $summaries, EntityManagerInterface $entityManager): Response
    {
        $user = $this->requireUser();
        $summary = $summaries->findOneVisibleTo($id, $user);
        if ($summary === null) { throw $this->createNotFoundException(); }
        if (!$this->isCsrfTokenValid('summary_like_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        if ($summary->getOwner()?->getId() === $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas aimer votre propre résumé.');
            return $this->returnToOrigin($request);
        }

        try {
            $entityManager->persist((new SummaryLike())->setUser($user)->setSummary($summary));
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            // Idempotent: another concurrent request already created the same like.
        }
        $this->addFlash('success', 'J’aime enregistré.');
        return $this->returnToOrigin($request);
    }

    #[Route('/resumes/{id}/retirer-jaime', name: 'app_summary_unlike', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unlike(int $id, Request $request, SummaryRepository $summaries, SummaryLikeRepository $likes): Response
    {
        $user = $this->requireUser();
        if ($summaries->findOneVisibleTo($id, $user) === null) { throw $this->createNotFoundException(); }
        if (!$this->isCsrfTokenValid('summary_unlike_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        $likes->removeForUserAndSummary($user, $id);
        $this->addFlash('success', 'J’aime retiré.');
        return $this->returnToOrigin($request);
    }

    #[Route('/mes-resumes/{id}/publier', name: 'app_summary_publish', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function publish(int $id, Request $request, SummaryRepository $summaries, EntityManagerInterface $entityManager): Response
    {
        return $this->changeVisibility($id, true, $request, $summaries, $entityManager);
    }

    #[Route('/mes-resumes/{id}/rendre-prive', name: 'app_summary_unpublish', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unpublish(int $id, Request $request, SummaryRepository $summaries, EntityManagerInterface $entityManager): Response
    {
        return $this->changeVisibility($id, false, $request, $summaries, $entityManager);
    }

    private function changeVisibility(int $id, bool $public, Request $request, SummaryRepository $summaries, EntityManagerInterface $entityManager): Response
    {
        $user = $this->requireUser();
        $summary = $summaries->findOneOwned($id, $user);
        if ($summary === null || ($summary->getEpisode() === null && $summary->getTome() === null && $summary->getChapitre() === null)) {
            throw $this->createNotFoundException();
        }
        $action = $public ? 'publish' : 'unpublish';
        if (!$this->isCsrfTokenValid('summary_' . $action . '_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        $root = $summary->getEpisode()?->getSeason()?->getAnime()
            ?? $summary->getTome()?->getManga()
            ?? $summary->getChapitre()?->getManga();
        if ($root === null || $root->getOwner()?->getId() !== $user->getId()) {
            throw $this->createNotFoundException();
        }
        $summary->setIsPublic($public);
        if ($public) {
            $root->setIsPublic(true);
            $summary->setPublishedAt(new \DateTimeImmutable());
        }
        $entityManager->flush();
        $this->addFlash('success', $public ? 'L’œuvre et le résumé sont maintenant publics.' : 'Le résumé est maintenant privé.');
        return $this->returnToOrigin($request);
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) { throw $this->createAccessDeniedException(); }
        return $user;
    }

    private function returnToOrigin(Request $request): Response
    {
        $path = (string) $request->request->get('return_to', '');
        if ($path !== '' && str_starts_with($path, '/') && !str_starts_with($path, '//')) {
            return $this->redirect($path);
        }
        return $this->redirectToRoute('app_my_summaries');
    }
}
