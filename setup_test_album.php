<?php

declare(strict_types=1);

/**
 * Script pour créer des données de test réelles pour la galerie.
 * Exécute la procédure complète :
 * 1. Créer une anomalie
 * 2. Créer un événement lié à l'anomalie
 * 3. Valider l'événement
 * 4. Créer un album avec photos
 * 5. Publier l'album
 */

define('BASE_PATH', __DIR__);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/Bootstrap.php';

\App\Bootstrap::boot();

use App\Helpers\Database;

echo "=== Création de données de test pour la galerie ===\n\n";

// 1. Créer une anomalie si elle n'existe pas
$anomalieId = Database::value("SELECT id FROM anomalies WHERE nom = 'Espaces verts'");
if (!$anomalieId) {
    Database::run(
        "INSERT INTO anomalies (nom, description, icone, couleur)
         VALUES ('Espaces verts', 'Emballés alimentaires dans les espaces verts', 'fa-tree', '#22c55e')"
    );
    $anomalieId = Database::value("SELECT id FROM anomalies WHERE nom = 'Espaces verts'");
    echo "[OK] Anomalie créée : ID=$anomalieId\n";
} else {
    echo "[OK] Anomalie existante : ID=$anomalieId\n";
}

// 2. Créer un événement lié à l'anomalie
$eventId = Database::value("SELECT id FROM evenements WHERE adresse LIKE 'TEST-EVENT-GALLERY%' ORDER BY id DESC LIMIT 1");
if (!$eventId) {
    Database::run(
        "INSERT INTO evenements 
         (commune_id, adresse, description, date_evenement, heure, statut, association_id)
         VALUES 
         (1, 'TEST-EVENT-GALLERY: Rue des chênes, Alger', 'Nettoyage des espaces verts du quartier', 
          DATE_SUB(CURDATE(), INTERVAL 2 DAY), '10:00:00', 'TERMINE', 1)"
    );
    $eventId = Database::value("SELECT id FROM evenements WHERE adresse LIKE 'TEST-EVENT-GALLERY%' ORDER BY id DESC LIMIT 1");
    echo "[OK] Événement créé : ID=$eventId\n";
    
    // Lier l'anomalie à l'événement
    Database::run(
        "INSERT INTO anomalies_evenement (evenement_id, anomalie_id) VALUES (?, ?)",
        [(int)$eventId, (int)$anomalieId]
    );
    echo "[OK] Anomalie liée à l'événement\n";
} else {
    echo "[OK] Événement existant : ID=$eventId\n";
}

// 3. Créer un album pour l'événement
$albumId = Database::value("SELECT id FROM albums WHERE evenement_id = ? AND titre = 'Nettoyage espaces verts'", [(int)$eventId]);
if (!$albumId) {
    Database::run(
        "INSERT INTO albums (evenement_id, titre, recit, date_creation, statut)
         VALUES (?, ?, ?, NOW(), 'publie')",
        [
            (int)$eventId,
            'Nettoyage espaces verts',
            'Une équipe de 25 citoyens a nettoyé les espaces verts du quartier. Des arbres ont été plantés et des bancs rénovés.'
        ]
    );
    $albumId = Database::value("SELECT id FROM albums WHERE evenement_id = ? AND titre = 'Nettoyage espaces verts'", [(int)$eventId]);
    echo "[OK] Album créé : ID=$albumId\n";
} else {
    echo "[OK] Album existant : ID=$albumId\n";
}

// 4. Ajouter des photos à l'album
$existingPhotos = Database::value("SELECT COUNT(*) FROM photos WHERE album_id = ?", [(int)$albumId]);
if ($existingPhotos == 0) {
    $photos = [
        ['image' => '/assets/img/demo/gallery1.jpg', 'legende' => 'Avant le nettoyage', 'sort_order' => 1],
        ['image' => '/assets/img/demo/gallery2.jpg', 'legende' => 'Travail d\'équipe', 'sort_order' => 2],
        ['image' => '/assets/img/demo/gallery3.jpg', 'legende' => 'Résultat final', 'sort_order' => 3],
    ];
    
    foreach ($photos as $photo) {
        Database::run(
            "INSERT INTO photos (album_id, image, legende, sort_order, uploaded_at)
             VALUES (?, ?, ?, ?, NOW())",
            [
                (int)$albumId,
                $photo['image'],
                $photo['legende'],
                $photo['sort_order']
            ]
        );
    }
    
    // Set the first photo as cover
    Database::run(
        "UPDATE albums SET couverture = ? WHERE id = ?",
        ['/assets/img/demo/gallery1.jpg', (int)$albumId]
    );
    
    echo "[OK] Photos ajoutées à l'album\n";
    echo "[OK] Couverture définie\n";
} else {
    echo "[OK] Photos existantes : $existingPhotos\n";
}

// 5. Vérifier l'état final
$album = Database::one("SELECT * FROM albums WHERE id = ?", [(int)$albumId]);
$photosCount = Database::value("SELECT COUNT(*) FROM photos WHERE album_id = ?", [(int)$albumId]);

echo "\n=== Résumé ===\n";
echo "Album ID: $albumId\n";
echo "Titre: {$album['titre']}\n";
echo "Statut: {$album['statut']}\n";
echo "Couverture: {$album['couverture']}\n";
echo "Photos: $photosCount\n";
echo "Récit: {$album['recit']}\n";

echo "\n=== Test terminé avec succès ===\n";