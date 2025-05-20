<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class BrevoEmailService
{
    private MailerInterface $mailer;
    private string $ADMIN_EMAIL = 'ghannemsahar2002@gmail.com';
    private string $SENDER_NAME = 'Anime App Notification';

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Envoyer une notification pour un nouvel anime
     */
    public function sendAnimeCreationNotification($animeName, $animeId, $genre = null, $imageUrl = null)
    {
        $htmlContent = $this->buildAnimeNotificationEmail($animeName, $animeId, $genre, $imageUrl);

        $email = (new Email())
            ->from($this->ADMIN_EMAIL)
            ->to($this->ADMIN_EMAIL)
            ->subject('Nouvel anime ajouté : ' . $animeName)
            ->html($htmlContent);

        if ($imageUrl) {
            $imagePath = substr($imageUrl, 0, 1) === '/'
                ? dirname(__DIR__, 2) . '/public' . $imageUrl
                : dirname(__DIR__, 2) . '/public/' . $imageUrl;

            if (file_exists($imagePath)) {
                $email->embedFromPath($imagePath, 'anime_image');
            }
        }

        try {
            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            error_log("Exception lors de l'envoi de l'email de notification: " . $e->getMessage());
            return false;
        }
    }

    private function buildAnimeNotificationEmail($animeName, $animeId, $genre = null, $imageUrl = null): string
    {
        $baseUrl = "http://localhost";
        $animeUrl = $baseUrl . "/anime/show/{$animeId}";
        $genre = $genre ?? 'Non catégorisé';

        $imageHtml = '<div style="background-color: #1c1c3a; padding: 15px; text-align: center; border-radius: 5px; margin-bottom: 15px;">
            <span style="color: #e53637; font-size: 16px;">Image non disponible</span>
        </div>';

        if ($imageUrl) {
            $imageHtml = '<img src="cid:anime_image" alt="' . htmlspecialchars($animeName) . '" style="display: block; width: 100%; max-width: 400px; border-radius: 8px; margin: 0 auto 15px; border: 2px solid #252a59;">';
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvel Anime Ajouté</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #e0e0e0;
            background-color: #0b0c2a;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #161b42;
            border-radius: 8px;
            border: 1px solid #252a59;
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 3px solid #e53637;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
        }
        .anime-info {
            background-color: #0e103a;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #e53637;
        }
        .anime-title {
            font-size: 22px;
            color: #ffffff;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .anime-genre {
            color: #9b9ecc;
            margin-bottom: 15px;
            font-style: italic;
        }
        .button {
            display: inline-block;
            background-color: #e53637;
            color: #ffffff;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            color: #9b9ecc;
            font-size: 12px;
            border-top: 1px solid #252a59;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Notification - Nouvel Anime</h1>
        </div>

        <p>Un nouvel anime a été ajouté à votre collection :</p>

        <div class="anime-info">
            <h2 class="anime-title">{$animeName}</h2>
            <div class="anime-genre">Genre: {$genre}</div>
            {$imageHtml}
            <p>Consultez cet anime pour voir tous les détails et effectuer des modifications si nécessaire.</p>
            <a href="{$animeUrl}" class="button">Voir l'anime</a>
        </div>

        <p>Cette notification a été envoyée automatiquement depuis votre application de gestion d'anime.</p>

        <div class="footer">
            <p>&copy; 2025 Application Anime. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
