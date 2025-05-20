-- Sauvegarde des données existantes si nécessaire (facultatif)
CREATE TEMPORARY TABLE user_backup AS SELECT * FROM user;

-- Supprimer la table existante
DROP TABLE IF EXISTS user;

-- Créer une nouvelle table avec la structure correcte
CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) NOT NULL,
    username VARCHAR(255) NOT NULL,
    roles LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
    password VARCHAR(255) NOT NULL
);

-- Créer un index unique sur l'email
CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email);

-- Insérer un utilisateur administrateur de test
INSERT INTO user (email, username, roles, password) 
VALUES ('admin@example.com', 'Administrateur', '["ROLE_ADMIN", "ROLE_USER"]', '$2y$13$9HFDVXiKfqZgvs9eVpg8S.TcHlHuFWlZUmpGFzPWwZuTCp2p9D0bG');

-- Insérer un utilisateur normal de test
INSERT INTO user (email, username, roles, password) 
VALUES ('user@example.com', 'Utilisateur', '["ROLE_USER"]', '$2y$13$9HFDVXiKfqZgvs9eVpg8S.TcHlHuFWlZUmpGFzPWwZuTCp2p9D0bG');
