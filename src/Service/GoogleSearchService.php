<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GoogleSearchService
{
    private string $apiKey;
    private string $searchEngineId;
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $params)
    {
        // Récupération des variables d'environnement
        $this->apiKey = $params->get('google_search_api_key');
        $this->searchEngineId = $params->get('google_search_engine_id');
        $this->httpClient = $httpClient;
    }

    /**
     * Effectue une recherche Google pour un anime spécifique ou une requête générale
     */
    public function searchAnimeInfo(string $query, string $searchType = 'general', int $numResults = 5): array
    {
        // Si la requête est vide, retourner directement la version simulée
        if (empty(trim($query))) {
            error_log('Query is empty, using mock data');
            return $this->mockSearchAnimeInfo('Unknown anime', $searchType);
        }

        // Construire la requête en fonction du type de recherche
        switch ($searchType) {
            case 'characters':
                $query = "personnages principaux anime {$query}";
                break;
            case 'universe':
                $query = "univers monde setting pouvoirs anime {$query}";
                break;
            case 'plot':
                $query = "intrigue histoire résumé anime {$query}";
                break;
            case 'genres':
                $query = "genre categorie type anime {$query}";
                break;
            case 'ages':
                $query = "classification âge public cible anime {$query}";
                break;
            case 'statuts':
                $query = "statut état diffusion anime {$query} terminé en cours";
                break;
            case 'trailer':
                $query = "official trailer anime {$query} site:youtube.com | site:imdb.com";
                break;
            default:
                $query = "anime {$query} {$searchType}";
        }

        error_log("Google Search Query: {$query}");
        error_log("SearchEngineID: {$this->searchEngineId}");
        error_log("API Key (first 10 chars): " . substr($this->apiKey, 0, 10) . '...');

        try {
            // URL de l'API Google Custom Search
            $url = 'https://www.googleapis.com/customsearch/v1';

            // Paramètres de la recherche
            $params = [
                'key' => $this->apiKey,
                'cx' => $this->searchEngineId,
                'q' => $query,
                'num' => min($numResults, 10), // Limite à 10 résultats max
                'safe' => 'active' // SafeSearch activé
            ];

            // Construction de l'URL avec les paramètres
            $url .= '?' . http_build_query($params);

            error_log("URL de recherche: {$url}");

            // Exécution de la requête avec timeout explicite
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 5, // Timeout de 5 secondes
                'max_duration' => 10, // Durée maximale totale de 10 secondes
            ]);
            $statusCode = $response->getStatusCode();
            error_log("Status code: {$statusCode}");

            $data = $response->toArray();
            error_log("Response received: " . json_encode($data));

            // Extraction des informations utiles
            $results = [];
            if (isset($data['items']) && !empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $results[] = [
                        'title' => $item['title'] ?? '',
                        'link' => $item['link'] ?? '',
                        'snippet' => $item['snippet'] ?? '',
                        'source' => $item['displayLink'] ?? ''
                    ];
                }
                error_log("Found {$searchType} results: " . count($results));
            } else {
                error_log("No search results found in the response");
                if (isset($data['error'])) {
                    error_log("Google API Error: " . json_encode($data['error']));
                }
            }

            // Si on n'a pas de résultats, utiliser les données simulées
            if (empty($results)) {
                error_log("Using mock data as fallback");
                return $this->mockSearchAnimeInfo($query, $searchType);
            }

            return $results;
        } catch (\Exception $e) {
            error_log('Exception lors de la recherche Google: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());
            error_log('Full response (if available): ' . ($response->getContent(false) ?? 'No response'));
            // Utiliser les données simulées en cas d'erreur
            return $this->mockSearchAnimeInfo($query, $searchType);
        }
    }

    /**
     * Simule une recherche Google lorsque l'API n'est pas disponible ou configurée
     */
    public function mockSearchAnimeInfo(string $animeName, string $searchType = 'characters'): array
    {
        // Création de données factices adaptées au type de recherche
        switch ($searchType) {
            case 'characters':
                // Personnages dynamiques spécifiques à l'anime
                if (stripos($animeName, 'naruto') !== false) {
                    return [
                        [
                            'title' => "Naruto Uzumaki et les personnages de Naruto - Guide officiel",
                            'link' => "https://myanimelist.net/anime/20/Naruto",
                            'snippet' => "Naruto Uzumaki est le protagoniste principal, un ninja hyperactif et déterminé qui rêve de devenir Hokage. D'autres personnages clés incluent Sasuke Uchiha, Sakura Haruno et Kakashi Hatake.",
                            'source' => 'myanimelist.net'
                        ],
                        [
                            'title' => "Les personnages de l'univers Naruto - Konoha et au-delà",
                            'link' => "https://www.animenewsnetwork.com/encyclopedia/anime.php?id=1825",
                            'snippet' => "L'univers de Naruto comprend de nombreux ninjas de différents villages. Les antagonistes notables incluent l'Akatsuki, Orochimaru et Madara Uchiha. Chaque personnage possède des techniques ninjas uniques.",
                            'source' => 'animenewsnetwork.com'
                        ],
                        [
                            'title' => "Évolution des personnages dans Naruto et Naruto Shippuden",
                            'link' => "https://naruto.fandom.com/wiki/Narutopedia",
                            'snippet' => "Les personnages de Naruto évoluent considérablement au fil des séries. Naruto maîtrise le mode Sage et l'énergie du Kyubi, tandis que Sasuke développe son Sharingan et cherche vengeance pour son clan.",
                            'source' => 'naruto.fandom.com'
                        ]
                    ];
                } elseif (stripos($animeName, 'one piece') !== false) {
                    return [
                        [
                            'title' => "L'équipage du Chapeau de Paille - Personnages principaux de One Piece",
                            'link' => "https://myanimelist.net/anime/21/One_Piece",
                            'snippet' => "L'équipage de Luffy comprend Zoro le bretteur, Nami la navigatrice, Usopp le tireur d'élite, Sanji le cuisinier, Chopper le médecin, Robin l'archéologue, Franky le charpentier, Brook le musicien et Jinbe le timonier.",
                            'source' => 'myanimelist.net'
                        ],
                        [
                            'title' => "Les pirates et marines de One Piece - Personnages influents",
                            'link' => "https://www.animenewsnetwork.com/encyclopedia/anime.php?id=836",
                            'snippet' => "One Piece présente de nombreux personnages importants comme les Empereurs (Kaido, Big Mom, Shanks, Barbe Noire), l'Amiral en chef de la Marine, et les différents Grands Corsaires qui forment l'équilibre du monde.",
                            'source' => 'animenewsnetwork.com'
                        ],
                        [
                            'title' => "Alliés et ennemis majeurs dans le voyage de One Piece",
                            'link' => "https://onepiece.fandom.com/wiki/One_Piece_Wiki",
                            'snippet' => "Au cours de son voyage, Luffy rencontre de nombreux alliés comme Vivi, Law, et les membres de la Grande Flotte du Chapeau de Paille. Il affronte également des adversaires redoutables comme Crocodile, Rob Lucci et Doflamingo.",
                            'source' => 'onepiece.fandom.com'
                        ]
                    ];
                } elseif (stripos($animeName, 'attack on titan') !== false || stripos($animeName, 'shingeki no kyojin') !== false) {
                    return [
                        [
                            'title' => "Eren Jaeger et les personnages principaux de L'Attaque des Titans",
                            'link' => "https://myanimelist.net/anime/16498/Shingeki_no_Kyojin",
                            'snippet' => "Les personnages principaux incluent Eren Jaeger, dont la mère a été dévorée par un Titan, ses amis d'enfance Mikasa Ackerman et Armin Arlert, ainsi que le Capitaine Levi, considéré comme le soldat le plus fort de l'humanité.",
                            'source' => 'myanimelist.net'
                        ],
                        [
                            'title' => "Les différentes unités militaires dans L'Attaque des Titans",
                            'link' => "https://www.animenewsnetwork.com/encyclopedia/anime.php?id=14950",
                            'snippet' => "L'armée humaine est divisée en trois branches: le Bataillon d'exploration (qui comprend Eren, Mikasa, Armin et Levi), les Brigades spéciales, et les Garnisons. Chaque unité a des soldats aux personnages bien définis.",
                            'source' => 'animenewsnetwork.com'
                        ],
                        [
                            'title' => "Les Titans et leurs porteurs dans Shingeki no Kyojin",
                            'link' => "https://attackontitan.fandom.com/wiki/Attack_on_Titan_Wiki",
                            'snippet' => "L'histoire révèle que certains humains peuvent se transformer en Titans. Les porteurs de Titans incluent Eren (Titan Assaillant), Reiner (Titan Cuirassé), Annie (Titan Femelle), et d'autres personnages qui jouent des rôles cruciaux.",
                            'source' => 'attackontitan.fandom.com'
                        ]
                    ];
                } elseif (stripos($animeName, 'hunter') !== false) {
                    return [
                        [
                            'title' => "Les Hunters principaux de Hunter x Hunter",
                            'link' => "https://myanimelist.net/anime/11061/Hunter_x_Hunter_2011",
                            'snippet' => "Gon Freecss est le protagoniste qui souhaite devenir Hunter pour retrouver son père. Il se lie d'amitié avec Killua Zoldyck (ex-assassin), Kurapika (dernier survivant du clan Kurta) et Leorio (aspirant médecin).",
                            'source' => 'myanimelist.net'
                        ],
                        [
                            'title' => "Les antagonistes majeurs dans Hunter x Hunter",
                            'link' => "https://www.animenewsnetwork.com/encyclopedia/anime.php?id=13271",
                            'snippet' => "La Brigade Fantôme, dirigée par Chrollo Lucilfer, est responsable du massacre du clan de Kurapika. Hisoka, un magicien mystérieux, et Meruem, le roi des Fourmières-Chimères, sont d'autres antagonistes importants.",
                            'source' => 'animenewsnetwork.com'
                        ],
                        [
                            'title' => "Le système de Nen et ses utilisateurs dans Hunter x Hunter",
                            'link' => "https://hunterxhunter.fandom.com/wiki/Hunterpedia",
                            'snippet' => "Le Nen est le système de combat de Hunter x Hunter. Gon utilise le Renforcement (jajanken), Killua la Transformation (électricité), Kurapika la Matérialisation (chaînes) et l'Spécialisation (yeux écarlates), et Hisoka la Transformation (Bungee Gum).",
                            'source' => 'hunterxhunter.fandom.com'
                        ]
                    ];
                } elseif (stripos($animeName, 'dragon ball') !== false) {
                    return [
                        [
                            'title' => "Les Saiyans et personnages principaux de Dragon Ball",
                            'link' => "https://myanimelist.net/anime/813/Dragon_Ball_Z",
                            'snippet' => "Son Goku est le protagoniste principal, un Saiyan envoyé sur Terre enfant. Les autres personnages importants incluent Vegeta (prince des Saiyans), Gohan et Goten (fils de Goku), Piccolo (Namek) et Bulma (génie scientifique).",
                            'source' => 'myanimelist.net'
                        ],
                        [
                            'title' => "Les vilains de la saga Dragon Ball",
                            'link' => "https://www.animenewsnetwork.com/encyclopedia/anime.php?id=165",
                            'snippet' => "Dragon Ball présente de nombreux antagonistes puissants: Freezer (qui a détruit la planète des Saiyans), Cell (bio-androïde créé à partir de cellules des combattants), Majin Buu (entité magique ancienne) et récemment Beerus et Jiren.",
                            'source' => 'animenewsnetwork.com'
                        ],
                        [
                            'title' => "Transformations et techniques des personnages de Dragon Ball",
                            'link' => "https://dragonball.fandom.com/wiki/Dragon_Ball_Wiki",
                            'snippet' => "Les Saiyans peuvent se transformer en plusieurs formes Super Saiyan. Goku maîtrise l'Ultra Instinct, Vegeta l'Ultra Ego. Les techniques populaires incluent le Kamehame-Ha de Goku, le Final Flash de Vegeta et le Masenko de Gohan.",
                            'source' => 'dragonball.fandom.com'
                        ]
                    ];
                }

                // Pour les autres animes, générer des informations génériques
                return [
                    [
                        'title' => "Les personnages principaux de {$animeName} - MyAnimeList",
                        'link' => "https://myanimelist.net/anime/search?q={$animeName}",
                        'snippet' => "Découvrez les personnages importants de l'anime {$animeName}, incluant le protagoniste principal et ses alliés qui l'accompagnent dans son aventure. Chacun a des motivations et capacités uniques.",
                        'source' => 'myanimelist.net'
                    ],
                    [
                        'title' => "{$animeName} - Analyse des personnages - Anime News Network",
                        'link' => "https://www.animenewsnetwork.com/encyclopedia/anime.php?search={$animeName}",
                        'snippet' => "Les relations entre les personnages de {$animeName} sont complexes et évoluent au cours de l'histoire. Les protagonistes font face à des défis qui testent leurs liens et révèlent leur véritable nature.",
                        'source' => 'animenewsnetwork.com'
                    ],
                    [
                        'title' => "Antagonistes et personnages secondaires dans {$animeName} - Wiki",
                        'link' => "https://anime.fandom.com/wiki/{$animeName}",
                        'snippet' => "Les antagonistes de {$animeName} sont tout aussi bien développés que les héros. Leurs histoires d'origine et motivations sont souvent nuancées, ce qui crée une intrigue plus riche et des conflits captivants.",
                        'source' => 'anime.fandom.com'
                    ]
                ];

            case 'universe':
                if (stripos($animeName, 'naruto') !== false) {
                    return [
                        [
                            'title' => "Le monde des ninjas - L'univers de Naruto",
                            'link' => "https://naruto.fandom.com/wiki/Narutopedia",
                            'snippet' => "L'univers de Naruto est divisé en Pays élémentaires, chacun abritant un Village Caché de ninjas. Le Village de Konoha au Pays du Feu est le foyer du protagoniste. D'autres villages majeurs incluent Suna (Sable), Kiri (Brume), Kumo (Nuage) et Iwa (Roche).",
                            'source' => 'naruto.fandom.com'
                        ],
                        [
                            'title' => "Le système de chakra et techniques ninjas dans Naruto",
                            'link' => "https://www.animenewsnetwork.com/encyclopedia/anime.php?id=1825",
                            'snippet' => "Dans Naruto, les ninjas utilisent le chakra pour exécuter des techniques (jutsu). Les techniques sont classées en Ninjutsu (techniques ninja), Genjutsu (illusions), Taijutsu (combat physique), et peuvent être basées sur différents éléments comme le Feu, l'Eau, et la Foudre.",
                            'source' => 'animenewsnetwork.com'
                        ],
                        [
                            'title' => "L'histoire des Uchiha et l'univers étendu de Naruto",
                            'link' => "https://www.cbr.com/tag/naruto",
                            'snippet' => "L'histoire du clan Uchiha et leurs yeux puissants (Sharingan) est centrale dans l'univers de Naruto. Le monde comprend aussi des créatures mythiques comme les Bijuu (bêtes à queues) dont Kyuubi, le Renard à Neuf Queues scellé en Naruto.",
                            'source' => 'cbr.com'
                        ]
                    ];
                } elseif (stripos($animeName, 'one piece') !== false) {
                    return [
                        [
                            'title' => "La Géographie et les mers de One Piece",
                            'link' => "https://onepiece.fandom.com/wiki/One_Piece_Wiki",
                            'snippet' => "One Piece se déroule dans un monde composé de deux parties: la Blue Sea, divisée en quatre mers (East Blue, North Blue, West Blue, South Blue) séparées par la Red Line et la Grand Line. La Grand Line elle-même est divisée par la Red Line, formant le Nouveau Monde dans sa seconde moitié.",
                            'source' => 'onepiece.fandom.com'
                        ],
                        [
                            'title' => "Le gouvernement mondial et les forces politiques de One Piece",
                            'link' => "https://www.animenewsnetwork.com/encyclopedia/anime.php?id=836",
                            'snippet' => "Le monde de One Piece est gouverné par le Gouvernement Mondial, qui s'oppose aux pirates. L'équilibre des pouvoirs est maintenu entre la Marine, les Sept Grands Corsaires (Shichibukai) et les Quatre Empereurs (Yonko). Le One Piece, trésor légendaire de Gold Roger, est au centre de cet univers.",
                            'source' => 'animenewsnetwork.com'
                        ],
                        [
                            'title' => "Les fruits du démon et pouvoirs dans One Piece",
                            'link' => "https://www.cbr.com/tag/one-piece",
                            'snippet' => "Les fruits du démon confèrent des pouvoirs uniques mais condamnent leur utilisateur à ne plus pouvoir nager. Ils se divisent en trois catégories: Paramecia (pouvoirs superhumains), Zoan (transformation animale) et Logia (contrôle d'éléments naturels comme le feu ou la glace).",
                            'source' => 'cbr.com'
                        ]
                    ];
                } elseif (stripos($animeName, 'attack on titan') !== false || stripos($animeName, 'shingeki no kyojin') !== false) {
                    return [
                        [
                            'title' => "Les murs et la géographie de L'Attaque des Titans",
                            'link' => "https://attackontitan.fandom.com/wiki/Attack_on_Titan_Wiki",
                            'snippet' => "L'humanité vit recluse derrière trois murs concentriques: Maria (externe), Rose (milieu) et Sina (interne). Chaque mur protège des districts et des terres. Au-delà des murs se trouve un territoire hostile infesté de Titans et, plus loin encore, un monde insoupçonné.",
                            'source' => 'attackontitan.fandom.com'
                        ],
                        [
                            'title' => "La technologie et société dans Shingeki no Kyojin",
                            'link' => "https://www.animenewsnetwork.com/encyclopedia/anime.php?id=14950",
                            'snippet' => "La société de l'Attaque des Titans possède une technologie comparable à celle du début du XXe siècle, avec quelques avances comme l'équipement de manœuvre tridimensionnelle, permettant aux soldats de se déplacer en trois dimensions pour combattre les Titans.",
                            'source' => 'animenewsnetwork.com'
                        ],
                        [
                            'title' => "Les origines des Titans et le monde extérieur",
                            'link' => "https://www.cbr.com/tag/attack-on-titan",
                            'snippet' => "L'origine des Titans est liée à l'histoire de Ymir Fritz et aux Eldéens. Le monde extérieur est divisé en nations en guerre, comme Mahr et Eldia. Les Titans sont en réalité des Eldéens transformés, utilisés comme armes par Mahr contre ses ennemis.",
                            'source' => 'cbr.com'
                        ]
                    ];
                } elseif (stripos($animeName, 'hunter') !== false) {
                    return [
                        [
                            'title' => "La géographie et les territoires dans Hunter x Hunter",
                            'link' => "https://hunterxhunter.fandom.com/wiki/Hunterpedia",
                            'snippet' => "Le monde de Hunter x Hunter est divisé en plusieurs continents et régions, dont le Continent V qui est civilisé, et le mystérieux Continent Sombre, considéré comme extrêmement dangereux. Des lieux notables incluent l'Arbre du Monde, l'Île de la Baleine (où Gon a grandi) et York Shin City.",
                            'source' => 'hunterxhunter.fandom.com'
                        ],
                        [
                            'title' => "L'Association des Hunters et son rôle dans l'univers",
                            'link' => "https://www.animenewsnetwork.com/encyclopedia/anime.php?id=13271",
                            'snippet' => "L'Association des Hunters est une organisation d'élite qui forme et certifie des Hunters, individus exceptionnels qui explorent des lieux dangereux, traquent des créatures rares, recherchent des trésors ou chassent des criminels. Les Hunters ont accès à des ressources privées et des privilèges spéciaux.",
                            'source' => 'animenewsnetwork.com'
                        ],
                        [
                            'title' => "Le système du Nen dans Hunter x Hunter",
                            'link' => "https://www.cbr.com/tag/hunter-x-hunter",
                            'snippet' => "Le Nen est un système de combat avancé dans Hunter x Hunter qui permet aux utilisateurs de manipuler leur énergie vitale. Il est divisé en six catégories: Renforcement, Émission, Transformation, Manipulation, Matérialisation et Spécialisation. Chaque personne a une affinité naturelle avec l'une de ces catégories.",
                            'source' => 'cbr.com'
                        ]
                    ];
                } elseif (stripos($animeName, 'dragon ball') !== false) {
                    return [
                        [
                            'title' => "L'univers et les planètes de Dragon Ball",
                            'link' => "https://dragonball.fandom.com/wiki/Dragon_Ball_Wiki",
                            'snippet' => "L'univers de Dragon Ball est divisé en 12 univers, chacun avec ses propres planètes. Dans l'Univers 7, où se déroule l'histoire principale, on trouve la Terre, Namek, Vegeta (détruite), le monde des Kaïo, et d'autres royaumes comme l'au-delà avec le Paradis, l'Enfer et le monde des Kaïos.",
                            'source' => 'dragonball.fandom.com'
                        ],
                        [
                            'title' => "La hiérarchie divine dans Dragon Ball",
                            'link' => "https://www.animenewsnetwork.com/encyclopedia/anime.php?id=165",
                            'snippet' => "Dragon Ball présente une hiérarchie divine complexe: les Kaïo, les dieux de la Destruction (comme Beerus), les Anges (comme Whis) et le Grand Prêtre sous l'autorité de Zeno, le Roi de Tout qui règne sur les 12 univers. Chaque univers a son propre Kaïo Suprême et dieu de la Destruction.",
                            'source' => 'animenewsnetwork.com'
                        ],
                        [
                            'title' => "Le Ki et les transformations dans l'univers de Dragon Ball",
                            'link' => "https://www.cbr.com/tag/dragon-ball",
                            'snippet' => "Dans Dragon Ball, le Ki est l'énergie vitale utilisée pour le combat. Les Saiyans peuvent atteindre diverses transformations (Super Saiyan 1-3, Super Saiyan God, Super Saiyan Blue, Ultra Instinct) qui amplifient considérablement leur puissance. D'autres races comme les Nameks ou les Frost Demons ont leurs propres formes de pouvoir.",
                            'source' => 'cbr.com'
                        ]
                    ];
                }

                // Pour les autres animes, générer des informations génériques
                return [
                    [
                        'title' => "L'univers et le monde de {$animeName} - Guide complet",
                        'link' => "https://worldbuilding.stackexchange.com/search?q={$animeName}",
                        'snippet' => "Découvrez les règles, la géographie et l'univers unique de {$animeName}. Le monde créé pour cette série possède sa propre mythologie et son histoire riche qui servent de toile de fond au récit principal.",
                        'source' => 'worldbuilding.stackexchange.com'
                    ],
                    [
                        'title' => "Les lieux emblématiques dans l'univers de {$animeName}",
                        'link' => "https://tvtropes.org/pmwiki/pmwiki.php/Anime/{$animeName}",
                        'snippet' => "{$animeName} se déroule dans plusieurs lieux mémorables qui sont devenus iconiques pour les fans. Chaque lieu possède son atmosphère propre et contribue à enrichir l'histoire et le développement des personnages.",
                        'source' => 'tvtropes.org'
                    ],
                    [
                        'title' => "Le système de pouvoir et les règles dans {$animeName}",
                        'link' => "https://www.cbr.com/tag/{$animeName}",
                        'snippet' => "Les personnages de {$animeName} utilisent un système de pouvoirs unique à cet univers, avec ses propres règles et limitations. Ces mécaniques de l'univers sont essentielles pour comprendre les batailles et les défis que les protagonistes doivent relever.",
                        'source' => 'cbr.com'
                    ]
                ];

            case 'plot':
                if (stripos($animeName, 'naruto') !== false) {
                    return [
                        [
                            'title' => "L'histoire complète de Naruto: du ninja perturbateur au Hokage",
                            'link' => "https://fr.wikipedia.org/wiki/Naruto",
                            'snippet' => "Naruto suit l'histoire d'un jeune ninja orphelin, Naruto Uzumaki, qui rêve de devenir Hokage (chef du village). Rejeté par les villageois car il abrite le démon renard à neuf queues (Kyûbi), il intègre l'équipe 7 avec Sasuke Uchiha et Sakura Haruno sous la direction de Kakashi Hatake.",
                            'source' => 'wikipedia.org'
                        ],
                        [
                            'title' => "Les grands arcs de Naruto et Naruto Shippuden",
                            'link' => "https://www.reddit.com/r/Naruto/",
                            'snippet' => "Naruto se compose de plusieurs arcs majeurs: l'examen Chunin, la poursuite de Sasuke, Naruto Shippuden (après un saut temporel de 2 ans), l'arc Akatsuki, la Quatrième Grande Guerre Ninja, et finalement le combat contre Kaguya. L'histoire se conclut avec Naruto devenant le Septième Hokage.",
                            'source' => 'reddit.com'
                        ],
                        [
                            'title' => "Les thèmes centraux de l'histoire de Naruto",
                            'link' => "https://www.animenewsnetwork.com/feature/naruto-themes",
                            'snippet' => "Naruto explore des thèmes profonds comme la persévérance (le nindô de Naruto), les cycles de haine et de vengeance (Sasuke, Pain), la guerre et la paix, ainsi que l'amitié et les liens qui unissent les personnages. La série interroge si la compréhension mutuelle peut vraiment mettre fin aux conflits.",
                            'source' => 'animenewsnetwork.com'
                        ]
                    ];
                } elseif (stripos($animeName, 'one piece') !== false) {
                    return [
                        [
                            'title' => "La quête du One Piece: L'aventure de Luffy et son équipage",
                            'link' => "https://fr.wikipedia.org/wiki/One_Piece",
                            'snippet' => "One Piece suit Monkey D. Luffy, un jeune homme dont le corps a acquis les propriétés du caoutchouc après avoir mangé un fruit du démon. Avec son équipage de pirates, les Chapeaux de Paille, il explore Grand Line à la recherche du trésor ultime, le 'One Piece', afin de devenir le Roi des Pirates.",
                            'source' => 'wikipedia.org'
                        ],
                        [
                            'title' => "Les sagas et arcs narratifs de One Piece",
                            'link' => "https://www.reddit.com/r/OnePiece/",
                            'snippet' => "One Piece est divisé en grandes sagas: East Blue, Alabasta, Skypiea, Water 7/Enies Lobby, Thriller Bark, Guerre au Sommet, Île des Hommes-Poissons, Dressrosa, Whole Cake Island, Wano Kuni, et d'autres. Chaque arc introduit de nouveaux personnages, territoires et éléments de l'intrigue globale.",
                            'source' => 'reddit.com'
                        ],
                        [
                            'title' => "Le Siècle Oublié et les mystères centraux de One Piece",
                            'link' => "https://www.animenewsnetwork.com/feature/one-piece-mysteries",
                            'snippet' => "L'intrigue centrale de One Piece tourne autour de mystères historiques comme le Siècle Oublié, la Volonté du D., les Armes Antiques (Pluton, Poséidon, Uranus), et la vraie nature du One Piece. Robin déchiffre progressivement les Ponéglyphes qui révèlent cette histoire cachée par le Gouvernement Mondial.",
                            'source' => 'animenewsnetwork.com'
                        ]
                    ];
                } elseif (stripos($animeName, 'attack on titan') !== false || stripos($animeName, 'shingeki no kyojin') !== false) {
                    return [
                        [
                            'title' => "L'Attaque des Titans: De la chute du Mur Maria à la vérité sur le monde",
                            'link' => "https://fr.wikipedia.org/wiki/L%27Attaque_des_Titans",
                            'snippet' => "L'Attaque des Titans commence lorsque le Mur Maria est percé par le Titan Colossal, permettant aux Titans d'envahir le territoire humain. Eren Jäger, dont la mère est dévorée, jure de les exterminer. Après avoir rejoint le Bataillon d'Exploration, il découvre qu'il peut se transformer en Titan.",
                            'source' => 'wikipedia.org'
                        ],
                        [
                            'title' => "Les révélations majeures et rebondissements de l'histoire",
                            'link' => "https://www.reddit.com/r/ShingekiNoKyojin/",
                            'snippet' => "L'intrigue devient plus complexe avec la révélation que certains Titans sont des humains transformés (Annie, Reiner, Bertholdt), puis que l'humanité dans les murs n'est qu'une petite population isolée. On découvre que le monde extérieur est dominé par la nation de Mahr qui utilise les Eldiens transformés en Titans comme armes.",
                            'source' => 'reddit.com'
                        ],
                        [
                            'title' => "Les thèmes de liberté et de cycle de violence dans Shingeki no Kyojin",
                            'link' => "https://www.animenewsnetwork.com/feature/attack-on-titan-themes",
                            'snippet' => "L'Attaque des Titans explore des thèmes sombres comme la liberté (symbolisée par les ailes du Bataillon d'Exploration), les cycles de haine, le racisme (Eldiens vs Mahr) et la guerre. La question centrale devient: peut-on rompre ce cycle millénaire de violence, ou est-ce que la quête de liberté d'un peuple signifie nécessairement l'oppression d'un autre?",
                            'source' => 'animenewsnetwork.com'
                        ]
                    ];
                } elseif (stripos($animeName, 'hunter') !== false) {
                    return [
                        [
                            'title' => "Hunter x Hunter: Le voyage initiatique de Gon Freecss",
                            'link' => "https://fr.wikipedia.org/wiki/Hunter_%C3%97_Hunter",
                            'snippet' => "Hunter × Hunter suit Gon Freecss, un jeune garçon qui découvre que son père, qu'il croyait mort, est en fait un légendaire Hunter. Déterminé à le retrouver, Gon passe l'examen Hunter et se lie d'amitié avec Leorio, Kurapika et Killua, chacun ayant ses propres motivations pour devenir Hunter.",
                            'source' => 'wikipedia.org'
                        ],
                        [
                            'title' => "Les arcs narratifs de Hunter x Hunter",
                            'link' => "https://www.reddit.com/r/HunterXHunter/",
                            'snippet' => "L'histoire se divise en plusieurs arcs majeurs: l'Examen Hunter, la Tour Céleste, l'Arc de York Shin City (où Kurapika affronte la Brigade Fantôme), l'Arc de Greed Island (jeu vidéo réel), et l'Arc des Fourmis-Chimères (considéré comme le plus sombre, culminant avec le combat contre Meruem).",
                            'source' => 'reddit.com'
                        ],
                        [
                            'title' => "Les relations complexes et la moralité dans Hunter x Hunter",
                            'link' => "https://www.animenewsnetwork.com/feature/hunter-x-hunter-analysis",
                            'snippet' => "Hunter x Hunter se distingue par la complexité morale de ses personnages. Les antagonistes comme Hisoka, Chrollo ou Meruem sont nuancés et développés. La série aborde des thèmes comme l'humanité (arc des Fourmis-Chimères), l'amitié (Gon et Killua), la vengeance (Kurapika) et explore ce qui définit la nature humaine.",
                            'source' => 'animenewsnetwork.com'
                        ]
                    ];
                } elseif (stripos($animeName, 'dragon ball') !== false) {
                    return [
                        [
                            'title' => "L'épopée de Son Goku: De Dragon Ball à Dragon Ball Super",
                            'link' => "https://fr.wikipedia.org/wiki/Dragon_Ball",
                            'snippet' => "Dragon Ball commence avec Son Goku, un jeune garçon doté d'une force extraordinaire et d'une queue de singe, qui rencontre Bulma partie à la recherche des Dragon Balls. La série suit sa croissance, ses tournois d'arts martiaux, puis dans Dragon Ball Z, ses combats pour défendre la Terre contre des menaces toujours plus puissantes.",
                            'source' => 'wikipedia.org'
                        ],
                        [
                            'title' => "Les sagas majeures de l'univers Dragon Ball",
                            'link' => "https://www.reddit.com/r/dbz/",
                            'snippet' => "L'histoire comprend plusieurs sagas emblématiques: la saga Saiyan (révélant les origines extraterrestres de Goku), Freezer (sur Namek), Cell (incluant les Cyborgs), Majin Buu, puis dans Super: Battle of Gods (Beerus), Resurrection of F (Freezer), le Tournoi du 6ème Univers, Black Goku, et le Tournoi du Pouvoir.",
                            'source' => 'reddit.com'
                        ],
                        [
                            'title' => "L'évolution et l'héritage de Dragon Ball dans la culture manga",
                            'link' => "https://www.animenewsnetwork.com/feature/dragon-ball-legacy",
                            'snippet' => "Dragon Ball est centré sur les thèmes de dépassement de soi, d'amitié et de protection des êtres chers. L'histoire suit une escalade constante de puissance, avec Goku et ses alliés atteignant des niveaux toujours plus élevés pour affronter des menaces d'envergure universelle, tout en préservant leur humanité et leurs valeurs.",
                            'source' => 'animenewsnetwork.com'
                        ]
                    ];
                }

                // Pour les autres animes, générer des informations génériques
                return [
                    [
                        'title' => "Synopsis et intrigue principale de {$animeName}",
                        'link' => "https://fr.wikipedia.org/wiki/{$animeName}",
                        'snippet' => "{$animeName} présente une histoire originale qui captive les spectateurs avec ses rebondissements et son développement de personnages. L'intrigue principale suit un parcours émotionnel qui résonne avec les thèmes universels tout en offrant une expérience narrative unique.",
                        'source' => 'wikipedia.org'
                    ],
                    [
                        'title' => "Analyse des arcs narratifs et de l'évolution de {$animeName}",
                        'link' => "https://www.reddit.com/r/anime/search?q={$animeName}",
                        'snippet' => "La structure narrative de {$animeName} se compose de plusieurs arcs distincts qui permettent d'explorer différentes facettes des personnages et de l'univers. Chaque arc apporte son lot de défis et d'évolutions qui font progresser l'intrigue principale vers sa résolution.",
                        'source' => 'reddit.com'
                    ],
                    [
                        'title' => "Les thèmes et messages de {$animeName} - Analyse approfondie",
                        'link' => "https://www.animenewsnetwork.com/feature/{$animeName}-analysis",
                        'snippet' => "{$animeName} aborde plusieurs thèmes profonds qui réfléchissent sur la condition humaine et les relations interpersonnelles. Ces thèmes sont habilement entrelacés dans l'intrigue et portés par des personnages dont les motivations et les conflits internes résonnent avec le public.",
                        'source' => 'animenewsnetwork.com'
                    ]
                ];

            default:
                return [
                    [
                        'title' => "Informations générales sur {$animeName} - Base de données anime",
                        'link' => "https://myanimelist.net/anime/search?q={$animeName}",
                        'snippet' => "Toutes les informations sur {$animeName} : staff, production, dates de diffusion. Un anime populaire qui a marqué les fans du monde entier.",
                        'source' => 'myanimelist.net'
                    ],
                    [
                        'title' => "Critique et réception de {$animeName}",
                        'link' => "https://www.imdb.com/find?q={$animeName}",
                        'snippet' => "{$animeName} a reçu des critiques élogieuses pour son animation, son développement de personnages et son histoire captivante. Les fans apprécient particulièrement la profondeur des personnages.",
                        'source' => 'imdb.com'
                    ]
                ];
        }
    }
}