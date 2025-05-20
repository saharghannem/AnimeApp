-- Script pour mettre à jour la structure de la table User
-- Conforme au thème sombre (#0b0c2a avec accents #e53637)

-- Ajouter la colonne password si elle n'existe pas déjà
ALTER TABLE user ADD COLUMN IF NOT EXISTS password VARCHAR(255) NOT NULL DEFAULT '$2y$13$9HFDVXiKfqZgvs9eVpg8S.TcHlHuFWlZUmpGFzPWwZuTCp2p9D0bG';

-- Ajouter un utilisateur admin si la table est vide
INSERT INTO user (email, password)
SELECT 'admin@anime.fr', '$2y$13$9HFDVXiKfqZgvs9eVpg8S.TcHlHuFWlZUmpGFzPWwZuTCp2p9D0bG'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM user LIMIT 1);

-- Message de confirmation
SELECT 'Table user mise à jour avec succès. Mot de passe pour tous les comptes: password' AS message;
