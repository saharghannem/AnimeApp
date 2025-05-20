-- Script pour ajouter la colonne password à la table user
-- Compatible avec les préférences d'interface sombre (#0b0c2a / #e53637)

-- Ajouter la colonne password si elle n'existe pas
ALTER TABLE user ADD COLUMN IF NOT EXISTS password VARCHAR(255) NOT NULL DEFAULT '$2y$13$9HFDVXiKfqZgvs9eVpg8S.TcHlHuFWlZUmpGFzPWwZuTCp2p9D0bG';

-- Supprimer la colonne 'mot de passe' si elle existe (pour éviter la confusion)
ALTER TABLE user DROP COLUMN IF EXISTS `mot de passe`;

-- Ajouter un index sur l'email pour optimiser les recherches
ALTER TABLE user ADD UNIQUE INDEX IF NOT EXISTS UNIQ_8D93D649E7927C74 (email);

-- Créer un utilisateur admin avec le mot de passe 'password'
INSERT IGNORE INTO user (email, password) 
VALUES ('admin@anime.fr', '$2y$13$9HFDVXiKfqZgvs9eVpg8S.TcHlHuFWlZUmpGFzPWwZuTCp2p9D0bG');
