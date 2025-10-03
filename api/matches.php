<?php
// Maç API - Maç oluşturma ve skor yönetimi

require_once '../includes/config.php';

// POST istekleri - Maç işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendError('Geçersiz JSON verisi');
    }
    
    $action = $input['action'] ?? 'create';
    
    if ($action === 'create') {
        createMatch($input);
    } else {
        sendError('Geçersiz işlem');
    }
}

function createMatch($data) {
    $partyId = intval($data['party_id'] ?? 0);
    $gameId = intval($data['game_id'] ?? 0);
    $matchDate = $data['match_date'] ?? '';
    $scores = $data['scores'] ?? [];
    
    if ($partyId <= 0 || $gameId <= 0 || empty($matchDate) || empty($scores)) {
        sendError('Tüm alanlar gerekli');
    }
    
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        
        // Parti kontrolü
        $partyStmt = $pdo->prepare("
            SELECT status, host_id 
            FROM parties 
            WHERE party_id = ?
        ");
        $partyStmt->execute([$partyId]);
        $party = $partyStmt->fetch();
        
        if (!$party) {
            sendError('Parti bulunamadı');
        }
        
        if ($party['status'] === 'completed') {
            sendError('Tamamlanmış partiye maç eklenemez');
        }
        
        // Maç oluştur
        $matchStmt = $pdo->prepare("
            INSERT INTO matches (party_id, game_id, match_date, status) 
            VALUES (?, ?, ?, 'completed')
        ");
        $matchStmt->execute([$partyId, $gameId, $matchDate]);
        $matchId = $pdo->lastInsertId();
        
        // Skorları kaydet
        $scoreStmt = $pdo->prepare("
            INSERT INTO scores (match_id, user_id, score, result, points) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($scores as $score) {
            $userId = intval($score['user_id']);
            $scoreValue = intval($score['score']);
            $result = $score['result'];
            
            // Puan hesapla: galibiyet = 3, beraberlik = 1, mağlubiyet = 0
            $points = 0;
            if ($result === 'win') $points = 3;
            elseif ($result === 'draw') $points = 1;
            
            $scoreStmt->execute([$matchId, $userId, $scoreValue, $result, $points]);
        }
        
        $pdo->commit();
        
        sendResponse([
            'success' => true,
            'match_id' => $matchId,
            'message' => 'Maç ve skorlar başarıyla kaydedildi'
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}

// GET istekleri - Maç listesi
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $partyId = intval($_GET['party_id'] ?? 0);
    
    if ($partyId <= 0) {
        sendError('Parti ID gerekli');
    }
    
    getMatches($partyId);
}

function getMatches($partyId) {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                m.*,
                g.title as game_title
            FROM matches m
            JOIN games g ON m.game_id = g.game_id
            WHERE m.party_id = ?
            ORDER BY m.match_date DESC
        ");
        $stmt->execute([$partyId]);
        $matches = $stmt->fetchAll();
        
        sendResponse([
            'success' => true,
            'matches' => $matches
        ]);
        
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}
?>