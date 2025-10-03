<?php
// Kullanıcı API - Kullanıcı oluşturma ve yönetimi

require_once '../includes/config.php';

// POST istekleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendError('Geçersiz JSON verisi');
    }
    
    $action = $input['action'] ?? '';
    
    if ($action === 'create_or_get') {
        createOrGetUser($input);
    } else {
        sendError('Geçersiz işlem');
    }
}

function createOrGetUser($data) {
    $name = trim($data['name'] ?? '');
    $gamerTag = trim($data['gamer_tag'] ?? '');
    
    if (empty($name) || empty($gamerTag)) {
        sendError('Ad ve gamer tag gerekli');
    }
    
    try {
        $pdo = getDBConnection();
        
        // Önce mevcut kullanıcıyı kontrol et
        $stmt = $pdo->prepare("SELECT * FROM users WHERE gamer_tag = ?");
        $stmt->execute([$gamerTag]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            // Mevcut kullanıcıyı döndür
            sendResponse([
                'success' => true,
                'user' => $existingUser,
                'message' => 'Mevcut kullanıcı bulundu'
            ]);
        } else {
            // Yeni kullanıcı oluştur
            $stmt = $pdo->prepare("INSERT INTO users (name, gamer_tag) VALUES (?, ?)");
            $stmt->execute([$name, $gamerTag]);
            
            $userId = $pdo->lastInsertId();
            
            // Oluşturulan kullanıcıyı getir
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $newUser = $stmt->fetch();
            
            sendResponse([
                'success' => true,
                'user' => $newUser,
                'message' => 'Yeni kullanıcı oluşturuldu'
            ]);
        }
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            sendError('Bu gamer tag zaten kullanımda');
        } else {
            sendError('Veritabanı hatası: ' . $e->getMessage());
        }
    }
}

// GET istekleri
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = $_GET['user_id'] ?? '';
    $gamerTag = $_GET['gamer_tag'] ?? '';
    
    if ($userId) {
        getUserById($userId);
    } elseif ($gamerTag) {
        getUserByGamerTag($gamerTag);
    } else {
        getAllUsers();
    }
}

function getUserById($userId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            sendResponse(['success' => true, 'user' => $user]);
        } else {
            sendError('Kullanıcı bulunamadı', 404);
        }
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}

function getUserByGamerTag($gamerTag) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE gamer_tag = ?");
        $stmt->execute([$gamerTag]);
        $user = $stmt->fetch();
        
        if ($user) {
            sendResponse(['success' => true, 'user' => $user]);
        } else {
            sendResponse(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
        }
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}

function getAllUsers() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT user_id, name, gamer_tag, avatar, created_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll();
        
        sendResponse(['success' => true, 'users' => $users]);
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}
?>
