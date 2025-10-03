<?php
// Game+ Party Hub - Otomatik Kurulum Script'i

echo "<h1>Game+ Party Hub - Kurulum</h1>";
echo "<p>Veritabanı bağlantısı test ediliyor...</p>";

// Config dosyasını yükle
require_once 'includes/config.php';

try {
    // Veritabanı bağlantısını test et
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✅ Veritabanı bağlantısı başarılı!</p>";
    
    // Tabloları kontrol et
    $tables = [
        'users', 'games', 'parties', 'party_games', 'game_votes', 
        'slots', 'slot_votes', 'matches', 'scores', 'party_participants'
    ];
    
    $existingTables = [];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $existingTables[] = $table;
        }
    }
    
    if (count($existingTables) === count($tables)) {
        echo "<p style='color: green;'>✅ Tüm tablolar mevcut!</p>";
        
        // Örnek verileri kontrol et
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM games");
        $gameCount = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $userCount = $stmt->fetch()['count'];
        
        echo "<p>📊 Mevcut veriler:</p>";
        echo "<ul>";
        echo "<li>Oyunlar: $gameCount adet</li>";
        echo "<li>Kullanıcılar: $userCount adet</li>";
        echo "</ul>";
        
        if ($gameCount == 0) {
            echo "<p style='color: orange;'>⚠️ Oyun verisi bulunamadı. database.sql dosyasını import etmeyi unutmayın!</p>";
        } elseif ($gameCount < 20) {
            echo "<p style='color: orange;'>⚠️ Sadece $gameCount oyun bulundu. Tüm popüler oyunlar için database.sql dosyasını yeniden import edin!</p>";
        } else {
            echo "<p style='color: green;'>✅ Popüler oyunlar yüklendi! ($gameCount adet)</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Bazı tablolar eksik!</p>";
        echo "<p>Eksik tablolar: " . implode(', ', array_diff($tables, $existingTables)) . "</p>";
        echo "<p><strong>Çözüm:</strong> database.sql dosyasını phpMyAdmin'den import edin.</p>";
    }
    
    // Dosya izinlerini kontrol et
    $writableDirs = ['images/games', 'images/avatars'];
    foreach ($writableDirs as $dir) {
        if (is_writable($dir)) {
            echo "<p style='color: green;'>✅ $dir klasörü yazılabilir</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ $dir klasörü yazılabilir değil (resim yükleme çalışmayabilir)</p>";
        }
    }
    
    echo "<hr>";
    echo "<h2>🎮 Kurulum Tamamlandı!</h2>";
    echo "<p><a href='index.html' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Ana Sayfaya Git</a></p>";
    echo "<p><a href='scores.html' style='padding: 10px 20px; background: #4facfe; color: white; text-decoration: none; border-radius: 5px;'>Skor Sayfasına Git</a></p>";
    
    echo "<h3>📝 Sonraki Adımlar:</h3>";
    echo "<ol>";
    echo "<li>Ana sayfada kullanıcı kaydı yapın</li>";
    echo "<li>Yeni parti oluşturun</li>";
    echo "<li>Arkadaşlarınızı davet edin</li>";
    echo "<li>Oyun oylama yapın</li>";
    echo "<li>Zaman slotu planlayın</li>";
    echo "<li>Maç sonuçlarını girin</li>";
    echo "<li>Liderlik tablosunu takip edin</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
    echo "<h3>Çözüm Önerileri:</h3>";
    echo "<ul>";
    echo "<li>MySQL servisinin çalıştığından emin olun</li>";
    echo "<li>includes/config.php dosyasındaki veritabanı ayarlarını kontrol edin</li>";
    echo "<li>game_party_hub veritabanının oluşturulduğundan emin olun</li>";
    echo "<li>database.sql dosyasını phpMyAdmin'den import edin</li>";
    echo "</ul>";
}

echo "<style>
body { font-family: Arial, sans-serif; margin: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
h1, h2, h3 { color: white; }
p, li { background: rgba(255,255,255,0.1); padding: 10px; border-radius: 5px; margin: 10px 0; }
ul, ol { background: rgba(255,255,255,0.1); padding: 20px; border-radius: 5px; }
hr { border: 1px solid rgba(255,255,255,0.3); }
</style>";
?>
