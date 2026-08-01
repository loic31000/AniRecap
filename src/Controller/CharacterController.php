<?php

namespace App\Controller;

use App\Dto\CharacterInput;
use App\Entity\Character;
use App\Entity\User;
use App\Form\CharacterType;
use App\Repository\AnimeRepository;
use App\Repository\ChapitreRepository;
use App\Repository\CharacterRepository;
use App\Repository\EpisodeRepository;
use App\Repository\MangaRepository;
use App\Repository\SeasonRepository;
use App\Repository\TomeRepository;
use App\Service\SynopsisImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class CharacterController extends AbstractController
{
    #[Route('/formulaires/personnage', name: 'app_forms_character', methods: ['GET', 'POST'])]
    public function create(Request $request, AnimeRepository $animes, SeasonRepository $seasons, EpisodeRepository $episodes, MangaRepository $mangas, TomeRepository $tomes, ChapitreRepository $chapitres, EntityManagerInterface $em, SynopsisImageUploader $uploader): Response
    {
        $user = $this->requireUser();
        $choices = $this->ownedChoices($user, $animes, $seasons, $episodes, $mangas, $tomes, $chapitres);
        $input = new CharacterInput();
        $form = $this->createForm(CharacterType::class, $input, ['owned_choices' => $choices, 'csrf_token_id' => 'character_create']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->assertOwnedChoices($input, $this->ownedChoices($user, $animes, $seasons, $episodes, $mangas, $tomes, $chapitres));
            $filename = null;
            try {
                if ($input->image instanceof UploadedFile) { $filename = $uploader->store($input->image); }
                $character = (new Character())->setOwner($user);
                $em->wrapInTransaction(function () use ($em, $character, $input, $filename): void {
                    $this->applyInput($character, $input, $filename);
                    $em->persist($character);
                });
            } catch (\Throwable) {
                if ($filename !== null) { $uploader->remove($filename); }
                $this->addFlash('error', 'Le personnage n’a pas pu être enregistré. Veuillez réessayer.');
                return $this->redirectToRoute('app_forms_character');
            }
            $this->addFlash('success', 'Le personnage a été créé avec succès.');
            return $this->redirectToRoute('app_character_show', ['id' => $character->getId()]);
        }

        return $this->render('forms/character.html.twig', ['form' => $form, 'is_edit' => false, 'character' => null]);
    }

    #[Route('/formulaires/personnages/{id}/modifier', name: 'app_forms_character_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, CharacterRepository $characters, AnimeRepository $animes, SeasonRepository $seasons, EpisodeRepository $episodes, MangaRepository $mangas, TomeRepository $tomes, ChapitreRepository $chapitres, EntityManagerInterface $em, SynopsisImageUploader $uploader): Response
    {
        $user = $this->requireUser();
        $character = $characters->findOneOwned($id, $user);
        if ($character === null) { throw $this->createNotFoundException(); }
        $choices = $this->ownedChoices($user, $animes, $seasons, $episodes, $mangas, $tomes, $chapitres);
        $input = $this->inputFromCharacter($character);
        $form = $this->createForm(CharacterType::class, $input, ['owned_choices' => $choices, 'is_edit' => true, 'csrf_token_id' => 'character_edit_' . $id]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->assertOwnedChoices($input, $this->ownedChoices($user, $animes, $seasons, $episodes, $mangas, $tomes, $chapitres));
            $oldFilename = $character->getImageUrl(); $newFilename = null;
            try {
                if ($input->image instanceof UploadedFile) { $newFilename = $uploader->store($input->image); }
                $em->wrapInTransaction(fn () => $this->applyInput($character, $input, $newFilename ?? $oldFilename));
            } catch (\Throwable) {
                if ($newFilename !== null) { $uploader->remove($newFilename); }
                $this->addFlash('error', 'Le personnage n’a pas pu être modifié. Veuillez réessayer.');
                return $this->redirectToRoute('app_forms_character_edit', ['id' => $id]);
            }
            if ($newFilename !== null && $oldFilename !== null) { $uploader->remove($oldFilename); }
            $this->addFlash('success', 'Le personnage a été modifié avec succès.');
            return $this->redirectToRoute('app_character_show', ['id' => $id]);
        }
        return $this->render('forms/character.html.twig', ['form' => $form, 'is_edit' => true, 'character' => $character]);
    }

    #[Route('/personnages/{id}', name: 'app_character_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, CharacterRepository $repository): Response
    {
        $user = $this->requireUser(); $character = $repository->findOneOwned($id, $user);
        if ($character === null) { throw $this->createNotFoundException(); }
        return $this->render('character/index.html.twig', ['character' => $character, 'catalogueRoute' => 'app_catalogue']);
    }

    #[Route('/personnages/{id}/image', name: 'app_character_image', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function image(int $id, CharacterRepository $repository, SynopsisImageUploader $uploader): Response
    {
        $character = $repository->findOneOwned($id, $this->requireUser());
        if ($character === null || $character->getImageUrl() === null || ($path = $uploader->resolve($character->getImageUrl())) === null) { throw $this->createNotFoundException(); }
        return $this->file($path, null, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    private function requireUser(): User { $user = $this->getUser(); if (!$user instanceof User) { throw $this->createAccessDeniedException(); } return $user; }

    private function ownedChoices(User $user, AnimeRepository $animes, SeasonRepository $seasons, EpisodeRepository $episodes, MangaRepository $mangas, TomeRepository $tomes, ChapitreRepository $chapitres): array
    {
        return ['animes' => $animes->findOwnedPrivate($user), 'seasons' => $seasons->findOwnedForCharacterSelection($user), 'episodes' => $episodes->findOwnedForSceneSelection($user), 'mangas' => $mangas->findOwnedPrivate($user), 'tomes' => $tomes->findOwnedForSceneSelection($user), 'chapitres' => $chapitres->findOwnedForSceneSelection($user)];
    }

    private function assertOwnedChoices(CharacterInput $input, array $allowed): void
    {
        foreach (array_keys($allowed) as $field) {
            $allowedIds = array_map(static fn ($entity) => $entity->getId(), $allowed[$field]);
            foreach ($input->{$field} as $entity) { if (!in_array($entity->getId(), $allowedIds, true)) { throw $this->createNotFoundException(); } }
        }
    }

    private function inputFromCharacter(Character $character): CharacterInput
    {
        $input = new CharacterInput(); $input->name = $character->getName(); $input->description = $character->getDescription(); $input->spoilerLevel = $character->getSpoilerLevel();
        $input->animes = $character->getAnimes()->toArray(); $input->seasons = $character->getSeasons()->toArray(); $input->episodes = $character->getEpisodes()->toArray(); $input->mangas = $character->getMangas()->toArray(); $input->tomes = $character->getTomes()->toArray(); $input->chapitres = $character->getChapitres()->toArray(); return $input;
    }

    private function applyInput(Character $character, CharacterInput $input, ?string $filename): void
    {
        $character->setName((string) $input->name)->setDescription($input->description)->setImageUrl($filename)->setSpoilerLevel($input->spoilerLevel);
        $relations = ['Animes' => $input->animes, 'Seasons' => $input->seasons, 'Episodes' => $input->episodes, 'Mangas' => $input->mangas, 'Tomes' => $input->tomes, 'Chapitres' => $input->chapitres];
        foreach ($relations as $suffix => $selected) {
            $getter = 'get' . $suffix; $adder = 'add' . rtrim($suffix, 's'); $remover = 'remove' . rtrim($suffix, 's');
            foreach ($character->{$getter}()->toArray() as $current) { if (!in_array($current, $selected, true)) { $character->{$remover}($current); } }
            foreach ($selected as $target) { $character->{$adder}($target); }
        }
    }
}
