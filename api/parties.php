<?php
// Parti API - Parti oluşturma, listeleme ve yönetimi

require_once '../includes/config.php';

// GET istekleri - Parti listesi
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = $_GET['status'] ?? '';
    $date = $_GET['date'] ?? '';
    getParties($status, $date);
}

// POST istekleri - Parti işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendError('Geçersiz JSON verisi');
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create':
            createParty($input);
            break;
        case 'join':
            joinPartyByCode($input);
            break;
        case 'join_by_id':
            joinPartyById($input);
            break;
        case 'update_status':
            updatePartyStatus($input);
            break;
        default:
            sendError('Geçersiz işlem');
    }
}

function getParties($status = '', $date = '') {
    try {
        $pdo = getDBConnection();
        
        $sql = "
            SELECT 
                p.party_id,
                p.title,
                p.party_date,
                p.status,
                p.invite_code,
                p.created_at,
                u.name as host_name,
                u.gamer_tag as host_gamer_tag,
                g.title as selected_game_title,
                COUNT(pp.user_id) as participant_count
            FROM parties p
            LEFT JOIN users u ON p.host_id = u.user_id
            LEFT JOIN games g ON p.selected_game_id = g.game_id
            LEFT JOIN party_participants pp ON p.party_id = pp.party_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($status)) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }
        
        if (!empty($date)) {
            $sql .= " AND p.party_date = ?";
            $params[] = $date;
        }
        
        $sql .= " GROUP BY p.party_id ORDER BY p.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $parties = $stmt->fetchAll();
        
        sendResponse([
            'success' => true,
            'parties' => $parties
        ]);
        
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}

function createParty($data) {
    $title = trim($data['title'] ?? '');
    $date = $data['date'] ?? '';
    $hostId = intval($data['host_id'] ?? 0);
    $gameIds = $data['game_ids'] ?? [];
    $timeSlots = $data['time_slots'] ?? [];
    
    if (empty($title) || empty($date) || $hostId <= 0 || count($gameIds) < 3) {
        sendError('Tüm alanlar gerekli ve en az 3 oyun seçilmeli');
    }
    
    // Tarih kontrolü - geçmiş tarih olamaz
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        sendError('Geçmiş tarih seçilemez');
    }
    
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        
        // Benzersiz davet kodu oluştur
        do {
            $inviteCode = generateInviteCode();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM parties WHERE invite_code = ?");
            $stmt->execute([$inviteCode]);
        } while ($stmt->fetchColumn() > 0);
        
        // Parti oluştur
        $stmt = $pdo->prepare("
            INSERT INTO parties (title, host_id, party_date, invite_code, status) 
            VALUES (?, ?, ?, ?, 'planning')
        ");
        
        $stmt->execute([$title, $hostId, $date, $inviteCode]);
        $partyId = $pdo->lastInsertId();
        
        // Host'u otomatik katılımcı olarak ekle
        $stmt = $pdo->prepare("
            INSERT INTO party_participants (party_id, user_id) 
            VALUES (?, ?)
        ");
        $stmt->execute([$partyId, $hostId]);
        
        // Seçilen oyunları ekle
        $stmt = $pdo->prepare("
            INSERT INTO party_games (party_id, game_id) 
            VALUES (?, ?)
        ");
        
        foreach ($gameIds as $gameId) {
            $stmt->execute([$partyId, intval($gameId)]);
        }

        // Zaman slotlarını ekle
        if (!empty($timeSlots)) {
            $slotStmt = $pdo->prepare("
                INSERT INTO slots (party_id, start_time, end_time, slot_date)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($timeSlots as $slot) {
                $startTime = $slot['start_time'];
                $endTime = $slot['end_time'];
                
                // Zaman doğrulama
                if (strtotime($startTime) >= strtotime($endTime)) {
                    $pdo->rollBack();
                    sendError('Başlangıç saati bitiş saatinden önce olmalı');
                }
                
                $slotStmt->execute([$partyId, $startTime, $endTime, $date]);
            }
        }
        
        $pdo->commit();
        
        sendResponse([
            'success' => true,
            'party_id' => $partyId,
            'invite_code' => $inviteCode,
            'message' => 'Parti başarıyla oluşturuldu'
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}

function joinPartyByCode($data) {
    $inviteCode = strtoupper(trim($data['invite_code'] ?? ''));
    $userId = intval($data['user_id'] ?? 0);
    
    if (empty($inviteCode) || $userId <= 0) {
        sendError('Davet kodu ve kullanıcı ID gerekli');
    }
    
    try {
        $pdo = getDBConnection();
        
        // Parti kontrolü
        $stmt = $pdo->prepare("
            SELECT party_id, status, title 
            FROM parties 
            WHERE invite_code = ?
        ");
        $stmt->execute([$inviteCode]);
        $party = $stmt->fetch();
        
        if (!$party) {
            sendError('Geçersiz davet kodu');
        }
        
        if ($party['status'] === 'completed') {
            sendError('Bu parti tamamlanmış');
        }
        
        // Zaten katılımcı mı kontrol et
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM party_participants 
            WHERE party_id = ? AND user_id = ?
        ");
        $stmt->execute([$party['party_id'], $userId]);
        
        if ($stmt->fetchColumn() > 0) {
            sendError('Bu partiye zaten katıldınız');
        }
        
        // Katılımcı ekle
        $stmt = $pdo->prepare("
            INSERT INTO party_participants (party_id, user_id) 
            VALUES (?, ?)
        ");
        $stmt->execute([$party['party_id'], $userId]);
        
        sendResponse([
            'success' => true,
            'party_title' => $party['title'],
            'message' => 'Partiye başarıyla katıldınız'
        ]);
        
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}

function joinPartyById($data) {
    $partyId = intval($data['party_id'] ?? 0);
    $userId = intval($data['user_id'] ?? 0);
    
    if ($partyId <= 0 || $userId <= 0) {
        sendError('Parti ID ve kullanıcı ID gerekli');
    }
    
    try {
        $pdo = getDBConnection();
        
        // Parti kontrolü
        $stmt = $pdo->prepare("
            SELECT status, title 
            FROM parties 
            WHERE party_id = ?
        ");
        $stmt->execute([$partyId]);
        $party = $stmt->fetch();
        
        if (!$party) {
            sendError('Parti bulunamadı');
        }
        
        if ($party['status'] === 'completed') {
            sendError('Bu parti tamamlanmış');
        }
        
        // Zaten katılımcı mı kontrol et
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM party_participants 
            WHERE party_id = ? AND user_id = ?
        ");
        $stmt->execute([$partyId, $userId]);
        
        if ($stmt->fetchColumn() > 0) {
            sendError('Bu partiye zaten katıldınız');
        }
        
        // Katılımcı ekle
        $stmt = $pdo->prepare("
            INSERT INTO party_participants (party_id, user_id) 
            VALUES (?, ?)
        ");
        $stmt->execute([$partyId, $userId]);
        
        sendResponse([
            'success' => true,
            'party_title' => $party['title'],
            'message' => 'Partiye başarıyla katıldınız'
        ]);
        
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}

function updatePartyStatus($data) {
    $partyId = intval($data['party_id'] ?? 0);
    $status = $data['status'] ?? '';
    $hostId = intval($data['host_id'] ?? 0);
    
    $allowedStatuses = ['planning', 'voting', 'scheduled', 'completed'];
    
    if ($partyId <= 0 || !in_array($status, $allowedStatuses) || $hostId <= 0) {
        sendError('Geçersiz veri');
    }
    
    try {
        $pdo = getDBConnection();
        
        // Host kontrolü
        $stmt = $pdo->prepare("
            SELECT host_id 
            FROM parties 
            WHERE party_id = ?
        ");
        $stmt->execute([$partyId]);
        $party = $stmt->fetch();
        
        if (!$party || $party['host_id'] != $hostId) {
            sendError('Bu işlemi yapma yetkiniz yok');
        }
        
        // Status güncelle
        $stmt = $pdo->prepare("
            UPDATE parties 
            SET status = ? 
            WHERE party_id = ?
        ");
        $stmt->execute([$status, $partyId]);
        
        sendResponse([
            'success' => true,
            'message' => 'Parti durumu güncellendi'
        ]);
        
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}
?>
