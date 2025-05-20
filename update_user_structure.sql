-- Script pour mettre à jour la structure de la table User pour Symfony
-- Adapté au thème sombre avec l'interface moderne (#0b0c2a et #e53637)

-- Ajouter la colonne password si elle n'existe pas
ALTER TABLE user ADD COLUMN IF NOT EXISTS password VARCHAR(255) NOT NULL DEFAULT '$2y$13$9HFDVXiKfqZgvs9eVpg8S.TcHlHuFWlZUmpGFzPWwZuTCp2p9D0bG';

-- Ajouter la colonne username si elle n'existe pas
ALTER TABLE user ADD COLUMN IF NOT EXISTS username VARCHAR(255) DEFAULT NULL;

-- Ajouter la colonne roles si elle n'existe pas
ALTER TABLE user ADD COLUMN IF NOT EXISTS roles LONGTEXT NOT NULL DEFAULT '["ROLE_USER"]' COMMENT '(DC2Type:json)';

-- Mise à jour des username vides
UPDATE user SET username = CONCAT('Utilisateur', id) WHERE username IS NULL;

-- Créer un utilisateur admin si la table est vide
INSERT INTO user (email, username, roles, password)
SELECT 'admin@anime.com', 'AdminAnime', '["ROLE_ADMIN","ROLE_USER"]', '$2y$13$9HFDVXiKfqZgvs9eVpg8S.TcHlHuFWlZUmpGFzPWwZuTCp2p9D0bG'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM user LIMIT 1);

-- Message de confirmation
SELECT 'Structure de la table User mise à jour avec succès. Mot de passe pour tous les comptes: password' AS message;
