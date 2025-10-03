<?php
// Liderlik Tablosu API - Parti ve genel liderlik tabloları

require_once '../includes/config.php';

// Sadece GET istekleri kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Sadece GET isteği kabul edilir');
}

$partyId = intval($_GET['party_id'] ?? 0);
$type = $_GET['type'] ?? 'party'; // 'party' veya 'global'

if ($type === 'party') {
    if ($partyId <= 0) {
        sendError('Parti ID gerekli');
    }
    getPartyLeaderboard($partyId);
} else {
    getGlobalLeaderboard();
}

function getPartyLeaderboard($partyId) {
    try {
        $pdo = getDBConnection();
        
        // Parti kontrolü
        $partyStmt = $pdo->prepare("SELECT title FROM parties WHERE party_id = ?");
        $partyStmt->execute([$partyId]);
        $party = $partyStmt->fetch();
        
        if (!$party) {
            sendError('Parti bulunamadı', 404);
        }
        
        // Liderlik tablosu
        $leaderboardStmt = $pdo->prepare("
            SELECT 
                u.user_id,
                u.name,
                u.gamer_tag,
                u.avatar,
                COALESCE(SUM(s.points), 0) as total_points,
                COUNT(DISTINCT s.match_id) as matches_played,
                SUM(CASE WHEN s.result = 'win' THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN s.result = 'draw' THEN 1 ELSE 0 END) as draws,
                SUM(CASE WHEN s.result = 'lose' THEN 1 ELSE 0 END) as losses,
                COALESCE(SUM(s.score), 0) as total_score,
                CASE 
                    WHEN COUNT(DISTINCT s.match_id) > 0 
                    THEN ROUND(COALESCE(SUM(s.score), 0) / COUNT(DISTINCT s.match_id), 2)
                    ELSE 0 
                END as avg_score,
                CASE 
                    WHEN SUM(CASE WHEN s.result IN ('win', 'draw', 'lose') THEN 1 ELSE 0 END) > 0
                    THEN ROUND((SUM(CASE WHEN s.result = 'win' THEN 1 ELSE 0 END) * 100.0) / 
                         SUM(CASE WHEN s.result IN ('win', 'draw', 'lose') THEN 1 ELSE 0 END), 1)
                    ELSE 0
                END as win_percentage
            FROM party_participants pp
            JOIN users u ON pp.user_id = u.user_id
            LEFT JOIN scores s ON u.user_id = s.user_id 
                AND s.match_id IN (
                    SELECT match_id FROM matches WHERE party_id = ?
                )
            WHERE pp.party_id = ?
            GROUP BY u.user_id, u.name, u.gamer_tag, u.avatar
            ORDER BY total_points DESC, wins DESC, avg_score DESC, u.name ASC
        ");
        $leaderboardStmt->execute([$partyId, $partyId]);
        $leaderboard = $leaderboardStmt->fetchAll();
        
        // Sıralama ekle
        foreach ($leaderboard as $index => &$player) {
            $player['rank'] = $index + 1;
            
            // Başarı rozetleri
            $player['achievements'] = [];
            
            if ($player['wins'] >= 3) {
                $player['achievements'][] = [
                    'name' => 'Hot Streak',
                    'description' => '3+ galibiyet',
                    'icon' => '🔥'
                ];
            }
            
            if ($player['win_percentage'] >= 80 && $player['matches_played'] >= 3) {
                $player['achievements'][] = [
                    'name' => 'Dominator',
                    'description' => '%80+ galibiyet oranı',
                    'icon' => '👑'
                ];
            }
            
            if ($player['matches_played'] >= 5) {
                $player['achievements'][] = [
                    'name' => 'Dedicated',
                    'description' => '5+ maç',
                    'icon' => '🎯'
                ];
            }
            
            if ($player['avg_score'] >= 50) {
                $player['achievements'][] = [
                    'name' => 'High Scorer',
                    'description' => '50+ ortalama skor',
                    'icon' => '⭐'
                ];
            }
        }
        
        // Parti istatistikleri
        $statsStmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT m.match_id) as total_matches,
                COUNT(DISTINCT pp.user_id) as total_players,
                COALESCE(SUM(s.score), 0) as total_scores,
                MAX(s.score) as highest_score,
                AVG(s.score) as avg_score
            FROM parties p
            LEFT JOIN party_participants pp ON p.party_id = pp.party_id
            LEFT JOIN matches m ON p.party_id = m.party_id AND m.status = 'completed'
            LEFT JOIN scores s ON m.match_id = s.match_id
            WHERE p.party_id = ?
        ");
        $statsStmt->execute([$partyId]);
        $stats = $statsStmt->fetch();
        
        sendResponse([
            'success' => true,
            'party_title' => $party['title'],
            'leaderboard' => $leaderboard,
            'stats' => $stats
        ]);
        
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}

function getGlobalLeaderboard() {
    try {
        $pdo = getDBConnection();
        
        // Genel liderlik tablosu
        $leaderboardStmt = $pdo->prepare("
            SELECT 
                u.user_id,
                u.name,
                u.gamer_tag,
                u.avatar,
                COALESCE(SUM(s.points), 0) as total_points,
                COUNT(DISTINCT s.match_id) as matches_played,
                COUNT(DISTINCT m.party_id) as parties_joined,
                SUM(CASE WHEN s.result = 'win' THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN s.result = 'draw' THEN 1 ELSE 0 END) as draws,
                SUM(CASE WHEN s.result = 'lose' THEN 1 ELSE 0 END) as losses,
                COALESCE(SUM(s.score), 0) as total_score,
                CASE 
                    WHEN COUNT(DISTINCT s.match_id) > 0 
                    THEN ROUND(COALESCE(SUM(s.score), 0) / COUNT(DISTINCT s.match_id), 2)
                    ELSE 0 
                END as avg_score,
                CASE 
                    WHEN SUM(CASE WHEN s.result IN ('win', 'draw', 'lose') THEN 1 ELSE 0 END) > 0
                    THEN ROUND((SUM(CASE WHEN s.result = 'win' THEN 1 ELSE 0 END) * 100.0) / 
                         SUM(CASE WHEN s.result IN ('win', 'draw', 'lose') THEN 1 ELSE 0 END), 1)
                    ELSE 0
                END as win_percentage
            FROM users u
            LEFT JOIN scores s ON u.user_id = s.user_id
            LEFT JOIN matches m ON s.match_id = m.match_id
            WHERE u.user_id IN (
                SELECT DISTINCT user_id FROM party_participants
            )
            GROUP BY u.user_id, u.name, u.gamer_tag, u.avatar
            HAVING matches_played > 0
            ORDER BY total_points DESC, wins DESC, avg_score DESC, u.name ASC
            LIMIT 50
        ");
        $leaderboardStmt->execute();
        $leaderboard = $leaderboardStmt->fetchAll();
        
        // Sıralama ve rozetler ekle
        foreach ($leaderboard as $index => &$player) {
            $player['rank'] = $index + 1;
            
            // Global başarı rozetleri
            $player['achievements'] = [];
            
            if ($player['parties_joined'] >= 10) {
                $player['achievements'][] = [
                    'name' => 'Party Animal',
                    'description' => '10+ parti',
                    'icon' => '🎉'
                ];
            }
            
            if ($player['total_points'] >= 100) {
                $player['achievements'][] = [
                    'name' => 'Century Club',
                    'description' => '100+ puan',
                    'icon' => '💯'
                ];
            }
            
            if ($player['rank'] <= 3) {
                $icons = ['🥇', '🥈', '🥉'];
                $player['achievements'][] = [
                    'name' => 'Top 3',
                    'description' => 'Genel sıralama top 3',
                    'icon' => $icons[$player['rank'] - 1]
                ];
            }
        }
        
        // Genel istatistikler
        $globalStatsStmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT p.party_id) as total_parties,
                COUNT(DISTINCT u.user_id) as total_players,
                COUNT(DISTINCT m.match_id) as total_matches,
                COALESCE(SUM(s.score), 0) as total_scores,
                MAX(s.score) as highest_score,
                AVG(s.score) as avg_score
            FROM parties p
            LEFT JOIN party_participants pp ON p.party_id = pp.party_id
            LEFT JOIN users u ON pp.user_id = u.user_id
            LEFT JOIN matches m ON p.party_id = m.party_id AND m.status = 'completed'
            LEFT JOIN scores s ON m.match_id = s.match_id
        ");
        $globalStatsStmt->execute();
        $globalStats = $globalStatsStmt->fetch();
        
        sendResponse([
            'success' => true,
            'leaderboard' => $leaderboard,
            'stats' => $globalStats
        ]);
        
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}
?>
