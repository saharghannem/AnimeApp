<?php

namespace App\Service;

use App\Entity\Anime;
use App\Entity\QuizScore;
use App\Service\SimpleExcelExport;

/**
 * Service pour exporter les données au format Excel (CSV)
 */
class ExcelExportService
{
    /**
     * Exporter la liste des animes vers Excel
     */
    public function exportAnimes($animes, $filename = 'animes-export.csv')
    {
        $exporter = new SimpleExcelExport('Liste des Animes');

        // Définir les en-têtes
        $exporter->setHeaders([
            'ID',
            'Nom',
            'Genre',
            'Status',
            'Age'
        ]);

        // Ajouter les données
        foreach ($animes as $anime) {
            $genreName = 'Non catégorisé';
            try {
                // Essayons les deux méthodes possibles d'accès au genre
                $genre = null;
                if (method_exists($anime, 'getGenre')) {
                    $genre = $anime->getGenre();
                } elseif (method_exists($anime, 'getGenre_id')) {
                    $genre = $anime->getGenre_id();
                }

                if ($genre && is_object($genre) && method_exists($genre, 'getName')) {
                    $genreName = $genre->getName();
                }
            } catch (\Exception $e) {
                // Ignorer l'erreur et utiliser la valeur par défaut
            }

            $exporter->addRow([
                $anime->getId(),
                $anime->getName(),
                $genreName,
                $anime->getStatut(),
                $anime->getAge()
            ]);
        }

        // Export final
        $exporter->export($filename);
    }

    /**
     * Exporter les scores de quiz vers Excel
     */
    public function exportQuizScores($scores, $filename = 'quiz-scores-export.csv')
    {
        $exporter = new SimpleExcelExport('Scores de Quiz');

        // Définir les en-têtes
        $exporter->setHeaders([
            'ID',
            'Utilisateur',
            'Score',
            'Date',
            'Difficulté'
        ]);

        // Ajouter les données
        foreach ($scores as $score) {
            $username = 'Anonyme';
            try {
                $user = $score->getUser();
                if ($user && method_exists($user, 'getUsername')) {
                    $username = $user->getUsername();
                }
            } catch (\Exception $e) {
                // Ignorer l'erreur et utiliser la valeur par défaut
            }

            $exporter->addRow([
                $score->getId(),
                $username,
                $score->getScore(),
                $score->getDate() ? $score->getDate()->format('Y-m-d H:i:s') : 'N/A',
                $score->getDifficulty() ?: 'Standard'
            ]);
        }

        // Export final
        $exporter->export($filename);
    }
}