<?php
// Demo Veriler Oluşturucu
// 20 demo kullanıcı ve 4 demo parti oluşturur

require_once 'includes/config.php';

echo "<h1>🎮 Demo Veriler Oluşturuluyor...</h1>";

try {
    $pdo = getDBConnection();
    
    // Mevcut verileri temizle
    echo "<p>🧹 Mevcut demo veriler temizleniyor...</p>";
    
    $pdo->exec("DELETE FROM scores WHERE match_id IN (SELECT match_id FROM matches WHERE party_id IN (SELECT party_id FROM parties WHERE title LIKE 'Demo%'))");
    $pdo->exec("DELETE FROM matches WHERE party_id IN (SELECT party_id FROM parties WHERE title LIKE 'Demo%')");
    $pdo->exec("DELETE FROM slot_votes WHERE slot_id IN (SELECT slot_id FROM slots WHERE party_id IN (SELECT party_id FROM parties WHERE title LIKE 'Demo%'))");
    $pdo->exec("DELETE FROM slots WHERE party_id IN (SELECT party_id FROM parties WHERE title LIKE 'Demo%')");
    $pdo->exec("DELETE FROM game_votes WHERE party_id IN (SELECT party_id FROM parties WHERE title LIKE 'Demo%')");
    $pdo->exec("DELETE FROM party_games WHERE party_id IN (SELECT party_id FROM parties WHERE title LIKE 'Demo%')");
    $pdo->exec("DELETE FROM party_participants WHERE party_id IN (SELECT party_id FROM parties WHERE title LIKE 'Demo%')");
    $pdo->exec("DELETE FROM parties WHERE title LIKE 'Demo%'");
    $pdo->exec("DELETE FROM users WHERE name LIKE 'Demo%'");
    
    echo "<p style='color: green;'>✅ Temizlik tamamlandı!</p>";
    
    // 20 Demo Kullanıcı Oluştur
    echo "<p>👥 20 demo kullanıcı oluşturuluyor...</p>";
    
    $demoUsers = [
        ['Demo Ahmet', 'demo_ahmet', 'avatar1.png'],
        ['Demo Zehra', 'demo_zehra', 'avatar2.png'],
        ['Demo Mehmet', 'demo_mehmet', 'avatar3.png'],
        ['Demo Ayşe', 'demo_ayse', 'avatar4.png'],
        ['Demo Ali', 'demo_ali', 'avatar5.png'],
        ['Demo Fatma', 'demo_fatma', 'avatar6.png'],
        ['Demo Mustafa', 'demo_mustafa', 'avatar7.png'],
        ['Demo Zeynep', 'demo_zeynep', 'avatar8.png'],
        ['Demo Emre', 'demo_emre', 'avatar9.png'],
        ['Demo Elif', 'demo_elif', 'avatar10.png'],
        ['Demo Can', 'demo_can', 'avatar11.png'],
        ['Demo Seda', 'demo_seda', 'avatar12.png'],
        ['Demo Burak', 'demo_burak', 'avatar13.png'],
        ['Demo Merve', 'demo_merve', 'avatar14.png'],
        ['Demo Oğuz', 'demo_oguz', 'avatar15.png'],
        ['Demo Ceren', 'demo_ceren', 'avatar16.png'],
        ['Demo Kaan', 'demo_kaan', 'avatar17.png'],
        ['Demo Derya', 'demo_derya', 'avatar18.png'],
        ['Demo Tolga', 'demo_tolga', 'avatar19.png'],
        ['Demo Pınar', 'demo_pinar', 'avatar20.png']
    ];
    
    $userStmt = $pdo->prepare("INSERT INTO users (name, gamer_tag, avatar) VALUES (?, ?, ?)");
    $createdUsers = [];
    
    foreach ($demoUsers as $user) {
        $userStmt->execute($user);
        $createdUsers[] = $pdo->lastInsertId();
    }
    
    echo "<p style='color: green;'>✅ 20 demo kullanıcı oluşturuldu!</p>";
    
    // Oyunları getir
    $gameStmt = $pdo->query("SELECT game_id, title FROM games ORDER BY RAND() LIMIT 20");
    $games = $gameStmt->fetchAll();
    
    // 4 Demo Parti Oluştur
    echo "<p>🎉 4 demo parti oluşturuluyor...</p>";
    
    $demoParties = [
        [
            'title' => 'Demo FIFA Gecesi',
            'host_id' => $createdUsers[0],
            'date' => date('Y-m-d', strtotime('+1 day')),
            'status' => 'voting',
            'games' => array_slice($games, 0, 3) // İlk 3 oyun
        ],
        [
            'title' => 'Demo PUBG Turnuvası',
            'host_id' => $createdUsers[5],
            'date' => date('Y-m-d', strtotime('+2 days')),
            'status' => 'scheduled',
            'games' => array_slice($games, 3, 4) // Sonraki 4 oyun
        ],
        [
            'title' => 'Demo CS2 Maçları',
            'host_id' => $createdUsers[10],
            'date' => date('Y-m-d', strtotime('+3 days')),
            'status' => 'planning',
            'games' => array_slice($games, 7, 3) // Sonraki 3 oyun
        ],
        [
            'title' => 'Demo League of Legends',
            'host_id' => $createdUsers[15],
            'date' => date('Y-m-d', strtotime('+4 days')),
            'status' => 'completed',
            'games' => array_slice($games, 10, 2) // Sonraki 2 oyun
        ]
    ];
    
    $partyStmt = $pdo->prepare("
        INSERT INTO parties (title, host_id, party_date, status, invite_code) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $partyGameStmt = $pdo->prepare("
        INSERT INTO party_games (party_id, game_id) VALUES (?, ?)
    ");
    
    $participantStmt = $pdo->prepare("
        INSERT INTO party_participants (party_id, user_id) VALUES (?, ?)
    ");
    
    $slotStmt = $pdo->prepare("
        INSERT INTO slots (party_id, start_time, end_time, slot_date) 
        VALUES (?, ?, ?, ?)
    ");
    
    $gameVoteStmt = $pdo->prepare("
        INSERT INTO game_votes (party_id, game_id, user_id) VALUES (?, ?, ?)
    ");
    
    $slotVoteStmt = $pdo->prepare("
        INSERT INTO slot_votes (slot_id, user_id, choice) VALUES (?, ?, ?)
    ");
    
    $createdParties = [];
    
    foreach ($demoParties as $index => $party) {
        // Rastgele davet kodu oluştur
        $inviteCode = strtoupper(substr(md5($party['title'] . $index), 0, 8));
        
        // Parti oluştur
        $partyStmt->execute([
            $party['title'],
            $party['host_id'],
            $party['date'],
            $party['status'],
            $inviteCode
        ]);
        
        $partyId = $pdo->lastInsertId();
        $createdParties[] = $partyId;
        
        // Oyunları partiye ekle
        foreach ($party['games'] as $game) {
            $partyGameStmt->execute([$partyId, $game['game_id']]);
        }
        
        // Rastgele katılımcılar ekle (5-8 kişi)
        $participantCount = rand(5, 8);
        $selectedParticipants = array_rand($createdUsers, $participantCount);
        
        foreach ($selectedParticipants as $userId) {
            $participantStmt->execute([$partyId, $createdUsers[$userId]]);
        }
        
        // Zaman slotları ekle (2-3 slot)
        $slotCount = rand(2, 3);
        for ($i = 0; $i < $slotCount; $i++) {
            $startHour = 19 + ($i * 2); // 19:00, 21:00, 23:00
            $endHour = $startHour + 2;
            
            $slotStmt->execute([
                $partyId,
                sprintf('%02d:00:00', $startHour),
                sprintf('%02d:00:00', $endHour),
                $party['date']
            ]);
            
            $slotId = $pdo->lastInsertId();
            
            // Slot oyları ekle
            foreach ($selectedParticipants as $userId) {
                $choice = rand(0, 1) ? 'yes' : 'no'; // Rastgele oy
                $slotVoteStmt->execute([$slotId, $createdUsers[$userId], $choice]);
            }
        }
        
        // Oyun oyları ekle
        foreach ($party['games'] as $game) {
            $voteCount = rand(3, count($selectedParticipants));
            $votedUsers = array_rand($selectedParticipants, $voteCount);
            
            foreach ($votedUsers as $userId) {
                $gameVoteStmt->execute([$partyId, $game['game_id'], $createdUsers[$userId]]);
            }
        }
        
        echo "<p style='color: green;'>✅ {$party['title']} oluşturuldu! (ID: $partyId)</p>";
    }
    
    // Tamamlanmış parti için maç ve skor verileri ekle
    $completedPartyId = $createdParties[3]; // Son parti tamamlanmış
    
    // Maç oluştur
    $matchStmt = $pdo->prepare("
        INSERT INTO matches (party_id, game_id, match_date, status) 
        VALUES (?, ?, ?, 'completed')
    ");
    
    $scoreStmt = $pdo->prepare("
        INSERT INTO scores (match_id, user_id, score, result, points) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    // 2 maç oluştur
    for ($i = 0; $i < 2; $i++) {
        $gameId = $demoParties[3]['games'][$i]['game_id'];
        $matchDate = date('Y-m-d H:i:s', strtotime($demoParties[3]['date'] . ' ' . (19 + $i * 2) . ':00:00'));
        
        $matchStmt->execute([$completedPartyId, $gameId, $matchDate]);
        $matchId = $pdo->lastInsertId();
        
        // Skorlar ekle
        $participants = array_slice($createdUsers, 15, 6); // 6 katılımcı
        $scores = [];
        
        foreach ($participants as $userId) {
            $score = rand(0, 100);
            $scores[] = ['user_id' => $userId, 'score' => $score];
        }
        
        // Skorları sırala ve sonuçları belirle
        usort($scores, function($a, $b) { return $b['score'] - $a['score']; });
        
        foreach ($scores as $index => $scoreData) {
            $result = 'lose';
            $points = 0;
            
            if ($index === 0) {
                $result = 'win';
                $points = 3;
            } elseif ($index === 1 && $scoreData['score'] === $scores[0]['score']) {
                $result = 'draw';
                $points = 1;
            }
            
            $scoreStmt->execute([
                $matchId,
                $scoreData['user_id'],
                $scoreData['score'],
                $result,
                $points
            ]);
        }
    }
    
    echo "<p style='color: green;'>✅ Tamamlanmış parti için maç ve skor verileri eklendi!</p>";
    
    // Özet
    echo "<hr>";
    echo "<h2>🎉 Demo Veriler Başarıyla Oluşturuldu!</h2>";
    echo "<div style='background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>📊 Oluşturulan Veriler:</h3>";
    echo "<ul>";
    echo "<li><strong>Kullanıcılar:</strong> 20 demo kullanıcı</li>";
    echo "<li><strong>Partiler:</strong> 4 demo parti</li>";
    echo "<li><strong>Oyunlar:</strong> Her partiye 2-4 oyun eklendi</li>";
    echo "<li><strong>Katılımcılar:</strong> Her partiye 5-8 katılımcı eklendi</li>";
    echo "<li><strong>Zaman Slotları:</strong> Her partiye 2-3 slot eklendi</li>";
    echo "<li><strong>Oylar:</strong> Oyun ve slot oyları eklendi</li>";
    echo "<li><strong>Maçlar:</strong> Tamamlanmış parti için 2 maç eklendi</li>";
    echo "<li><strong>Skorlar:</strong> Maç sonuçları ve puanlar eklendi</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>🎮 Demo Partiler:</h3>";
    echo "<ol>";
    echo "<li><strong>Demo FIFA Gecesi</strong> - Durum: Oylama Aşaması</li>";
    echo "<li><strong>Demo PUBG Turnuvası</strong> - Durum: Zamanlandı</li>";
    echo "<li><strong>Demo CS2 Maçları</strong> - Durum: Planlama Aşaması</li>";
    echo "<li><strong>Demo League of Legends</strong> - Durum: Tamamlandı</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<p><a href='index.html' style='padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>🎮 Ana Sayfaya Git</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 40px; 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    color: white; 
}
h1, h2, h3 { color: white; }
p, li { background: rgba(255,255,255,0.1); padding: 10px; border-radius: 5px; margin: 10px 0; }
ul, ol { background: rgba(255,255,255,0.1); padding: 20px; border-radius: 5px; }
hr { border: 1px solid rgba(255,255,255,0.3); }
a { display: inline-block; margin: 10px 0; }
</style>";
?>
