<?php

namespace App\Controller;

use App\Repository\QuizScoreRepository;
use App\Service\SimpleExcelExport;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class ExportController extends AbstractController
{
    #[Route('/export/anime-list', name: 'app_export_anime_list')]
    public function exportAnimeList(\App\Repository\AnimeRepository $animeRepository): Response
    {
        // Récupérer tous les animes
        $animes = $animeRepository->findAll();
        
        // Créer l'exportation
        $exporter = new SimpleExcelExport('Liste des Animes');
        
        // Définir les en-têtes
        $exporter->setHeaders([
            'ID',
            'Nom',
            'Description',
            'Statut',
            'Âge',
            'Genre',
            'Date'
        ]);
        
        // Ajouter les données
        foreach ($animes as $anime) {
            $genreName = 'Non catégorisé';
            // Approche sécurisée et robuste pour accéder au genre
            try {
                // D'abord, vérifions si l'anime est un objet valide
                if (!is_object($anime)) {
                    continue; // Passer à l'anime suivant si celui-ci n'est pas un objet
                }
                
                // Maintenant, essayons plusieurs méthodes pour obtenir le genre
                $genre = null;
                // 1. Méthode getGenre si elle existe
                if (method_exists($anime, 'getGenre')) {
                    $genre = $anime->getGenre();
                }
                // 2. Méthode getGenre_id si elle existe (et que getGenre n'a pas fonctionné)
                elseif (method_exists($anime, 'getGenre_id')) {
                    $genre = $anime->getGenre_id();
                }
                
                // Si nous avons un genre objet avec une méthode getName, utilisons-la
                if ($genre && is_object($genre) && method_exists($genre, 'getName')) {
                    $genreName = $genre->getName();
                }
            } catch (\Exception $e) {
                // En cas d'erreur, utiliser la valeur par défaut 'Non catégorisé'
            }
            
            $exporter->addRow([
                $anime->getId(),
                $anime->getName(),
                $anime->getDescrition(),
                $anime->getStatut(),
                $anime->getAge(),
                $genreName,
                (new \DateTime())->format('d/m/Y H:i:s')
            ]);
        }
        
        // Générer le fichier CSV et le renvoyer
        $filename = 'liste_animes_' . date('Y-m-d_H-i-s') . '.csv';
        $exporter->export($filename);
        
        // Cette ligne ne sera jamais atteinte car export() termine l'exécution du script
        return new Response('Export réussi');
    }
    #[Route('/export/quiz-scores', name: 'app_export_quiz_scores')]
    public function exportQuizScores(QuizScoreRepository $scoreRepository): Response
    {
        // Récupérer les meilleurs scores
        $scores = $scoreRepository->findBy([], ['score' => 'DESC', 'date' => 'DESC']);
        
        // Créer l'exportation
        $exporter = new SimpleExcelExport('Scores des Quiz Anime');
        
        // Définir les en-têtes
        $exporter->setHeaders([
            'Joueur',
            'Type de quiz',
            'Score',
            'Questions',
            'Pourcentage',
            'Date'
        ]);
        
        // Ajouter les données
        foreach ($scores as $score) {
            $percentage = $score->getTotalQuestions() > 0 
                ? round(($score->getScore() / $score->getTotalQuestions()) * 100, 1) . '%'
                : '0%';
            
            $quizType = '';
            switch ($score->getQuizType()) {
                case 'genres':
                    $quizType = 'Quiz des genres';
                    break;
                case 'ages':
                    $quizType = 'Quiz des âges';
                    break;
                case 'statuts':
                    $quizType = 'Quiz des statuts';
                    break;
                default:
                    $quizType = 'Quiz inconnu';
                    break;
            }
            
            $exporter->addRow([
                $score->getUser() ? $score->getUser()->getEmail() : 'Utilisateur inconnu',
                $quizType,
                $score->getScore(),
                $score->getTotalQuestions(),
                $percentage,
                $score->getDate()->format('d/m/Y H:i')
            ]);
        }
        
        // Générer le fichier CSV et le renvoyer
        $filename = 'scores_quiz_anime_' . date('Y-m-d_H-i-s') . '.csv';
        $exporter->export($filename);
        
        // Cette ligne ne sera jamais atteinte car export() termine l'exécution du script
        return new Response('Export réussi');
    }
}
