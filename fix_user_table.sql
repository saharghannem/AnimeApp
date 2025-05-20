-- Ajouter les colonnes manquantes à la table user
ALTER TABLE user 
ADD COLUMN username VARCHAR(255) NOT NULL AFTER email,
ADD COLUMN roles JSON NOT NULL AFTER username;

-- Mettre à jour les utilisateurs existants
UPDATE user SET username = CONCAT('User', id), roles = '["ROLE_USER"]' WHERE 1;

-- S'assurer que les types de colonnes sont corrects
ALTER TABLE user 
MODIFY COLUMN email VARCHAR(180) NOT NULL,
MODIFY COLUMN password VARCHAR(255) NOT NULL;
