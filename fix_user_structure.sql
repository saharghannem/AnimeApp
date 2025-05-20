-- Script complet pour corriger la structure de la table User

-- 1. Vérifier si la table existe et la sauvegarder
CREATE TABLE IF NOT EXISTS user_backup AS SELECT * FROM user;

-- 2. Supprimer la table existante
DROP TABLE IF EXISTS user;

-- 3. Recréer la table avec la structure exacte correspondant à l'entité
CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) NOT NULL,
    username VARCHAR(255) NOT NULL,
    roles LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
    password VARCHAR(255) NOT NULL
);

-- 4. Ajouter un index unique sur l'email pour optimiser les recherches
CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email);

-- 5. Créer un utilisateur de test avec le design dark theme que vous préférez
INSERT INTO user (email, username, roles, password) 
VALUES ('admin@anime.com', 'AdminAnime', '["ROLE_ADMIN", "ROLE_USER"]', '$2y$13$9HFDVXiKfqZgvs9eVpg8S.TcHlHuFWlZUmpGFzPWwZuTCp2p9D0bG');

-- Ajout d'un message pour indiquer le succès
SELECT 'Structure de la table User mise à jour avec succès!' AS message;
