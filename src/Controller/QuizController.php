<?php

namespace App\Controller;

use App\Entity\Anime;
use App\Entity\QuizScore;
use App\Entity\User;
use App\Repository\AnimeRepository;
use App\Repository\QuizScoreRepository;
use App\Service\GeminiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/quiz')]
class QuizController extends AbstractController
{
    #[Route('/', name: 'app_quiz_index', methods: ['GET'])]
    public function index(Request $request, AnimeRepository $animeRepository, GeminiService $geminiService): Response
    {
        // Récupérer le type de quiz sélectionné (par défaut: genres)
        $quizType = $request->query->get('type', 'genres');
        // Vérifier si l'utilisateur a demandé des nouvelles questions
        $refresh = $request->query->has('refresh');
        
        // Récupérer tous les animes pour créer des questions de quiz
        $animes = $animeRepository->findAll();
        
        // Définir le titre du quiz
        $quizTitle = match($quizType) {
            'ages' => 'Quiz IA sur les âges recommandés',
            'statuts' => 'Quiz IA sur les statuts des animes',
            'personnages' => 'Quiz IA sur les personnages d\'anime',
            'univers' => 'Quiz IA sur l\'univers des animes',
            'intrigue' => 'Quiz IA sur les intrigues d\'anime',
            default => 'Quiz IA sur les genres d\'anime',
        };
        
        // Clé de cache pour stocker/récupérer les questions
        $cacheKey = 'quiz_questions_' . $quizType;
        $session = $request->getSession();
        
        $quizQuestions = [];
        
        // Générer de nouvelles questions si demandé ou si aucune en cache
        if ($refresh || !$session->has($cacheKey)) {
            // Tenter de générer des questions avec Gemini
            try {
                $geminiQuestions = $geminiService->generateQuizQuestions($animes, $quizType, 5);
                if (!empty($geminiQuestions)) {
                    $quizQuestions = $geminiQuestions;
                    $session->set($cacheKey, $quizQuestions);
                } else {
                    throw new \Exception('Pas de questions générées par Gemini');
                }
            } catch (\Exception $e) {
                // Utiliser le générateur de questions local comme solution de secours
                $quizTitle .= ' (généré localement)';
                error_log('Erreur avec l\'API Gemini: ' . $e->getMessage());
                
                // Générer des questions localement selon le type de quiz
                switch ($quizType) {
                    case 'ages':
                        $quizQuestions = $this->prepareAgeRatingQuestions($animes);
                        break;
                    case 'statuts':
                        $quizQuestions = $this->prepareStatusQuestions($animes);
                        break;
                    default:
                        $quizQuestions = $this->prepareGenreQuestions($animes);
                }
                
                // Sauvegarder en cache
                if (!empty($quizQuestions)) {
                    $session->set($cacheKey, $quizQuestions);
                }
            }
        } else {
            // Utiliser les questions en cache
            $quizQuestions = $session->get($cacheKey);
        }
        
        return $this->render('quiz/index.html.twig', [
            'quiz_questions' => $quizQuestions,
            'quiz_type' => $quizType,
            'quiz_title' => $quizTitle,
        ]);
    }
    
    #[Route('/result', name: 'app_quiz_result', methods: ['POST'])]
    public function result(Request $request, AnimeRepository $animeRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupérer les réponses soumises
        $answers = $request->request->all();
        $quizType = $request->request->get('quiz_type', 'genres');
        $score = 0;
        $totalQuestions = 0;
        
        // Récupérer les questions de quiz depuis la session pour les types de quiz basés sur Google Search
        $isGoogleSearchQuiz = in_array($quizType, ['personnages', 'univers', 'intrigue']);
        $session = $request->getSession();
        $quizQuestions = [];
        
        if ($isGoogleSearchQuiz) {
            $cacheKey = 'quiz_questions_' . $quizType;
            if ($session->has($cacheKey)) {
                $quizQuestions = $session->get($cacheKey);
            }
        }
        
        // Calculer le score en fonction du type de quiz
        foreach ($answers as $key => $value) {
            if (str_starts_with($key, 'question_')) {
                $totalQuestions++;
                $questionId = substr($key, 9);
                
                if ($isGoogleSearchQuiz) {
                    // Pour les quiz basés sur Google Search, vérifier la réponse dans les questions en cache
                    foreach ($quizQuestions as $question) {
                        if (isset($question['id']) && $question['id'] == $questionId && 
                            isset($question['correct_answer']) && $question['correct_answer'] === $value) {
                            $score++;
                            break;
                        }
                    }
                } else {
                    // Pour les quiz standards, vérifier par rapport aux données des entités
                    $anime = $animeRepository->find($questionId);
                    if ($anime) {
                        switch ($quizType) {
                            case 'ages':
                                if ($value === $anime->getAge()) {
                                    $score++;
                                }
                                break;
                            case 'statuts':
                                if ($value === $anime->getStatut()) {
                                    $score++;
                                }
                                break;
                            case 'genres':
                            default:
                                if ($anime->getGenre_id() && $value === $anime->getGenre_id()->getName()) {
                                    $score++;
                                }
                                break;
                        }
                    }
                }
            }
        }
        
        // Enregistrer le score si l'utilisateur est connecté
        $user = $this->getUser();
        if ($user && $totalQuestions > 0) {
            $quizScore = new QuizScore();
            $quizScore->setUser($user)
                      ->setScore($score)
                      ->setTotalQuestions($totalQuestions)
                      ->setQuizType($quizType);
            $entityManager->persist($quizScore);
            $entityManager->flush();
        }
        
        return $this->render('quiz/result.html.twig', [
            'score' => $score,
            'total_questions' => $totalQuestions,
            'quiz_type' => $quizType,
        ]);
    }
    
    #[Route('/leaderboard', name: 'app_quiz_leaderboard', methods: ['GET'])]
    public function leaderboard(Request $request, QuizScoreRepository $scoreRepository): Response
    {
        // Récupérer le type de quiz pour le classement (par défaut: tous)
        $quizType = $request->query->get('type', 'all');
        
        // Récupérer les meilleurs scores
        if ($quizType === 'all') {
            $topScores = $scoreRepository->findBy([], ['score' => 'DESC', 'date' => 'DESC'], 10);
        } else {
            $topScores = $scoreRepository->findBy(['quiz_type' => $quizType], ['score' => 'DESC', 'date' => 'DESC'], 10);
        }
        
        // Récupérer les scores de l'utilisateur connecté s'il existe
        $userScores = [];
        $user = $this->getUser();
        if ($user) {
            if ($quizType === 'all') {
                $userScores = $scoreRepository->findBy(['user' => $user], ['date' => 'DESC'], 5);
            } else {
                $userScores = $scoreRepository->findBy(['user' => $user, 'quiz_type' => $quizType], ['date' => 'DESC'], 5);
            }
        }
        
        return $this->render('quiz/leaderboard.html.twig', [
            'top_scores' => $topScores,
            'user_scores' => $userScores,
            'quiz_type' => $quizType
        ]);
    }

    /**
     * Prépare les questions de quiz sur les genres d'anime
     */
    private function prepareGenreQuestions(array $animes): array
    {
        $quizQuestions = [];
        
        // Mélanger les animes pour avoir des questions différentes à chaque fois
        shuffle($animes);
        
        // Limiter à 5 questions
        $animes = array_slice($animes, 0, min(5, count($animes)));
        
        // Créer des questions de type "À quel genre appartient cet anime ?"
        foreach ($animes as $anime) {
            if ($anime->getGenre_id()) {
                $question = [
                    'id' => $anime->getId(),
                    'question' => 'À quel genre appartient l\'anime "' . $anime->getName() . '" ?',
                    'image' => $anime->getImage(),
                    'options' => $this->getGenreOptions($animes, $anime),
                    'correct_answer' => $anime->getGenre_id()->getName(),
                ];
                
                $quizQuestions[] = $question;
            }
        }
        
        return $quizQuestions;
    }
    
    /**
     * Prépare les questions de quiz sur les âges recommandés
     */
    private function prepareAgeRatingQuestions(array $animes): array
    {
        $quizQuestions = [];
        
        // Mélanger les animes pour avoir des questions différentes à chaque fois
        shuffle($animes);
        
        // Limiter à 5 questions
        $animes = array_slice($animes, 0, min(5, count($animes)));
        
        // Options d'âge standard
        $ageOptions = ['Tous publics', '7+', '12+', '16+', '18+'];
        
        // Créer des questions de type "Quelle est la classification d'âge de cet anime ?"
        foreach ($animes as $anime) {
            // S'assurer que l'anime a une classification d'âge
            if ($anime->getAge()) {
                $options = $ageOptions;
                
                // Assurer que la bonne réponse est dans les options
                if (!in_array($anime->getAge(), $options)) {
                    $options[] = $anime->getAge();
                }
                
                // Réduire à 4 options maximum si nécessaire
                if (count($options) > 4) {
                    // Garder la bonne réponse et en choisir 3 autres aléatoirement
                    $correctOption = $anime->getAge();
                    $otherOptions = array_filter($options, function($option) use ($correctOption) {
                        return $option !== $correctOption;
                    });
                    shuffle($otherOptions);
                    $otherOptions = array_slice($otherOptions, 0, 3);
                    $options = array_merge([$correctOption], $otherOptions);
                }
                
                // Mélanger pour que la bonne réponse ne soit pas toujours en première position
                shuffle($options);
                
                $question = [
                    'id' => $anime->getId(),
                    'question' => 'Quelle est la classification d\'âge recommandée pour "' . $anime->getName() . '" ?',
                    'image' => $anime->getImage(),
                    'options' => $options,
                    'correct_answer' => $anime->getAge(),
                ];
                
                $quizQuestions[] = $question;
            }
        }
        
        return $quizQuestions;
    }
    
    /**
     * Prépare les questions de quiz sur les statuts des animes
     */
    private function prepareStatusQuestions(array $animes): array
    {
        $quizQuestions = [];
        
        // Mélanger les animes pour avoir des questions différentes à chaque fois
        shuffle($animes);
        
        // Limiter à 5 questions
        $animes = array_slice($animes, 0, min(5, count($animes)));
        
        // Options de statut standard
        $statusOptions = ['open', 'in-progress', 'resolved'];
        $statusLabels = [
            'open' => 'Disponible',
            'in-progress' => 'En cours',
            'resolved' => 'Terminé'
        ];
        
        // Créer des questions de type "Quel est le statut de cet anime ?"
        foreach ($animes as $anime) {
            if ($anime->getStatut()) {
                $options = $statusOptions;
                
                // Assurer que la bonne réponse est dans les options
                if (!in_array($anime->getStatut(), $options)) {
                    $options[] = $anime->getStatut();
                }
                
                // Mélanger pour que la bonne réponse ne soit pas toujours en première position
                shuffle($options);
                
                // Convertir les valeurs techniques en libellés lisibles
                $displayOptions = array_map(function($option) use ($statusLabels) {
                    return $statusLabels[$option] ?? $option;
                }, $options);
                
                $question = [
                    'id' => $anime->getId(),
                    'question' => 'Quel est le statut actuel de l\'anime "' . $anime->getName() . '" ?',
                    'image' => $anime->getImage(),
                    'options' => $displayOptions,
                    'correct_answer' => $statusLabels[$anime->getStatut()] ?? $anime->getStatut(),
                    'value_mapping' => array_combine($displayOptions, $options) // Pour mapper les valeurs d'affichage aux valeurs réelles
                ];
                
                $quizQuestions[] = $question;
            }
        }
        
        return $quizQuestions;
    }
    
    /**
     * Génère des options de réponse pour chaque question de genre
     */
    private function getGenreOptions(array $animes, Anime $currentAnime): array
    {
        $options = [];
        $correctGenre = $currentAnime->getGenre_id()->getName();
        $options[] = $correctGenre;
        
        // Récupérer d'autres genres comme options
        foreach ($animes as $anime) {
            if ($anime !== $currentAnime && $anime->getGenre_id()) {
                $genreName = $anime->getGenre_id()->getName();
                if (!in_array($genreName, $options)) {
                    $options[] = $genreName;
                    if (count($options) >= 4) {
                        break;
                    }
                }
            }
        }
        
        // Si nous n'avons pas assez d'options, ajouter quelques genres génériques
        $genericGenres = ['Action', 'Romance', 'Horror', 'Comedy', 'Sci-Fi', 'Fantasy'];
        shuffle($genericGenres);
        
        foreach ($genericGenres as $genre) {
            if (!in_array($genre, $options)) {
                $options[] = $genre;
                if (count($options) >= 4) {
                    break;
                }
            }
        }
        
        // Mélanger les options pour que la bonne réponse ne soit pas toujours à la même position
        shuffle($options);
        
        return $options;
    }
}
