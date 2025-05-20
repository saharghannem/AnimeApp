<?php

namespace App\Controller;

use App\Entity\Anime;
use App\Entity\Genre;
use App\Repository\AnimeRepository;
use App\Repository\GenreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(AnimeRepository $animeRepository, GenreRepository $genreRepository): Response
    {
        // Récupérer tous les animes et les genres pour le tableau de bord
        $animes = $animeRepository->findAll();
        $genres = $genreRepository->findAll();
        
        // Calculer quelques statistiques pour le tableau de bord
        $totalAnimes = count($animes);
        $totalGenres = count($genres);
        
        return $this->render('dashboard/index.html.twig', [
            'animes' => $animes,
            'genres' => $genres,
            'totalAnimes' => $totalAnimes,
            'totalGenres' => $totalGenres,
        ]);
    }
}