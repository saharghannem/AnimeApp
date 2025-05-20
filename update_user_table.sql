-- Supprimer la table user si elle existe déjà
DROP TABLE IF EXISTS user;

-- Créer la nouvelle table user avec la bonne structure
CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) NOT NULL UNIQUE,
    username VARCHAR(255) NOT NULL,
    roles JSON NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Insérer un utilisateur test avec un mot de passe hashé
-- Le mot de passe "password" hashé avec bcrypt
INSERT INTO user (email, username, roles, password) 
VALUES ('admin@example.com', 'Admin', '["ROLE_USER", "ROLE_ADMIN"]', '$2y$13$9HFDVXiKfqZgvs9eVpg8S.TcHlHuFWlZUmpGFzPWwZuTCp2p9D0bG');

-- Ce utilisateur pourra se connecter avec:
-- Email: admin@example.com
-- Mot de passe: password
