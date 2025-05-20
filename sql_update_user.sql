-- Script pour corriger la structure de la table User
-- Compatible avec interface sombre (#0b0c2a avec accents #e53637)

-- Vérifier si la colonne password existe, sinon l'ajouter
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user'
    AND COLUMN_NAME = 'password'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE user ADD COLUMN password VARCHAR(255) NOT NULL DEFAULT "$2y$13$9HFDVXiKfqZgvs9eVpg8S.TcHlHuFWlZUmpGFzPWwZuTCp2p9D0bG"',
    'SELECT "La colonne password existe déjà" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Créer un utilisateur de test avec mot de passe connu (mot de passe: password)
INSERT IGNORE INTO user (email, password)
VALUES ('admin@anime.fr', '$2y$13$9HFDVXiKfqZgvs9eVpg8S.TcHlHuFWlZUmpGFzPWwZuTCp2p9D0bG');

-- Afficher message de confirmation
SELECT 'Structure de table User mise à jour et utilisateur de test créé.' AS message;
SELECT 'Email: admin@anime.fr / Mot de passe: password' AS identifiants;
