<?php
// Slot API - Zaman slotu oluşturma ve oylama

require_once '../includes/config.php';

// POST istekleri - Slot işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendError('Geçersiz JSON verisi');
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create':
            createSlot($input);
            break;
        case 'vote':
            voteForSlot($input);
            break;
        case 'finalize':
            finalizeSlot($input);
            break;
        default:
            sendError('Geçersiz işlem');
    }
}

function createSlot($data) {
    $partyId = intval($data['party_id'] ?? 0);
    $startTime = $data['start_time'] ?? '';
    $endTime = $data['end_time'] ?? '';
    $slotDate = $data['slot_date'] ?? '';
    $hostId = intval($data['host_id'] ?? 0);
    
    if ($partyId <= 0 || empty($startTime) || empty($endTime) || empty($slotDate) || $hostId <= 0) {
        sendError('Tüm alanlar gerekli');
    }
    
    // Zaman kontrolü
    if (strtotime($startTime) >= strtotime($endTime)) {
        sendError('Başlangıç saati bitiş saatinden önce olmalı');
    }
    
    try {
        $pdo = getDBConnection();
        
        // Host kontrolü
        $partyStmt = $pdo->prepare("
            SELECT host_id, status 
            FROM parties 
            WHERE party_id = ?
        ");
        $partyStmt->execute([$partyId]);
        $party = $partyStmt->fetch();
        
        if (!$party) {
            sendError('Parti bulunamadı');
        }
        
        if ($party['host_id'] != $hostId) {
            sendError('Sadece host slot ekleyebilir');
        }
        
        if ($party['status'] === 'completed') {
            sendError('Tamamlanmış partiye slot eklenemez');
        }
        
        // Çakışan slot kontrolü
        $conflictStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM slots 
            WHERE party_id = ? 
            AND slot_date = ?
            AND (
                (start_time <= ? AND end_time > ?) OR
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?)
            )
        ");
        $conflictStmt->execute([
            $partyId, $slotDate,
            $startTime, $startTime,
            $endTime, $endTime,
            $startTime, $endTime
        ]);
        
        if ($conflictStmt->fetchColumn() > 0) {
            sendError('Bu zaman aralığında çakışan slot mevcut');
        }
        
        // Slot oluştur
        $insertStmt = $pdo->prepare("
            INSERT INTO slots (party_id, start_time, end_time, slot_date) 
            VALUES (?, ?, ?, ?)
        ");
        $insertStmt->execute([$partyId, $startTime, $endTime, $slotDate]);
        $slotId = $pdo->lastInsertId();
        
        sendResponse([
            'success' => true,
            'slot_id' => $slotId,
            'message' => 'Zaman slotu eklendi'
        ]);
        
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}

function voteForSlot($data) {
    $slotId = intval($data['slot_id'] ?? 0);
    $userId = intval($data['user_id'] ?? 0);
    $choice = $data['choice'] ?? ''; // 'yes' veya 'no'
    
    if ($slotId <= 0 || $userId <= 0 || !in_array($choice, ['yes', 'no'])) {
        sendError('Geçersiz veri');
    }
    
    try {
        $pdo = getDBConnection();
        
        // Slot ve parti kontrolü
        $slotStmt = $pdo->prepare("
            SELECT s.party_id, p.status 
            FROM slots s
            JOIN parties p ON s.party_id = p.party_id
            WHERE s.slot_id = ?
        ");
        $slotStmt->execute([$slotId]);
        $slotInfo = $slotStmt->fetch();
        
        if (!$slotInfo) {
            sendError('Slot bulunamadı');
        }
        
        if ($slotInfo['status'] === 'completed') {
            sendError('Tamamlanmış parti için slot oylaması yapılamaz');
        }
        
        // Katılımcı kontrolü
        $participantStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM party_participants 
            WHERE party_id = ? AND user_id = ?
        ");
        $participantStmt->execute([$slotInfo['party_id'], $userId]);
        
        if ($participantStmt->fetchColumn() == 0) {
            sendError('Bu partiye katılmadınız');
        }
        
        // Önceki oyu sil ve yeni oy ekle
        $deleteStmt = $pdo->prepare("
            DELETE FROM slot_votes 
            WHERE slot_id = ? AND user_id = ?
        ");
        $deleteStmt->execute([$slotId, $userId]);
        
        $insertStmt = $pdo->prepare("
            INSERT INTO slot_votes (slot_id, user_id, choice) 
            VALUES (?, ?, ?)
        ");
        $insertStmt->execute([$slotId, $userId, $choice]);
        
        // Oy sayılarını getir
        $countStmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN choice = 'yes' THEN 1 ELSE 0 END) as yes_votes,
                SUM(CASE WHEN choice = 'no' THEN 1 ELSE 0 END) as no_votes
            FROM slot_votes 
            WHERE slot_id = ?
        ");
        $countStmt->execute([$slotId]);
        $votes = $countStmt->fetch();
        
        sendResponse([
            'success' => true,
            'yes_votes' => $votes['yes_votes'],
            'no_votes' => $votes['no_votes'],
            'message' => 'Oyunuz kaydedildi'
        ]);
        
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}

function finalizeSlot($data) {
    $partyId = intval($data['party_id'] ?? 0);
    $slotId = intval($data['slot_id'] ?? 0);
    $hostId = intval($data['host_id'] ?? 0);
    
    if ($partyId <= 0 || $slotId <= 0 || $hostId <= 0) {
        sendError('Geçersiz veri');
    }
    
    try {
        $pdo = getDBConnection();
        
        // Host kontrolü
        $partyStmt = $pdo->prepare("
            SELECT host_id, status 
            FROM parties 
            WHERE party_id = ?
        ");
        $partyStmt->execute([$partyId]);
        $party = $partyStmt->fetch();
        
        if (!$party || $party['host_id'] != $hostId) {
            sendError('Bu işlemi yapma yetkiniz yok');
        }
        
        // Slot kontrolü
        $slotStmt = $pdo->prepare("
            SELECT * 
            FROM slots 
            WHERE slot_id = ? AND party_id = ?
        ");
        $slotStmt->execute([$slotId, $partyId]);
        $slot = $slotStmt->fetch();
        
        if (!$slot) {
            sendError('Slot bulunamadı');
        }
        
        // Parti güncelle
        $updateStmt = $pdo->prepare("
            UPDATE parties 
            SET selected_slot_id = ?, status = 'scheduled' 
            WHERE party_id = ?
        ");
        $updateStmt->execute([$slotId, $partyId]);
        
        sendResponse([
            'success' => true,
            'message' => 'Parti zamanlandı'
        ]);
        
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}
?>
