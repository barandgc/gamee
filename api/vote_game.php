<?php
// Oyun Oylama API - Parti için oyun oylama sistemi

require_once '../includes/config.php';

// Sadece POST istekleri kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Sadece POST isteği kabul edilir');
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendError('Geçersiz JSON verisi');
}

$partyId = intval($input['party_id'] ?? 0);
$gameId = intval($input['game_id'] ?? 0);
$userId = intval($input['user_id'] ?? 0);

if ($partyId <= 0 || $gameId <= 0 || $userId <= 0) {
    sendError('Tüm alanlar gerekli');
}

voteForGame($partyId, $gameId, $userId);

function voteForGame($partyId, $gameId, $userId) {
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        
        // Parti kontrolü
        $partyStmt = $pdo->prepare("
            SELECT status 
            FROM parties 
            WHERE party_id = ?
        ");
        $partyStmt->execute([$partyId]);
        $party = $partyStmt->fetch();
        
        if (!$party) {
            sendError('Parti bulunamadı');
        }
        
        if ($party['status'] !== 'planning' && $party['status'] !== 'voting') {
            sendError('Bu parti için oylama dönemi sona ermiş');
        }
        
        // Kullanıcının partiye katılımcı olup olmadığını kontrol et
        $participantStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM party_participants 
            WHERE party_id = ? AND user_id = ?
        ");
        $participantStmt->execute([$partyId, $userId]);
        
        if ($participantStmt->fetchColumn() == 0) {
            sendError('Bu partiye katılmadınız');
        }
        
        // Oyunun parti oyunları arasında olup olmadığını kontrol et
        $gameStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM party_games 
            WHERE party_id = ? AND game_id = ?
        ");
        $gameStmt->execute([$partyId, $gameId]);
        
        if ($gameStmt->fetchColumn() == 0) {
            sendError('Bu oyun parti seçenekleri arasında değil');
        }
        
        // Önceki oyunu sil (kullanıcı oyunu değiştiriyor)
        $deleteStmt = $pdo->prepare("
            DELETE FROM game_votes 
            WHERE party_id = ? AND user_id = ?
        ");
        $deleteStmt->execute([$partyId, $userId]);
        
        // Yeni oyu ekle
        $voteStmt = $pdo->prepare("
            INSERT INTO game_votes (party_id, game_id, user_id) 
            VALUES (?, ?, ?)
        ");
        $voteStmt->execute([$partyId, $gameId, $userId]);
        
        // Oy sayısını getir
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM game_votes 
            WHERE party_id = ? AND game_id = ?
        ");
        $countStmt->execute([$partyId, $gameId]);
        $voteCount = $countStmt->fetchColumn();
        
        // Eğer parti durumu 'planning' ise 'voting' olarak güncelle
        if ($party['status'] === 'planning') {
            $updateStatusStmt = $pdo->prepare("
                UPDATE parties 
                SET status = 'voting' 
                WHERE party_id = ?
            ");
            $updateStatusStmt->execute([$partyId]);
        }
        
        // En çok oy alan oyunu kontrol et ve gerekirse parti oyununu güncelle
        checkAndUpdateSelectedGame($pdo, $partyId);
        
        $pdo->commit();
        
        sendResponse([
            'success' => true,
            'vote_count' => $voteCount,
            'message' => 'Oyunuz kaydedildi'
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}

function checkAndUpdateSelectedGame($pdo, $partyId) {
    // En çok oy alan oyunu bul
    $stmt = $pdo->prepare("
        SELECT 
            gv.game_id,
            COUNT(*) as vote_count,
            g.title
        FROM game_votes gv
        JOIN games g ON gv.game_id = g.game_id
        WHERE gv.party_id = ?
        GROUP BY gv.game_id
        ORDER BY vote_count DESC, g.title ASC
        LIMIT 1
    ");
    $stmt->execute([$partyId]);
    $topGame = $stmt->fetch();
    
    if ($topGame) {
        // Toplam katılımcı sayısını al
        $participantStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM party_participants 
            WHERE party_id = ?
        ");
        $participantStmt->execute([$partyId]);
        $totalParticipants = $participantStmt->fetchColumn();
        
        // Eğer en çok oy alan oyun katılımcıların yarısından fazla oy almışsa, otomatik seç
        if ($topGame['vote_count'] > ($totalParticipants / 2)) {
            $updateStmt = $pdo->prepare("
                UPDATE parties 
                SET selected_game_id = ? 
                WHERE party_id = ?
            ");
            $updateStmt->execute([$topGame['game_id'], $partyId]);
        }
    }
}
?>
