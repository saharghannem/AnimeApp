<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GeminiService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent';
    private GoogleSearchService $googleSearchService;

    public function __construct(HttpClientInterface $httpClient, GoogleSearchService $googleSearchService, ParameterBagInterface $params)
    {
        $this->apiKey = $params->get('gemini_api_key');
        $this->httpClient = $httpClient;
        $this->googleSearchService = $googleSearchService;
    }

    /**
     * Génère des questions de quiz sur les animes en utilisant l'API Gemini
     */
    public function generateQuizQuestions(array $animes, string $quizType = 'genres', int $numberOfQuestions = 5): array
    {
        error_log("Generating quiz with quizType: {$quizType}, numberOfQuestions: {$numberOfQuestions}");
        switch ($quizType) {
            case 'personnages':
                $questions = $this->generateCharacterQuizWithGoogleSearch($animes, $numberOfQuestions);
                break;
            case 'univers':
                $questions = $this->generateQuizWithGoogleSearch($animes, 'universe', $numberOfQuestions);
                break;
            case 'intrigue':
                $questions = $this->generateQuizWithGoogleSearch($animes, 'plot', $numberOfQuestions);
                break;
            case 'ages':
                $questions = $this->generateQuizWithGoogleSearch($animes, 'ages', $numberOfQuestions);
                break;
            case 'statuts':
                $questions = $this->generateQuizWithGoogleSearch($animes, 'statuts', $numberOfQuestions);
                break;
            case 'genres':
            default:
                $questions = $this->generateQuizWithGoogleSearch($animes, 'genres', $numberOfQuestions);
                break;
        }
        error_log("Generated questions: " . json_encode($questions));
        return $questions;
    }

    /**
     * Retourne un trailer pour un anime spécifique en utilisant une base de données locale
     */
    public function findAnimeTrailer(string $animeName): array
    {
        $trailerDatabase = [
            'naruto' => ['url' => 'https://www.youtube.com/embed/QczGoCmX-pI', 'title' => 'Naruto - Bande Annonce Officielle', 'description' => 'Trailer officiel de Naruto Shippuden.', 'is_external' => false],
            'one piece' => ['url' => 'https://www.youtube.com/embed/S8_YwFLCh4U', 'title' => 'One Piece - Trailer Officiel', 'description' => 'Bande-annonce de la série One Piece.', 'is_external' => false],
            'attack on titan' => ['url' => 'https://www.youtube.com/embed/MGRm4IzK1SQ', 'title' => 'L\'Attaque des Titans - Trailer Officiel', 'description' => 'Trailer officiel de Shingeki no Kyojin (L\'Attaque des Titans).', 'is_external' => false],
            'demon slayer' => ['url' => 'https://www.youtube.com/embed/VQGCKyvzIM4', 'title' => 'Demon Slayer - Bande Annonce', 'description' => 'Trailer officiel de Kimetsu no Yaiba (Demon Slayer).', 'is_external' => false],
            'my hero academia' => ['url' => 'https://www.youtube.com/embed/EPVkcwyLQQ8', 'title' => 'My Hero Academia - Trailer', 'description' => 'Bande-annonce de Boku no Hero Academia.', 'is_external' => false],
            'sword art online' => ['url' => 'https://www.youtube.com/embed/6ohYYtxfDCg', 'title' => 'Sword Art Online - Trailer', 'description' => 'Trailer officiel de la série Sword Art Online.', 'is_external' => false],
            'tokyo ghoul' => ['url' => 'https://www.youtube.com/embed/vGuQeQsoRgU', 'title' => 'Tokyo Ghoul - Trailer Officiel', 'description' => 'Bande-annonce de Tokyo Ghoul.', 'is_external' => false],
            'hunter x hunter' => ['url' => 'https://www.youtube.com/embed/d6kBeJjTGnY', 'title' => 'Hunter X Hunter - Trailer', 'description' => 'Trailer officiel de Hunter X Hunter.', 'is_external' => false],
            'fullmetal alchemist' => ['url' => 'https://www.youtube.com/embed/--IcmZkvL0Q', 'title' => 'Fullmetal Alchemist: Brotherhood - Trailer', 'description' => 'Bande-annonce de Fullmetal Alchemist: Brotherhood.', 'is_external' => false],
            'dragon ball' => ['url' => 'https://www.youtube.com/embed/FHgm89hKpXU', 'title' => 'Dragon Ball Super - Trailer', 'description' => 'Trailer officiel de Dragon Ball Super.', 'is_external' => false],
            'steins gate' => ['url' => 'https://www.youtube.com/embed/uMYhjVwp0Fk', 'title' => 'Steins;Gate - Bande Annonce', 'description' => 'Trailer officiel de Steins;Gate.', 'is_external' => false],
        ];

        $animeLower = strtolower(trim($animeName));

        // Recherche dans la base locale
        if (isset($trailerDatabase[$animeLower])) {
            $trailer = $trailerDatabase[$animeLower];
            if (!empty($trailer['url'])) {
                return $trailer;
            }
        }

        // Recherche par correspondance partielle dans la base locale
        foreach ($trailerDatabase as $key => $trailerData) {
            if (strpos($animeLower, $key) !== false || strpos($key, $animeLower) !== false) {
                if (!empty($trailerData['url'])) {
                    return $trailerData;
                }
            }
        }

        // Recherche externe via Google + Gemini
        try {
            $searchResults = [];
            if ($this->googleSearchService) {
                $rawResults = $this->googleSearchService->searchAnimeInfo($animeName, 'trailer');
                foreach ($rawResults as $result) {
                    $source = parse_url($result['link'] ?? '', PHP_URL_HOST) ?? 'unknown';
                    $searchResults[] = [
                        'title' => $result['title'] ?? 'Unknown Title',
                        'link' => $result['link'] ?? '',
                        'snippet' => $result['snippet'] ?? '',
                        'source' => $source
                    ];
                }
            }

            $prompt = $this->buildTrailerPrompt($animeName, $searchResults);

            $geminiRequestPayload = [
                'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.5, 'topK' => 40, 'topP' => 0.95, 'maxOutputTokens' => 1024]
            ];

            $response = $this->httpClient->request('POST', $this->baseUrl . '?key=' . $this->apiKey, [
                'json' => $geminiRequestPayload,
                'timeout' => 8,
                'max_duration' => 15,
            ]);
            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray(false);
            error_log("Gemini API status code for trailer search: {$statusCode}");
            error_log('Gemini API response for trailer search: ' . json_encode($responseData));

            if ($statusCode !== 200) {
                error_log("Gemini API returned non-200 status: {$statusCode}");
                return $this->getFallbackTrailer($animeName);
            }

            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $generatedText = $responseData['candidates'][0]['content']['parts'][0]['text'];
                $trailerInfo = $this->parseGeminiTrailerResponse($generatedText, $searchResults, $animeName);

                if (!empty($trailerInfo) && !empty($trailerInfo['url'])) {
                    $trailerInfo['title'] = $trailerInfo['title'] ?? ('Trailer de ' . $animeName);
                    $trailerInfo['description'] = $trailerInfo['description'] ?? 'Bande-annonce trouvée via recherche externe.';
                    $trailerInfo['is_external'] = true;
                    return $trailerInfo;
                }
            }
            return $this->getFallbackTrailer($animeName);
        } catch (\Throwable $e) {
            error_log('Erreur lors de la recherche de trailer avec Google/Gemini: ' . $e->getMessage() . ' Anime: ' . $animeName);
            return $this->getFallbackTrailer($animeName);
        }
    }

    private function buildTrailerPrompt(string $animeName, array $searchResults): string
    {
        $searchResultsText = '';
        if (!empty($searchResults)) {
            foreach ($searchResults as $index => $result) {
                $title = $result['title'] ?? 'N/A';
                $link = $result['link'] ?? 'N/A';
                $snippet = $result['snippet'] ?? 'N/A';
                $source = $result['source'] ?? 'N/A';
                if (strpos($source, 'youtube.com') !== false || strpos($source, 'youtu.be') !== false) {
                    $searchResultsText .= "\n" . ($index + 1) . ". Titre: {$title}\n   Lien: {$link}\n   Description: {$snippet}\n   Source: {$source}";
                }
            }
        }
        if (empty($searchResultsText)) {
            $searchResultsText = "\nAucun résultat de recherche YouTube pertinent fourni. Essaye de trouver le trailer officiel YouTube pour '$animeName' en te basant sur ta connaissance générale si possible.";
        }

        return <<<EOT
Tu es un assistant expert en recherche de trailers d'anime.
L'anime recherché est : "{$animeName}".
Ta source prioritaire et unique pour les trailers est YouTube (liens de type youtube.com/watch ou youtu.be). Ignore toute autre source comme IMDB.
Résultats de recherche Google (filtrés pour YouTube si possible) :{$searchResultsText}
Extrait uniquement l'URL YouTube que tu considères comme étant le meilleur trailer officiel pour "{$animeName}". Si plusieurs sont valides, prends le plus récent et de la meilleure qualité.
Le format de ta réponse DOIT être un objet JSON unique et valide comme suit, et rien d'autre :
{"url":"[L'URL COMPLÈTE DU TRAILER YOUTUBE]","title":"[TITRE DESCRIPTIF DU TRAILER, ex: {$animeName} - Trailer Officiel YouTube VOSTFR]","description":"[COURTE DESCRIPTION DU TRAILER, ex: Découvrez la bande-annonce officielle YouTube de la saison 2 de {$animeName}.]"}
N'inclus aucun autre texte explicatif, commentaire, ou formatage en dehors de cet objet JSON. Si aucun trailer YouTube pertinent n'est trouvé, réponds avec un JSON vide: {}
EOT;
    }

    private function parseGeminiTrailerResponse(string $response, array $searchResults, string $animeNameForContext): array
    {
        $response = trim($response);
        if (strpos($response, '```json') !== false) {
            $response = preg_replace('/^```json\s*/', '', $response);
            $response = preg_replace('/\s*```$/', '', $response);
            $response = trim($response);
        }

        if (preg_match('/(\{.*?\})/s', $response, $matches)) {
            $jsonStr = $matches[1];
            $data = json_decode($jsonStr, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                if (empty($data)) {
                    return [];
                }
                if (isset($data['url']) && !empty($data['url'])) {
                    $formattedUrl = $this->formatYoutubeUrl($data['url']);
                    if (!empty($formattedUrl)) {
                        $data['url'] = $formattedUrl;
                        $data['title'] = $data['title'] ?? '';
                        $data['description'] = $data['description'] ?? '';
                        return $data;
                    }
                }
            } else {
                error_log("Erreur de décodage JSON pour la réponse du trailer: " . json_last_error_msg() . " - Réponse: " . $response);
                // Fallback: tenter d'extraire une URL YouTube directement
                if (preg_match('/https?:\/\/(?:www\.)?(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $response, $matchesUrl)) {
                    $youtubeUrlToFormat = 'https://www.youtube.com/watch?v=' . $matchesUrl[1];
                    $formattedUrl = $this->formatYoutubeUrl($youtubeUrlToFormat);
                    if (!empty($formattedUrl)) {
                        return [
                            'url' => $formattedUrl,
                            'title' => 'Trailer de ' . $animeNameForContext,
                            'description' => 'Bande-annonce YouTube trouvée par recherche automatique (URL extraite directement).'
                        ];
                    }
                }
            }
        }

        // Si aucune URL valide n'est trouvée, retourner un tableau vide
        return [];
    }

    private function formatYoutubeUrl(string $url): string
    {
        $url = trim($url);
        $videoId = null;

        if (preg_match('/^https:\/\/www\.youtube\.com\/embed\/([a-zA-Z0-9_-]{11})$/', $url)) {
            return $url;
        }

        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            $videoId = $matches[1];
        } elseif (preg_match('/youtube\.com\/embed\/([a-zAZ0-9_-]{11})/', $url, $matches)) {
            $videoId = $matches[1];
        } elseif (strpos($url, 'youtube.com/watch') !== false) {
            $queryString = parse_url($url, PHP_URL_QUERY);
            if ($queryString) {
                parse_str($queryString, $queryParams);
                if (isset($queryParams['v']) && preg_match('/^[a-zA-Z0-9_-]{11}$/', $queryParams['v'])) {
                    $videoId = $queryParams['v'];
                }
            }
        } elseif (preg_match('/youtube\.com\/(?:v|shorts)\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            $videoId = $matches[1];
        }

        if ($videoId) {
            return 'https://www.youtube.com/embed/' . $videoId;
        }

        return '';
    }

    private function getFallbackTrailer(string $animeName): array
    {
        return [
            'url' => '',
            'title' => "Trailer non disponible",
            'description' => "Aucun trailer trouvé pour \"$animeName\".",
            'is_external' => false
        ];
    }

    private function callGeminiAPI(array $animeData, int $numberOfQuestions = 5): array
    {
        $availableQuizTypes = ['personnages', 'univers', 'intrigue'];
        $selectedQuizType = $availableQuizTypes[array_rand($availableQuizTypes)];

        $userMessage = $this->buildPrompt($animeData, $selectedQuizType, $numberOfQuestions);

        $conversationContents = [
            ['role' => 'model', 'parts' => [['text' => "Here's a thinking process that leads to the anime quiz: [...]"]]],
            ['role' => 'user', 'parts' => [['text' => $userMessage]]]
        ];

        try {
            $response = $this->httpClient->request('POST', $this->baseUrl . '?key=' . $this->apiKey, [
                'json' => [
                    'contents' => $conversationContents,
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 8192,
                    ]
                ],
                'timeout' => 8,
                'max_duration' => 15,
            ]);
            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);
            error_log("Gemini API status code (quiz): {$statusCode}");
            error_log('Gemini API response (quiz): ' . json_encode($data));

            if ($statusCode !== 200) {
                error_log("Gemini API returned non-200 status: {$statusCode}");
                return [];
            }

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $generatedText = $data['candidates'][0]['content']['parts'][0]['text'];
                return $this->parseGeminiResponse($generatedText, $animeData);
            }
            return [];
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'appel à l\'API Gemini (quiz): ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());
            return [];
        }
    }

    private function parseGeminiResponse(string $response, array $animeData): array
    {
        $questions = [];

        $response = trim($response);
        error_log('Réponse brute de Gemini: ' . $response);

        try {
            if (preg_match('/\[\s*\{.*\}\s*\]/s', $response, $matches)) {
                $jsonText = $matches[0];
                $parsedQuestions = json_decode($jsonText, true);

                if (json_last_error() === JSON_ERROR_NONE && !empty($parsedQuestions)) {
                    error_log('Succès extraction JSON complet: ' . count($parsedQuestions) . ' questions');
                    return $this->normalizeQuestions($parsedQuestions, $animeData);
                }
            }

            if (preg_match_all('/```(?:json)?\s*(\{.*?\})\s*```|\{\s*"question".*?"correct_answer".*?\}/s', $response, $matches)) {
                foreach ($matches[0] as $match) {
                    $match = preg_replace('/```(?:json)?\s*(.*)\s*```/s', '$1', $match);
                    $question = json_decode($match, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $questions[] = $question;
                    }
                }

                if (!empty($questions)) {
                    error_log('Succès extraction questions individuelles JSON: ' . count($questions) . ' questions');
                    return $this->normalizeQuestions($questions, $animeData);
                }
            }

            if (preg_match_all('/(?:\d+\.|\*\*)\s*(.+?)\?\s*(?:\n|$)((?:.*?\bA\).*?\n.*?\bB\).*?\n.*?\bC\).*?\n.*?\bD\).*?)(?:\n\s*(?:Correct|Réponse|Answer).*?:\s*([A-D]))?/si', $response, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $questionText = trim($match[1]) . '?';
                    $optionsBlock = $match[2];
                    $correctLetter = isset($match[3]) ? trim($match[3]) : null;

                    preg_match_all('/([A-D])\)\s*(.+?)(?=\n[A-D]\)|$)/s', $optionsBlock, $optionMatches, PREG_SET_ORDER);

                    $options = [];
                    $optionMap = [];

                    foreach ($optionMatches as $optionMatch) {
                        $letter = trim($optionMatch[1]);
                        $optionText = trim($optionMatch[2]);
                        $options[] = $optionText;
                        $optionMap[$letter] = $optionText;
                    }

                    if (count($options) < 4) {
                        $options = ['Option A', 'Option B', 'Option C', 'Option D'];
                    }

                    $correctAnswer = null;
                    if ($correctLetter && isset($optionMap[$correctLetter])) {
                        $correctAnswer = $optionMap[$correctLetter];
                    } else {
                        preg_match('/(?:Correct|Réponse|Answer).*?:\s*(.+?)(?:\n|$)/i', $optionsBlock, $answerMatch);
                        if (!empty($answerMatch[1])) {
                            $correctAnswer = trim($answerMatch[1]);
                        } else {
                            foreach ($optionMap as $letter => $text) {
                                if (strpos($text, '*') !== false || strpos($text, '**') !== false) {
                                    $correctAnswer = preg_replace('/[\*\*]+/', '', $text);
                                    break;
                                }
                            }
                        }
                    }

                    if (empty($correctAnswer) && !empty($options)) {
                        $correctAnswer = $options[0];
                    }

                    $animeId = null;
                    foreach ($animeData as $anime) {
                        if (stripos($questionText, $anime['name']) !== false) {
                            $animeId = $anime['id'];
                            break;
                        }
                    }

                    if ($animeId === null && !empty($animeData)) {
                        $animeId = $animeData[0]['id'];
                    } else if ($animeId === null) {
                        $animeId = 1;
                    }

                    $questions[] = [
                        'id' => $animeId ?? 1,
                        'question' => $questionText,
                        'options' => $options,
                        'correct_answer' => $correctAnswer
                    ];
                }

                if (!empty($questions)) {
                    error_log('Succès extraction questions formatées: ' . count($questions) . ' questions');
                    return $this->normalizeQuestions($questions, $animeData);
                }
            }

            if (preg_match_all('/\b(?:Question|\d+\.)[:\s]\s*(.+?)\?/i', $response, $questionMatches)) {
                foreach ($questionMatches[1] as $index => $questionText) {
                    $animeId = null;
                    foreach ($animeData as $anime) {
                        if (stripos($questionText, $anime['name']) !== false) {
                            $animeId = $anime['id'];
                            break;
                        }
                    }

                    $options = ['Option A', 'Option B', 'Option C', 'Option D'];

                    $questions[] = [
                        'id' => $animeId ?? 1,
                        'question' => $questionText . '?',
                        'options' => $options,
                        'correct_answer' => $options[0]
                    ];
                }

                if (!empty($questions)) {
                    error_log('Extraction de secours: ' . count($questions) . ' questions');
                    return $this->normalizeQuestions($questions, $animeData);
                }
            }
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'analyse de la réponse Gemini: ' . $e->getMessage());
        }

        return $this->generateFallbackQuestions($animeData);
    }

    private function normalizeQuestions(array $questions, array $animeData): array
    {
        $normalized = [];
        foreach ($questions as $question) {
            if (!isset($question['question'])) {
                continue;
            }

            if (!isset($question['id']) || !is_numeric($question['id'])) {
                $question['id'] = null;
                foreach ($animeData as $anime) {
                    if (stripos($question['question'], $anime['name']) !== false) {
                        $question['id'] = $anime['id'];
                        break;
                    }
                }

                if ($question['id'] === null && !empty($animeData)) {
                    $question['id'] = $animeData[0]['id'];
                } else if ($question['id'] === null) {
                    $question['id'] = 1;
                }
            }

            if (!isset($question['options']) || !is_array($question['options'])) {
                $question['options'] = ['Option A', 'Option B', 'Option C', 'Option D'];
            }

            while (count($question['options']) < 4) {
                $question['options'][] = 'Option ' . count($question['options']);
            }

            if (count($question['options']) > 4) {
                $question['options'] = array_slice($question['options'], 0, 4);
            }

            if (!isset($question['correct_answer']) || !in_array($question['correct_answer'], $question['options'])) {
                $question['correct_answer'] = $question['options'][0];
            }

            if (!isset($question['image'])) {
                $animeId = $question['id'];
                foreach ($animeData as $anime) {
                    if ($anime['id'] == $animeId) {
                        $question['image'] = $anime['image'] ?? '/img/anime/default.jpg';
                        break;
                    }
                }
            }

            $normalized[] = $question;
        }

        return $normalized;
    }

    private function generateFallbackQuestions(array $animeData): array
    {
        $questions = [];
        $count = min(5, count($animeData));

        for ($i = 0; $i < $count; $i++) {
            if (!isset($animeData[$i]))
                continue;

            $anime = $animeData[$i];
            $id = $anime['id'];
            $name = $anime['name'];

            $questionTypes = [
                "Quel est le thème principal de l'anime '{$name}' ?",
                "Dans quel univers se déroule l'anime '{$name}' ?",
                "Quel personnage est le protagoniste de '{$name}' ?",
                "Quelle est l'intrigue principale de '{$name}' ?"
            ];

            $options = [
                "Une histoire d'amour et de découverte de soi",
                "Une aventure épique dans un monde fantastique",
                "Un combat contre des forces maléfiques",
                "Une quête pour retrouver un trésor perdu"
            ];

            $questions[] = [
                'id' => $id,
                'question' => $questionTypes[$i % count($questionTypes)],
                'options' => $options,
                'correct_answer' => $options[0]
            ];
        }

        return $questions;
    }

    private function buildPrompt(array $animeData, string $quizType, int $numberOfQuestions): string
    {
        $animeJson = json_encode($animeData, JSON_PRETTY_PRINT);

        $mainPrompt = <<<PROMPT
Je vais te présenter une liste d'animes avec leurs descriptions, genres et autres informations. Ta mission est de créer un quiz élaboré et créatif de {$numberOfQuestions} questions sur ces animes.

Je veux que tu procèdes par étapes :

1. **Analyse approfondie :** Pour chaque anime, analyse son titre, sa description, son genre et autres informations pour comprendre son univers.

2. **Création d'éléments imaginaires :** Invente des personnages, des arcs narratifs, des lieux, des pouvoirs ou des concepts importants qui seraient cohérents avec cet anime.

3. **Diversification des questions :** Crée une variété de questions comme :
   - Questions sur les personnages principaux et leurs caractéristiques/pouvoirs imaginés
   - Questions sur les événements clés ou arcs narratifs potentiels
   - Questions sur l'univers, les lieux ou les concepts
   - Questions sur les thèmes, relations entre personnages, ou symbolisme

4. **Équilibre de difficulté :** Mélange des questions faciles, moyennes et difficiles.

Voici les données sur les animes disponibles :
{$animeJson}

Format de réponse attendu (JSON) :
[
  {
    "id": ID_DE_L_ANIME,
    "question": "Question créative et spécifique sur l'anime [NOM] ?",
    "options": ["Option 1", "Option 2", "Option 3", "Option 4"],
    "correct_answer": "REPONSE_CORRECTE"
  },
  ...
]

PROMPT;

        switch ($quizType) {
            case 'personnages':
                return $mainPrompt . <<<PROMPT

Instructions spécifiques :
1. Concentre-toi uniquement sur les PERSONNAGES fictifs et créatifs que tu imagines pour chaque anime.
2. Pour chaque anime, invente des personnages principaux, antagonistes et secondaires avec des noms, caractéristiques et relations.
3. Les questions doivent porter sur : identités, pouvoirs/capacités, relations entre personnages, motivations, rôles dans l'histoire.
4. Assure-toi que chaque anime reçoive au moins une question détaillée sur ses personnages.
5. Base tes inventions sur le genre, la description et le ton de l'anime pour rester cohérent.

Réponds uniquement avec le JSON demandé, sans texte supplémentaire.  
PROMPT;

            case 'univers':
                return $mainPrompt . <<<PROMPT

Instructions spécifiques :
1. Concentre-toi sur la création d'UNIVERS fictifs détaillés pour chaque anime.
2. Pour chaque anime, invente des lieux, civilisations, systèmes magiques/technologiques, mythologie, histoire.
3. Les questions doivent porter sur : géographie imaginaire, règles du monde, organisations, artefacts puissants, créatures mythiques.
4. Développe au moins un aspect unique et mémorable de l'univers de chaque anime.
5. Assure-toi que l'univers créé correspond au genre et à l'atmosphère suggérée par la description de l'anime.

Réponds uniquement avec le JSON demandé, sans texte supplémentaire.
PROMPT;

            case 'intrigue':
                return $mainPrompt . <<<PROMPT

Instructions spécifiques :
1. Concentre-toi sur la création d'INTRIGUES et d'ARCS NARRATIFS fictifs pour chaque anime.
2. Pour chaque anime, invente des arcs majeurs, rebondissements, conflits importants, et développements d'histoire.
3. Les questions doivent porter sur : moments clés, rebondissements importants, révélations, développements de personnages, conclusions d'arcs.
4. Imagine des intrigues complexes avec début, milieu et fin qu'un anime pourrait réellement avoir.
5. Assure-toi que les intrigues inventées correspondent au genre et à l'atmosphère de l'anime.

Réponds uniquement avec le JSON demandé, sans texte supplémentaire.
PROMPT;

            case 'genres':
                return <<<PROMPT
Tu es un générateur de quiz sur les animes. Crée {$numberOfQuestions} questions intéressantes sur les genres d'anime à partir des données suivantes.
Voici les données sur les animes disponibles:
{$animeJson}

Format de réponse attendu (JSON):
[
  {
    "id": ID_DE_L_ANIME,
    "question": "À quel genre appartient l'anime [NOM] ?",
    "options": ["Option 1", "Option 2", "Option 3", "Option 4"],
    "correct_answer": "GENRE_CORRECT"
  },
  ...
]

Instructions:
1. La "correct_answer" doit correspondre exactement au genre réel de l'anime.
2. Les options doivent inclure la bonne réponse et 3 autres genres différents.
3. Assure-toi que les questions sont variées et que chaque anime n'est utilisé qu'une seule fois.
4. Réponds uniquement avec le JSON demandé, sans texte supplémentaire.
PROMPT;

            case 'ages':
                return <<<PROMPT
Tu es un générateur de quiz sur les animes. Crée {$numberOfQuestions} questions intéressantes sur les classifications d'âge des animes à partir des données suivantes.
Voici les données sur les animes disponibles:
{$animeJson}

Format de réponse attendu (JSON):
[
  {
    "id": ID_DE_L_ANIME,
    "question": "Quelle est la classification d'âge de l'anime [NOM] ?",
    "options": ["Option 1", "Option 2", "Option 3", "Option 4"],
    "correct_answer": "AGE_CORRECT"
  },
  ...
]

Instructions:
1. La "correct_answer" doit correspondre exactement à la classification d'âge réelle de l'anime.
2. Les options doivent inclue la bonne réponse et 3 autres classifications d'âge différentes.
3. Utilise exclusivement ces classifications d'âge pour les options: "Tous publics", "7+", "12+", "16+", "18+".
4. Assure-toi que les questions sont variées et que chaque anime n'est utilisé qu'une seule fois.
5. Réponds uniquement avec le JSON demandé, sans texte supplémentaire.
PROMPT;

            case 'statuts':
                return <<<PROMPT
Tu es un générateur de quiz sur les animes. Crée {$numberOfQuestions} questions intéressantes sur les statuts des animes à partir des données suivantes.
Voici les données sur les animes disponibles:
{$animeJson}

Format de réponse attendu (JSON):
[
  {
    "id": ID_DE_L_ANIME,
    "question": "Quel est le statut de l'anime [NOM] ?",
    "options": ["Option 1", "Option 2", "Option 3", "Option 4"],
    "correct_answer": "STATUT_CORRECT"
  },
  ...
]

Instructions:
1. La "correct_answer" doit correspondre exactement au statut réel de l'anime.
2. Les options doivent inclure la bonne réponse et 3 autres statuts différents.
3. Utilise exclusivement ces statuts pour les options: "En cours", "Terminé", "En pause", "Annulé".
4. Assure-toi que les questions sont variées et que chaque anime n'est utilisé qu'une seule fois.
5. Réponds uniquement avec le JSON demandé, sans texte supplémentaire.
PROMPT;

            default:
                return $mainPrompt . <<<PROMPT

Instructions spécifiques :
1. Crée un QUIZ MIXTE couvrant tous les aspects des animes :
   - Personnages imaginaires (héros, antagonistes, relations)
   - Éléments d'univers (lieux, concepts, règles du monde)
   - Intrigues fictives (arcs narratifs, événements clés)
   - Thèmes et symbolisme (messages, idées sous-jacentes)

2. Pour chaque anime, développe une question véritablement unique et créative qui exploite au maximum la description fournie.

3. Varie les types de questions : complétion de phrase, identification de concepts, questions "what if", déductions logiques.

4. Assure-toi que chaque question :
   - Est immersive et semble provenir d'un quiz officiel sur l'anime
   - A un niveau de détail qui montre une compréhension profonde de l'univers imaginé
   - Est formulée de manière engageante et amusante

Réponds uniquement avec le JSON demandé, sans texte supplémentaire.
PROMPT;
        }
    }

    public function generateContentDirect(string $prompt)
    {
        try {
            $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->apiKey, [
                'timeout' => 8,
                'max_duration' => 15,
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 4096,
                    ]
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);
            error_log("Gemini API status code (generateContent): {$statusCode}");
            error_log('Gemini API response (generateContent): ' . json_encode($data));

            if ($statusCode !== 200) {
                error_log("Gemini API returned non-200 status: {$statusCode}");
                return "";
            }

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }

            return "";
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'appel à l\'API Gemini (generateContent): ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());
            return "";
        }
    }

    private function generateCharacterQuizWithGoogleSearch(array $animes, int $numberOfQuestions = 5): array
    {
        shuffle($animes);
        $selectedAnimes = array_slice($animes, 0, min(count($animes), $numberOfQuestions));

        $quizQuestions = [];

        foreach ($selectedAnimes as $anime) {
            $searchResults = $this->googleSearchService->searchAnimeInfo($anime->getName(), 'characters');

            if (empty($searchResults)) {
                $searchResults = $this->googleSearchService->mockSearchAnimeInfo($anime->getName(), 'characters');
            }

            $characterNames = $this->extractCharacterNames($searchResults, $anime->getName());

            if (count($characterNames) < 4) {
                $mainCharacter = $this->guessMainCharacter($anime->getName());
                $characterNames = $this->getDefaultCharacters($anime->getName(), $mainCharacter);
            }

            $characterNames = array_unique($characterNames);
            while (count($characterNames) < 4) {
                $characterNames[] = "Personnage " . count($characterNames);
            }

            if (count($characterNames) > 4) {
                $characterNames = array_slice($characterNames, 0, 4);
            }

            $correctAnswer = $characterNames[0];

            shuffle($characterNames);

            $questionText = $this->generateCharacterQuestionText($anime->getName(), $correctAnswer, $searchResults);

            $quizQuestions[] = [
                'id' => $anime->getId(),
                'question' => $questionText,
                'image' => $anime->getImage(),
                'options' => $characterNames,
                'correct_answer' => $correctAnswer
            ];
        }

        return $quizQuestions;
    }

    private function generateCharacterQuestionText(string $animeName, string $correctCharacter, array $searchResults): string
    {
        $characterInfo = '';
        foreach ($searchResults as $result) {
            if (stripos($result['snippet'], $correctCharacter) !== false) {
                $characterInfo = $result['snippet'];
                break;
            }
        }

        $questionTypes = [
            "Qui est le personnage principal de l'anime \"{$animeName}\" ?",
            "Quel personnage de \"{$animeName}\" est connu pour être le protagoniste de la série ?",
            "Dans l'anime \"{$animeName}\", comment s'appelle le héros principal ?",
            "Parmi ces personnages, lequel est le protagoniste central de \"{$animeName}\" ?"
        ];

        if (stripos($animeName, 'naruto') !== false) {
            return "Quel personnage de \"{$animeName}\" aspire à devenir Hokage et possède le pouvoir du renard à neuf queues ?";
        } else if (stripos($animeName, 'one piece') !== false) {
            return "Quel est le capitaine de l'équipage du Chapeau de Paille dans \"{$animeName}\" ?";
        } else if (stripos($animeName, 'dragon ball') !== false) {
            return "Quel personnage de \"{$animeName}\" est un Saiyan élevé sur Terre et qui combat pour protéger la planète ?";
        } else if (stripos($animeName, 'attack on titan') !== false || stripos($animeName, 'shingeki') !== false) {
            return "Quel personnage de \"{$animeName}\" peut se transformer en Titan et jure de tuer tous les Titans après la mort de sa mère ?";
        } else if (stripos($animeName, 'hunter') !== false) {
            return "Quel personnage est le jeune protagoniste de \"{$animeName}\" qui cherche à retrouver son père, un Hunter légendaire ?";
        }

        return $questionTypes[array_rand($questionTypes)];
    }

    private function extractCharacterNames(array $searchResults, string $animeName): array
    {
        $characters = [];
        $animeNameLower = strtolower($animeName);

        foreach ($searchResults as $result) {
            $snippet = $result['snippet'];

            if (stripos($animeNameLower, 'naruto') !== false) {
                $this->extractMatchingNames($snippet, '/\b(Naruto|Sasuke|Sakura|Kakashi|Itachi|Hinata|Gaara|Jiraiya|Tsunade|Orochimaru|Madara|Pain|Obito)\b/', $characters);
            } else if (stripos($animeNameLower, 'one piece') !== false) {
                $this->extractMatchingNames($snippet, '/\b(Luffy|Zoro|Nami|Usopp|Sanji|Chopper|Robin|Franky|Brook|Jinbe|Ace|Sabo|Shanks)\b/', $characters);
            } else if (stripos($animeNameLower, 'dragon ball') !== false) {
                $this->extractMatchingNames($snippet, '/\b(Goku|Vegeta|Gohan|Piccolo|Trunks|Bulma|Krilin|Freezer|Cell|Majin Buu|Beerus|Whis)\b/', $characters);
            } else if (stripos($animeNameLower, 'attack on titan') !== false || stripos($animeNameLower, 'shingeki') !== false) {
                $this->extractMatchingNames($snippet, '/\b(Eren|Mikasa|Armin|Levi|Annie|Reiner|Bertholdt|Historia|Jean|Sasha|Connie|Erwin|Hange)\b/', $characters);
            } else if (stripos($animeNameLower, 'hunter') !== false) {
                $this->extractMatchingNames($snippet, '/\b(Gon|Killua|Kurapika|Leorio|Hisoka|Chrollo|Netero|Meruem|Biscuit|Kite|Illumi|Alluka)\b/', $characters);
            } else {
                preg_match_all('/(?:personnage[s]? principal|protagoniste|héros)\s+(?:\w+\s+)*?([A-Z][a-z]+(?:\s+[A-Z][a-z]+)?)/', $snippet, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $name) {
                        $characters[] = trim($name);
                    }
                }

                preg_match_all('/\b([A-Z][a-z]+(?:\s[A-Z][a-z]+)?)\b/', $snippet, $nameMatches);
                if (!empty($nameMatches[1])) {
                    foreach ($nameMatches[1] as $name) {
                        if (stripos($name, $animeNameLower) === false && strlen($name) > 2 && !in_array($name, ['Les', 'Dans', 'Pour', 'Avec'])) {
                            $characters[] = trim($name);
                        }
                    }
                }
            }
        }

        $characters = array_unique($characters);
        $characters = array_filter($characters, function ($name) {
            return strlen($name) > 2;
        });

        return array_values($characters);
    }

    private function extractMatchingNames(string $text, string $pattern, array &$characters): void
    {
        preg_match_all($pattern, $text, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $name) {
                $characters[] = trim($name);
            }
        } else {
            if (!empty($matches[0])) {
                foreach ($matches[0] as $name) {
                    $characters[] = trim($name);
                }
            }
        }
    }

    private function guessMainCharacter(string $animeName): string
    {
        $animeLower = strtolower($animeName);

        if (stripos($animeLower, 'naruto') !== false)
            return 'Naruto Uzumaki';
        if (stripos($animeLower, 'one piece') !== false)
            return 'Monkey D. Luffy';
        if (stripos($animeLower, 'dragon ball') !== false)
            return 'Son Goku';
        if (stripos($animeLower, 'attack on titan') !== false || stripos($animeLower, 'shingeki') !== false)
            return 'Eren Jaeger';
        if (stripos($animeLower, 'hunter') !== false)
            return 'Gon Freecss';
        if (stripos($animeLower, 'bleach') !== false)
            return 'Ichigo Kurosaki';
        if (stripos($animeLower, 'fullmetal') !== false)
            return 'Edward Elric';
        if (stripos($animeLower, 'death note') !== false)
            return 'Light Yagami';
        if (stripos($animeLower, 'tokyo ghoul') !== false)
            return 'Kaneki Ken';
        if (stripos($animeLower, 'sword art') !== false)
            return 'Kirito';
        if (stripos($animeLower, 'my hero') !== false || stripos($animeLower, 'academia') !== false)
            return 'Midoriya Izuku';

        return 'Personnage principal';
    }

    private function getDefaultCharacters(string $animeName, string $mainCharacter): array
    {
        $animeLower = strtolower($animeName);

        if (stripos($animeLower, 'naruto') !== false) {
            return ['Naruto Uzumaki', 'Sasuke Uchiha', 'Sakura Haruno', 'Kakashi Hatake'];
        }
        if (stripos($animeLower, 'one piece') !== false) {
            return ['Monkey D. Luffy', 'Roronoa Zoro', 'Nami', 'Sanji'];
        }
        if (stripos($animeLower, 'dragon ball') !== false) {
            return ['Son Goku', 'Vegeta', 'Gohan', 'Piccolo'];
        }
        if (stripos($animeLower, 'attack on titan') !== false || stripos($animeLower, 'shingeki') !== false) {
            return ['Eren Jaeger', 'Mikasa Ackerman', 'Armin Arlert', 'Levi Ackerman'];
        }
        if (stripos($animeLower, 'hunter') !== false) {
            return ['Gon Freecss', 'Killua Zoldyck', 'Kurapika', 'Leorio Paradinight'];
        }

        return [
            $mainCharacter,
            'Personnage secondaire',
            'Antagoniste principal',
            'Personnage de soutien'
        ];
    }

    private function generateQuizWithGoogleSearch(array $animes, string $quizType, int $numberOfQuestions = 5): array
    {
        error_log("Generating quiz with Google search for quizType: {$quizType}");
        shuffle($animes);
        $selectedAnimes = array_slice($animes, 0, min(count($animes), $numberOfQuestions));

        $quizQuestions = [];

        foreach ($selectedAnimes as $anime) {
            $searchResults = $this->googleSearchService->searchAnimeInfo($anime->getName(), $quizType);
            error_log("Search results for {$anime->getName()} and {$quizType}: " . json_encode($searchResults));

            if (empty($searchResults)) {
                $searchResults = $this->googleSearchService->mockSearchAnimeInfo($anime->getName(), $quizType);
                error_log("Using mock data for {$anime->getName()} and {$quizType}: " . json_encode($searchResults));
            }

            $animeInfo = [
                'id' => $anime->getId(),
                'name' => $anime->getName(),
                'description' => $anime->getDescrition(),
                'image' => $anime->getImage(),
                'age' => $anime->getAge(),
                'statut' => $anime->getStatut()
            ];

            if ($anime->getGenre_id()) {
                $animeInfo['genre'] = $anime->getGenre_id()->getName();
            }

            $options = [];
            $correctAnswer = '';
            $questionText = '';

            switch ($quizType) {
                case 'genres':
                    $options = $this->getGenreOptions($anime);
                    $correctAnswer = $anime->getGenre_id() ? $anime->getGenre_id()->getName() : 'Non catégorisé';
                    $questionText = $this->generateGenreQuestionText($anime->getName(), $searchResults);
                    break;

                case 'ages':
                    $options = ['Tous publics', '7+', '12+', '16+', '18+'];
                    $correctAnswer = $anime->getAge();
                    $questionText = $this->generateAgeQuestionText($anime->getName(), $searchResults);
                    break;

                case 'statuts':
                    $options = ['En cours', 'Terminé', 'En pause', 'Annulé'];
                    $correctAnswer = $anime->getStatut();
                    $questionText = $this->generateStatusQuestionText($anime->getName(), $searchResults);
                    break;

                case 'universe':
                    $universeElements = $this->extractUniverseElements($searchResults, $anime->getName());
                    error_log("Extracted universe elements for {$anime->getName()}: " . json_encode($universeElements));
                    if (count($universeElements) < 4) {
                        $universeElements = $this->getDefaultUniverseElements($anime->getName());
                        error_log("Using default universe elements for {$anime->getName()}: " . json_encode($universeElements));
                    }
                    $universeElements = array_unique($universeElements);
                    while (count($universeElements) < 4) {
                        $universeElements[] = "Élément " . count($universeElements);
                    }
                    if (count($universeElements) > 4) {
                        $universeElements = array_slice($universeElements, 0, 4);
                    }
                    $correctAnswer = $universeElements[0];
                    shuffle($universeElements);
                    $options = $universeElements;
                    $questionText = $this->generateUniverseQuestionText($anime->getName(), $correctAnswer, $searchResults);
                    error_log("Universe question for {$anime->getName()}: {$questionText}");
                    break;

                case 'plot':
                    $plotPoints = $this->extractPlotPoints($searchResults, $anime->getName());
                    error_log("Extracted plot points for {$anime->getName()}: " . json_encode($plotPoints));
                    if (count($plotPoints) < 4) {
                        $plotPoints = $this->getDefaultPlotPoints($anime->getName());
                        error_log("Using default plot points for {$anime->getName()}: " . json_encode($plotPoints));
                    }
                    $plotPoints = array_unique($plotPoints);
                    while (count($plotPoints) < 4) {
                        $plotPoints[] = "Événement " . count($plotPoints);
                    }
                    if (count($plotPoints) > 4) {
                        $plotPoints = array_slice($plotPoints, 0, 4);
                    }
                    $correctAnswer = $plotPoints[0];
                    shuffle($plotPoints);
                    $options = $plotPoints;
                    $questionText = $this->generatePlotQuestionText($anime->getName(), $correctAnswer, $searchResults);
                    error_log("Plot question for {$anime->getName()}: {$questionText}");
                    break;
            }

            if (!in_array($correctAnswer, $options)) {
                $options[0] = $correctAnswer;
            }

            shuffle($options);

            $quizQuestions[] = [
                'id' => $anime->getId(),
                'question' => $questionText,
                'image' => $anime->getImage(),
                'options' => $options,
                'correct_answer' => $correctAnswer
            ];
        }

        error_log("Final quiz questions for {$quizType}: " . json_encode($quizQuestions));
        return $quizQuestions;
    }

    private function extractUniverseElements(array $searchResults, string $animeName): array
    {
        $elements = [];
        $animeNameLower = strtolower($animeName);

        foreach ($searchResults as $result) {
            $snippet = $result['snippet'];

            if (stripos($animeNameLower, 'naruto') !== false) {
                $this->extractMatchingElements($snippet, '/\b(Konoha|Chakra|Bijuu|Village Caché|Ninjutsu|Sharingan)\b/', $elements);
            } else if (stripos($animeNameLower, 'one piece') !== false) {
                $this->extractMatchingElements($snippet, '/\b(Grand Line|Fruit du Démon|Marine|Empereur|Ponéglyphe|Nouveau Monde)\b/', $elements);
            } else if (stripos($animeNameLower, 'attack on titan') !== false || stripos($animeNameLower, 'shingeki') !== false) {
                $this->extractMatchingElements($snippet, '/\b(Mur Maria|Eldéens|Mahr|Équipement Tridimensionnel|Titan Colossal|Bataillon d\'Exploration)\b/', $elements);
            } else if (stripos($animeNameLower, 'hunter') !== false) {
                $this->extractMatchingElements($snippet, '/\b(Nen|Association des Hunters|Continent Sombre|Fourmis-Chimères|Greed Island|York Shin)\b/', $elements);
            } else if (stripos($animeNameLower, 'dragon ball') !== false) {
                $this->extractMatchingElements($snippet, '/\b(Super Saiyan|Ki|Namek|Univers 7|Dragon Balls|Ultra Instinct)\b/', $elements);
            } else {
                preg_match_all('/(?:lieu|monde|univers|système|artefact|organisation)\s+(?:\w+\s+)*?([A-Z][a-z]+(?:\s+[A-Z][a-z]+)?)/', $snippet, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $element) {
                        $elements[] = trim($element);
                    }
                }

                preg_match_all('/\b([A-Z][a-z]+(?:\s[A-Z][a-z]+)?)\b/', $snippet, $elementMatches);
                if (!empty($elementMatches[1])) {
                    foreach ($elementMatches[1] as $element) {
                        if (stripos($element, $animeNameLower) === false && strlen($element) > 2 && !in_array($element, ['Les', 'Dans', 'Pour', 'Avec'])) {
                            $elements[] = trim($element);
                        }
                    }
                }
            }
        }

        $elements = array_unique($elements);
        $elements = array_filter($elements, function ($element) {
            return strlen($element) > 2;
        });

        return array_values($elements);
    }

    private function extractMatchingElements(string $text, string $pattern, array &$elements): void
    {
        preg_match_all($pattern, $text, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $element) {
                $elements[] = trim($element);
            }
        } else {
            if (!empty($matches[0])) {
                foreach ($matches[0] as $element) {
                    $elements[] = trim($element);
                }
            }
        }
    }

    private function getDefaultUniverseElements(string $animeName): array
    {
        $animeLower = strtolower($animeName);

        if (stripos($animeLower, 'naruto') !== false) {
            return ['Konoha', 'Chakra', 'Bijuu', 'Ninjutsu'];
        }
        if (stripos($animeLower, 'one piece') !== false) {
            return ['Grand Line', 'Fruit du Démon', 'Marine', 'Ponéglyphe'];
        }
        if (stripos($animeLower, 'attack on titan') !== false || stripos($animeLower, 'shingeki') !== false) {
            return ['Mur Maria', 'Eldéens', 'Mahr', 'Bataillon d\'Exploration'];
        }
        if (stripos($animeLower, 'hunter') !== false) {
            return ['Nen', 'Association des Hunters', 'Continent Sombre', 'Greed Island'];
        }
        if (stripos($animeLower, 'dragon ball') !== false) {
            return ['Super Saiyan', 'Ki', 'Namek', 'Univers 7'];
        }

        return [
            'Monde principal',
            'Système de pouvoir',
            'Organisation centrale',
            'Artefact légendaire'
        ];
    }

    private function generateUniverseQuestionText(string $animeName, string $correctElement, array $searchResults): string
    {
        $elementInfo = '';
        foreach ($searchResults as $result) {
            if (stripos($result['snippet'], $correctElement) !== false) {
                $elementInfo = $result['snippet'];
                break;
            }
        }

        $questionTypes = [
            "Quel est un élément clé de l'univers de l'anime \"{$animeName}\" ?",
            "Dans l'univers de \"{$animeName}\", qu'est-ce qui est essentiel au monde ?",
            "Quel concept est central dans l'univers de \"{$animeName}\" ?",
            "Parmi ces éléments, lequel est emblématique de l'univers de \"{$animeName}\" ?"
        ];

        if (stripos($animeName, 'naruto') !== false && stripos($correctElement, 'Konoha') !== false) {
            return "Dans quel village principal se déroule une grande partie de l'histoire de \"{$animeName}\" ?";
        } else if (stripos($animeName, 'one piece') !== false && stripos($correctElement, 'Grand Line') !== false) {
            return "Quelle route maritime est au cœur des aventures de \"{$animeName}\" ?";
        } else if (stripos($animeName, 'attack on titan') !== false && stripos($correctElement, 'Mur Maria') !== false) {
            return "Quel mur est le plus extérieur dans l'univers de \"{$animeName}\" ?";
        } else if (stripos($animeName, 'hunter') !== false && stripos($correctElement, 'Nen') !== false) {
            return "Quel système de combat est fondamental dans l'univers de \"{$animeName}\" ?";
        } else if (stripos($animeName, 'dragon ball') !== false && stripos($correctElement, 'Super Saiyan') !== false) {
            return "Quelle transformation est emblématique des guerriers de \"{$animeName}\" ?";
        }

        return $questionTypes[array_rand($questionTypes)];
    }

    private function extractPlotPoints(array $searchResults, string $animeName): array
    {
        $plotPoints = [];
        $animeNameLower = strtolower($animeName);

        foreach ($searchResults as $result) {
            $snippet = $result['snippet'];

            if (stripos($animeNameLower, 'naruto') !== false) {
                $this->extractMatchingPlotPoints($snippet, '/\b(Hokage|Kyûbi|Akatsuki|Quatrième Guerre Ninja|Sasuke vengeance|examen Chunin)\b/', $plotPoints);
            } else if (stripos($animeNameLower, 'one piece') !== false) {
                $this->extractMatchingPlotPoints($snippet, '/\b(One Piece trésor|Guerre au Sommet|Siècle Oublié|Alabasta|Dressrosa|Wano Kuni)\b/', $plotPoints);
            } else if (stripos($animeNameLower, 'attack on titan') !== false || stripos($animeNameLower, 'shingeki') !== false) {
                $this->extractMatchingPlotPoints($snippet, '/\b(Mur Maria chute|Titan Colossal|Mahr vs Eldia|Eren transformation|Grand Terrassement)\b/', $plotPoints);
            } else if (stripos($animeNameLower, 'hunter') !== false) {
                $this->extractMatchingPlotPoints($snippet, '/\b(Examen Hunter|Fourmis-Chimères|Greed Island|York Shin|Meruem combat|Kurapika vengeance)\b/', $plotPoints);
            } else if (stripos($animeNameLower, 'dragon ball') !== false) {
                $this->extractMatchingPlotPoints($snippet, '/\b(Freezer Namek|Cell Games|Majin Buu|Tournoi du Pouvoir|Ultra Instinct|Black Goku)\b/', $plotPoints);
            } else {
                preg_match_all('/(?:événement|arc|rebondissement|intrigue)\s+(?:\w+\s+)*?([A-Z][a-z]+(?:\s+[A-Z][a-z]+)?)/', $snippet, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $point) {
                        $plotPoints[] = trim($point);
                    }
                }

                preg_match_all('/\b([A-Z][a-z]+(?:\s[A-Z][a-z]+)?)\b/', $snippet, $pointMatches);
                if (!empty($pointMatches[1])) {
                    foreach ($pointMatches[1] as $point) {
                        if (stripos($point, $animeNameLower) === false && strlen($point) > 2 && !in_array($point, ['Les', 'Dans', 'Pour', 'Avec'])) {
                            $plotPoints[] = trim($point);
                        }
                    }
                }
            }
        }

        $plotPoints = array_unique($plotPoints);
        $plotPoints = array_filter($plotPoints, function ($point) {
            return strlen($point) > 2;
        });

        return array_values($plotPoints);
    }

    private function extractMatchingPlotPoints(string $text, string $pattern, array &$plotPoints): void
    {
        preg_match_all($pattern, $text, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $point) {
                $plotPoints[] = trim($point);
            }
        } else {
            if (!empty($matches[0])) {
                foreach ($matches[0] as $point) {
                    $plotPoints[] = trim($point);
                }
            }
        }
    }

    private function getDefaultPlotPoints(string $animeName): array
    {
        $animeLower = strtolower($animeName);

        if (stripos($animeLower, 'naruto') !== false) {
            return ['Hokage', 'Kyûbi', 'Akatsuki', 'Quatrième Guerre Ninja'];
        }
        if (stripos($animeLower, 'one piece') !== false) {
            return ['One Piece trésor', 'Guerre au Sommet', 'Siècle Oublié', 'Alabasta'];
        }
        if (stripos($animeLower, 'attack on titan') !== false || stripos($animeLower, 'shingeki') !== false) {
            return ['Mur Maria chute', 'Titan Colossal', 'Mahr vs Eldia', 'Eren transformation'];
        }
        if (stripos($animeLower, 'hunter') !== false) {
            return ['Examen Hunter', 'Fourmis-Chimères', 'Greed Island', 'York Shin'];
        }
        if (stripos($animeLower, 'dragon ball') !== false) {
            return ['Freezer Namek', 'Cell Games', 'Majin Buu', 'Tournoi du Pouvoir'];
        }

        return [
            'Conflit principal',
            'Rebondissement majeur',
            'Quête centrale',
            'Bataille finale'
        ];
    }

    private function generatePlotQuestionText(string $animeName, string $correctPlotPoint, array $searchResults): string
    {
        $plotInfo = '';
        foreach ($searchResults as $result) {
            if (stripos($result['snippet'], $correctPlotPoint) !== false) {
                $plotInfo = $result['snippet'];
                break;
            }
        }

        $questionTypes = [
            "Quel est un moment clé de l'intrigue de l'anime \"{$animeName}\" ?",
            "Dans l'histoire de \"{$animeName}\", quel événement est central ?",
            "Quel arc narratif est emblématique de \"{$animeName}\" ?",
            "Parmi ces événements, lequel est un tournant dans l'intrigue de \"{$animeName}\" ?"
        ];

        if (stripos($animeName, 'naruto') !== false && stripos($correctPlotPoint, 'Hokage') !== false) {
            return "Quel est l'objectif principal de Naruto dans l'anime \"{$animeName}\" ?";
        } else if (stripos($animeName, 'one piece') !== false && stripos($correctPlotPoint, 'One Piece trésor') !== false) {
            return "Quel trésor légendaire Luffy cherche-t-il dans \"{$animeName}\" ?";
        } else if (stripos($animeName, 'attack on titan') !== false && stripos($correctPlotPoint, 'Mur Maria chute') !== false) {
            return "Quel événement marque le début de l'intrigue de \"{$animeName}\" ?";
        } else if (stripos($animeName, 'hunter') !== false && stripos($correctPlotPoint, 'Examen Hunter') !== false) {
            return "Quel événement permet à Gon de devenir un Hunter dans \"{$animeName}\" ?";
        } else if (stripos($animeName, 'dragon ball') !== false && stripos($correctPlotPoint, 'Freezer Namek') !== false) {
            return "Quel arc narratif voit Goku affronter Freezer sur la planète Namek dans \"{$animeName}\" ?";
        }

        return $questionTypes[array_rand($questionTypes)];
    }

    private function getGenreOptions(object $currentAnime): array
    {
        $commonGenres = [
            'Action',
            'Aventure',
            'Comédie',
            'Drame',
            'Fantasy',
            'Horreur',
            'Mecha',
            'Musique',
            'Mystère',
            'Psychologique',
            'Romance',
            'Science-fiction',
            'Slice of Life',
            'Sports',
            'Surnaturel',
            'Thriller',
            'Historique',
            'Seinen',
            'Shonen',
            'Shojo'
        ];

        $currentGenre = ($currentAnime->getGenre_id()) ? $currentAnime->getGenre_id()->getName() : 'Non catégorisé';

        $otherGenres = array_filter($commonGenres, function ($genre) use ($currentGenre) {
            return $genre !== $currentGenre;
        });
        shuffle($otherGenres);
        $selectedGenres = array_slice($otherGenres, 0, 3);

        $options = array_merge([$currentGenre], $selectedGenres);

        return $options;
    }

    private function generateGenreQuestionText(string $animeName, array $searchResults): string
    {
        $questionTypes = [
            "À quel genre appartient l'anime \"{$animeName}\" ?",
            "Dans quelle catégorie classerait-on l'anime \"{$animeName}\" ?",
            "Quel est le genre principal de l'anime \"{$animeName}\" ?",
            "Parmi ces genres, lequel caractérise le mieux \"{$animeName}\" ?"
        ];

        return $questionTypes[array_rand($questionTypes)];
    }

    private function generateAgeQuestionText(string $animeName, array $searchResults): string
    {
        $questionTypes = [
            "Quelle est la classification d'âge de l'anime \"{$animeName}\" ?",
            "Pour quel public l'anime \"{$animeName}\" est-il principalement destiné ?",
            "Quelle restriction d'âge s'applique à l'anime \"{$animeName}\" ?",
            "À partir de quel âge l'anime \"{$animeName}\" est-il recommandé ?"
        ];

        return $questionTypes[array_rand($questionTypes)];
    }

    private function generateStatusQuestionText(string $animeName, array $searchResults): string
    {
        $questionTypes = [
            "Quel est le statut actuel de l'anime \"{$animeName}\" ?",
            "La diffusion de l'anime \"{$animeName}\" est-elle terminée ou toujours en cours ?",
            "Dans quel état se trouve la production de l'anime \"{$animeName}\" ?",
            "Concernant sa diffusion, l'anime \"{$animeName}\" est-il : "
        ];

        return $questionTypes[array_rand($questionTypes)];
    }
}