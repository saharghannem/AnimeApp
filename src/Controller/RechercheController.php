<?php

namespace App\Controller;

use App\Entity\Anime;
use App\Repository\AnimeRepository;
use App\Service\GoogleSearchService;
use App\Service\GeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/recherche')]
class RechercheController extends AbstractController
{
    #[Route('/anime/{id}', name: 'app_recherche_anime_details')]
    public function animeDetails(Anime $anime, GoogleSearchService $googleSearchService, GeminiService $geminiService): Response
    {
        // Récupérer des informations sur les personnages via Google
        $characterInfo = $googleSearchService->searchAnimeInfo($anime->getName(), 'characters');
        
        // Récupérer des informations sur l'univers via Google
        $universeInfo = $googleSearchService->searchAnimeInfo($anime->getName(), 'universe');
        
        // Récupérer des informations sur l'intrigue via Google
        $plotInfo = $googleSearchService->searchAnimeInfo($anime->getName(), 'plot');
        
        // Résumé des informations par l'IA Gemini
        $characterSummary = $this->generateSummary($geminiService, $anime->getName(), $characterInfo, 'personnages');
        $universeSummary = $this->generateSummary($geminiService, $anime->getName(), $universeInfo, 'univers');
        $plotSummary = $this->generateSummary($geminiService, $anime->getName(), $plotInfo, 'intrigue');
        
        return $this->render('recherche/anime_details.html.twig', [
            'anime' => $anime,
            'character_info' => $characterInfo,
            'universe_info' => $universeInfo,
            'plot_info' => $plotInfo,
            'character_summary' => $characterSummary,
            'universe_summary' => $universeSummary,
            'plot_summary' => $plotSummary
        ]);
    }
    
    /**
     * Générer un résumé des informations avec Gemini
     */
    private function generateSummary(GeminiService $geminiService, string $animeName, array $searchResults, string $topic): string
    {
        // Vérifier si nous avons des résultats
        if (empty($searchResults)) {
            return "Aucune information disponible sur les {$topic} de cet anime.";
        }
        
        // Construire le prompt pour Gemini avec les résultats de recherche
        $searchData = [];
        foreach ($searchResults as $result) {
            $searchData[] = [
                'title' => $result['title'] ?? '',
                'snippet' => $result['snippet'] ?? '',
                'source' => $result['source'] ?? ''
            ];
        }
        
        $prompt = "Voici des informations sur les {$topic} de l'anime '{$animeName}' issues de recherches Google :\n\n";
        foreach ($searchData as $data) {
            $prompt .= "Titre: {$data['title']}\nExtrait: {$data['snippet']}\nSource: {$data['source']}\n\n";
        }
        
        $prompt .= "En te basant sur ces informations, génère un résumé concis et informatif en français sur les {$topic} de cet anime. Utilise un ton engageant et informatif. Limite la réponse à environ 150 mots.";
        
        try {
            // Appeler l'API Gemini
            $response = $geminiService->generateContentDirect($prompt);
            if (!empty($response)) {
                return $response;
            }
        } catch (\Exception $e) {
            // En cas d'erreur, retourner un message par défaut
        }
        
        return "Information non disponible pour le moment. Veuillez réessayer plus tard.";
    }
}
