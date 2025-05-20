<?php
// Récupérer les paramètres de connexion depuis le fichier .env de Symfony
$envFile = file_get_contents(__DIR__ . '/.env');
preg_match('/DATABASE_URL=mysql:\/\/([^:]+):([^@]*)@([^:]+):([^\/]+)\/([^?]+)/', $envFile, $matches);

if (count($matches) >= 6) {
    $username = $matches[1];
    $password = $matches[2];
    $host = $matches[3];
    $port = $matches[4];
    $database = $matches[5];
} else {
    // Valeurs par défaut si le format n'est pas reconnu
    $username = 'root';
    $password = '';
    $host = 'localhost';
    $port = '3306';
    $database = 'anime';
}

try {
    // Créer la connexion PDO
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Définir la requête SQL pour créer la table quiz_score
    $sql = "CREATE TABLE IF NOT EXISTS quiz_score (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        score INT NOT NULL,
        total_questions INT NOT NULL,
        quiz_type VARCHAR(20) NOT NULL,
        date DATETIME NOT NULL,
        INDEX IDX_quiz_score_user (user_id),
        CONSTRAINT FK_quiz_score_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;";
    
    // Exécuter la requête
    $pdo->exec($sql);
    
    echo "La table quiz_score a été créée avec succès !";
} catch (PDOException $e) {
    echo "Erreur lors de la création de la table quiz_score : " . $e->getMessage();
}
