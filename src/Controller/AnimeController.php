<?php

namespace App\Controller;

use App\Entity\Anime;
use App\Entity\Genre;
use App\Form\AnimeType;
use App\Repository\AnimeRepository;
use App\Repository\GenreRepository;
use App\Service\BrevoEmailService;
use App\Service\GeminiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/anime')]
final class AnimeController extends AbstractController
{
    #[Route('/', name: 'app_anime_index', methods: ['GET'])]

    public function index(EntityManagerInterface $entityManager): Response
    {
        $animes = $entityManager->getRepository(Anime::class)->findAll();

        $defaultGenre = $this->getOrCreateDefaultGenre($entityManager);
        foreach ($animes as $anime) {
            try {
                $genre = null;
                if (method_exists($anime, 'getGenre')) {
                    $genre = $anime->getGenre();
                } elseif (method_exists($anime, 'getGenre_id')) {
                    $genre = $anime->getGenre_id();
                }

                if ($genre && is_object($genre) && method_exists($genre, 'getName')) {
                    $genre->getName();
                }
            } catch (\Exception $e) {
                $anime->setGenre_id($defaultGenre);
                $entityManager->persist($anime);
            }
        }

        $entityManager->flush();

        return $this->render('anime/index.html.twig', [
            'animes' => $animes,
        ]);
    }

    #[Route('/new', name: 'app_anime_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager, BrevoEmailService $emailService): Response
    {
        $anime = new Anime();
        $anime->setName('');
        $anime->setDescrition('');
        $anime->setStatut('');
        $anime->setTrailerurl('');
        $anime->setImage('/img/default-anime.jpg');
        $defaultGenre = $this->getOrCreateDefaultGenre($entityManager);
        $anime->setGenre_id($defaultGenre);
        $anime->setAge('');

        $form = $this->createForm(AnimeType::class, $anime);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has('genre') && $form->get('genre')->getData()) {
                $anime->setGenre_id($form->get('genre')->getData());
            }

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugify($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/animes';
                    if (!file_exists($uploadsDir)) {
                        mkdir($uploadsDir, 0777, true);
                    }

                    $imageFile->move($uploadsDir, $newFilename);
                    $anime->setImage('/uploads/animes/' . $newFilename);
                } catch (\Exception $e) {
                    $anime->setImage('/img/default-anime.jpg');
                }
            }

            $entityManager->persist($anime);
            $entityManager->flush();

            try {
                $genre = $anime->getGenre_id();
                $genreName = $genre && is_object($genre) && method_exists($genre, 'getName') ? $genre->getName() : 'Non catégorisé';

                $emailService->sendAnimeCreationNotification(
                    $anime->getName(),
                    $anime->getId(),
                    $genreName,
                    $anime->getImage()
                );
            } catch (\Exception $e) {
                error_log('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_anime_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('anime/new.html.twig', [
            'anime' => $anime,
            'form' => $form,
        ]);
    }

    #[Route('/new-custom', name: 'app_anime_new_custom', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function customNew(Request $request, EntityManagerInterface $entityManager, BrevoEmailService $emailService): Response
    {
        $anime = new Anime();
        $genres = $entityManager->getRepository(Genre::class)->findAll();

        if ($request->isMethod('POST')) {
            $anime->setName($request->request->get('name'));
            $anime->setDescrition($request->request->get('descrition'));
            $anime->setStatut($request->request->get('statut'));
            $anime->setTrailerurl($request->request->get('trailerurl'));

            $imageFile = $request->files->get('imageFile');
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugify($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/animes';
                    if (!file_exists($uploadsDir)) {
                        mkdir($uploadsDir, 0777, true);
                    }

                    $imageFile->move($uploadsDir, $newFilename);
                    $anime->setImage('/uploads/animes/' . $newFilename);
                } catch (\Exception $e) {
                    $anime->setImage('/img/default-anime.jpg');
                }
            } else {
                $anime->setImage('/img/default-anime.jpg');
            }

            $genreId = $request->request->get('genre_id');
            if ($genreId) {
                $genre = $entityManager->getRepository(Genre::class)->find($genreId);
                if ($genre) {
                    $anime->setGenre_id($genre);
                }
            }

            $anime->setAge($request->request->get('age'));

            $errors = [];
            if (empty($anime->getName())) {
                $errors[] = 'Le nom ne peut pas être vide';
            }
            if (empty($anime->getDescrition())) {
                $errors[] = 'La description ne peut pas être vide';
            }

            if (empty($errors)) {
                $entityManager->persist($anime);
                $entityManager->flush();

                $genreName = $anime->getGenre_id() ? $anime->getGenre_id()->getName() : 'Non catégorisé';
                $emailService->sendAnimeCreationNotification(
                    $anime->getName(),
                    $anime->getId(),
                    $genreName,
                    $anime->getImage()
                );

                return $this->redirectToRoute('app_anime_index');
            }

            return $this->render('anime/new_custom.html.twig', [
                'anime' => $anime,
                'genres' => $genres,
                'errors' => $errors,
            ]);
        }

        return $this->render('anime/new_custom.html.twig', [
            'anime' => $anime,
            'genres' => $genres,
            'errors' => [],
        ]);
    }

    #[Route('/{id}', name: 'app_anime_show', methods: ['GET'])]
    public function show(Anime $anime, GeminiService $geminiService): Response
    {
        $trailerInfo = $geminiService->findAnimeTrailer($anime->getName());

        return $this->render('anime/show.html.twig', [
            'anime' => $anime,
            'trailerInfo' => $trailerInfo,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_anime_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Anime $anime, EntityManagerInterface $entityManager): Response
    {
        try {
            $genre = null;
            if (method_exists($anime, 'getGenre')) {
                $genre = $anime->getGenre();
            } elseif (method_exists($anime, 'getGenre_id')) {
                $genre = $anime->getGenre_id();
            }

            if (!$genre || !is_object($genre) || !method_exists($genre, 'getName')) {
                $defaultGenre = $this->getOrCreateDefaultGenre($entityManager);
                $anime->setGenre_id($defaultGenre);
            }
        } catch (\Exception $e) {
        }

        $form = $this->createForm(AnimeType::class, $anime);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has('genre') && $form->get('genre')->getData()) {
                $anime->setGenre_id($form->get('genre')->getData());
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_anime_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('anime/edit.html.twig', [
            'anime' => $anime,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_anime_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Anime $anime, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $anime->getId(), $request->request->get('_token'))) {
            $entityManager->remove($anime);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_anime_index', [], Response::HTTP_SEE_OTHER);
    }

    private function getOrCreateDefaultGenre(EntityManagerInterface $entityManager): Genre
    {
        $genreRepository = $entityManager->getRepository(Genre::class);
        $defaultGenre = $genreRepository->findOneBy([]);

        if (!$defaultGenre) {
            $defaultGenre = new Genre();
            $defaultGenre->setName('Animation');
            $defaultGenre->setDescrition('Genre par défaut pour tous les animes');
            $entityManager->persist($defaultGenre);
            $entityManager->flush();
        }

        return $defaultGenre;
    }

    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        return empty($text) ? 'n-a' : $text;
    }
}
