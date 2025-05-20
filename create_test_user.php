<?php
// Fichier pour créer un utilisateur test

require_once 'vendor/autoload.php';
require_once 'config/bootstrap.php';

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

// Créer un factory de hachage de mot de passe
$factory = new PasswordHasherFactory([
    'common' => ['algorithm' => 'bcrypt']
]);
$hasher = $factory->getPasswordHasher('common');

// Récupérer l'EntityManager
$entityManager = $entityManagerFactory->createEntityManager();

// Vérifier si l'utilisateur test existe déjà
$existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
if ($existingUser) {
    echo "L'utilisateur test existe déjà.\n";
    exit;
}

// Créer un nouvel utilisateur
$user = new User();
$user->setEmail('test@example.com');
$user->setUsername('TestUser');
$user->setRoles(['ROLE_USER']);

// Hash du mot de passe
$hashedPassword = $hasher->hash('password');
$user->setPassword($hashedPassword);

// Persistance dans la base de données
$entityManager->persist($user);
$entityManager->flush();

echo "Utilisateur test créé avec succès !\n";
echo "Email: test@example.com\n";
echo "Mot de passe: password\n";
?>
