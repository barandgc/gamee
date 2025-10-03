<?php
// Oyun API - Oyun listesi ve yönetimi

require_once '../includes/config.php';

// GET istekleri - Oyun listesi
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    getAllGames();
}

function getAllGames() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("
            SELECT 
                game_id,
                title,
                genre,
                max_players,
                image
            FROM games 
            ORDER BY title ASC
        ");
        
        $games = $stmt->fetchAll();
        
        sendResponse([
            'success' => true,
            'games' => $games
        ]);
        
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}

// POST istekleri - Yeni oyun ekleme (admin için)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendError('Geçersiz JSON verisi');
    }
    
    $action = $input['action'] ?? '';
    
    if ($action === 'add') {
        addGame($input);
    } else {
        sendError('Geçersiz işlem');
    }
}

function addGame($data) {
    $title = trim($data['title'] ?? '');
    $genre = trim($data['genre'] ?? '');
    $maxPlayers = intval($data['max_players'] ?? 0);
    $image = trim($data['image'] ?? 'default-game.png');
    
    if (empty($title) || empty($genre) || $maxPlayers <= 0) {
        sendError('Tüm alanlar gerekli');
    }
    
    try {
        $pdo = getDBConnection();
        
        // Oyun adı kontrolü
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM games WHERE title = ?");
        $stmt->execute([$title]);
        
        if ($stmt->fetchColumn() > 0) {
            sendError('Bu oyun zaten mevcut');
        }
        
        // Yeni oyun ekleme
        $stmt = $pdo->prepare("
            INSERT INTO games (title, genre, max_players, image) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$title, $genre, $maxPlayers, $image]);
        $gameId = $pdo->lastInsertId();
        
        // Eklenen oyunu getir
        $stmt = $pdo->prepare("SELECT * FROM games WHERE game_id = ?");
        $stmt->execute([$gameId]);
        $newGame = $stmt->fetch();
        
        sendResponse([
            'success' => true,
            'game' => $newGame,
            'message' => 'Oyun başarıyla eklendi'
        ]);
        
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}
?>
