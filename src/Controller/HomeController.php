<?php

namespace App\Controller;

use App\Repository\AnimeRepository;
use App\Repository\GenreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(AnimeRepository $animeRepository, GenreRepository $genreRepository): Response
    {
        // Récupérer les animes pour l'affichage sur la page d'accueil
        $animes = $animeRepository->findAll();
        $genres = $genreRepository->findAll();
        
        // Calculer quelques statistiques pour l'affichage
        $totalAnimes = count($animes);
        
        return $this->render('home.html.twig', [
            'animes' => $animes,
            'genres' => $genres,
            'totalAnimes' => $totalAnimes,
        ]);
    }
}
