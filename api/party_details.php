<?php
// Parti Detay API - Belirli bir partinin tüm detaylarını getir

require_once '../includes/config.php';

// Sadece GET istekleri kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Sadece GET isteği kabul edilir');
}

$partyId = intval($_GET['party_id'] ?? 0);

if ($partyId <= 0) {
    sendError('Geçerli parti ID gerekli');
}

getPartyDetails($partyId);

function getPartyDetails($partyId) {
    try {
        $pdo = getDBConnection();
        
        // Parti bilgilerini getir
        $partyStmt = $pdo->prepare("
            SELECT 
                p.*,
                u.name as host_name,
                u.gamer_tag as host_gamer_tag,
                g.title as selected_game_title
            FROM parties p
            LEFT JOIN users u ON p.host_id = u.user_id
            LEFT JOIN games g ON p.selected_game_id = g.game_id
            WHERE p.party_id = ?
        ");
        $partyStmt->execute([$partyId]);
        $party = $partyStmt->fetch();
        
        if (!$party) {
            sendError('Parti bulunamadı', 404);
        }
        
        // Parti oyunlarını ve oy sayılarını getir
        $gamesStmt = $pdo->prepare("
            SELECT 
                g.*,
                COALESCE(vote_counts.vote_count, 0) as vote_count
            FROM party_games pg
            JOIN games g ON pg.game_id = g.game_id
            LEFT JOIN (
                SELECT 
                    game_id, 
                    COUNT(*) as vote_count
                FROM game_votes 
                WHERE party_id = ?
                GROUP BY game_id
            ) vote_counts ON g.game_id = vote_counts.game_id
            WHERE pg.party_id = ?
            ORDER BY vote_counts.vote_count DESC, g.title ASC
        ");
        $gamesStmt->execute([$partyId, $partyId]);
        $games = $gamesStmt->fetchAll();
        
        // Zaman slotlarını ve oy sayılarını getir
        $slotsStmt = $pdo->prepare("
            SELECT 
                s.*,
                COALESCE(yes_votes.yes_votes, 0) as yes_votes,
                COALESCE(no_votes.no_votes, 0) as no_votes,
                COALESCE(total_votes.total_votes, 0) as total_votes
            FROM slots s
            LEFT JOIN (
                SELECT 
                    slot_id, 
                    COUNT(*) as yes_votes
                FROM slot_votes 
                WHERE choice = 'yes'
                GROUP BY slot_id
            ) yes_votes ON s.slot_id = yes_votes.slot_id
            LEFT JOIN (
                SELECT 
                    slot_id, 
                    COUNT(*) as no_votes
                FROM slot_votes 
                WHERE choice = 'no'
                GROUP BY slot_id
            ) no_votes ON s.slot_id = no_votes.slot_id
            LEFT JOIN (
                SELECT 
                    slot_id, 
                    COUNT(*) as total_votes
                FROM slot_votes 
                GROUP BY slot_id
            ) total_votes ON s.slot_id = total_votes.slot_id
            WHERE s.party_id = ?
            ORDER BY s.start_time ASC
        ");
        $slotsStmt->execute([$partyId]);
        $slots = $slotsStmt->fetchAll();
        
        // Her slot için katılımcı oylarını getir
        $currentUserId = intval($_GET['user_id'] ?? 0);
        foreach ($slots as &$slot) {
            $participantVotesStmt = $pdo->prepare("
                SELECT 
                    u.name,
                    u.gamer_tag,
                    sv.choice as vote
                FROM slot_votes sv
                JOIN users u ON sv.user_id = u.user_id
                WHERE sv.slot_id = ?
                ORDER BY u.name ASC
            ");
            $participantVotesStmt->execute([$slot['slot_id']]);
            $slot['participants'] = $participantVotesStmt->fetchAll();
            
            // Mevcut kullanıcının oyunu
            if ($currentUserId > 0) {
                $userVoteStmt = $pdo->prepare("
                    SELECT choice FROM slot_votes 
                    WHERE slot_id = ? AND user_id = ?
                ");
                $userVoteStmt->execute([$slot['slot_id'], $currentUserId]);
                $userVote = $userVoteStmt->fetch();
                $slot['user_vote'] = $userVote ? $userVote['choice'] : null;
            }
        }
        
        // Katılımcıları getir
        $participantsStmt = $pdo->prepare("
            SELECT 
                u.user_id,
                u.name,
                u.gamer_tag,
                u.avatar,
                pp.joined_at,
                CASE WHEN u.user_id = p.host_id THEN 1 ELSE 0 END as is_host
            FROM party_participants pp
            JOIN users u ON pp.user_id = u.user_id
            JOIN parties p ON pp.party_id = p.party_id
            WHERE pp.party_id = ?
            ORDER BY is_host DESC, pp.joined_at ASC
        ");
        $participantsStmt->execute([$partyId]);
        $participants = $participantsStmt->fetchAll();
        
        // Maç sonuçları ve liderlik tablosu (eğer varsa)
        $leaderboardStmt = $pdo->prepare("
            SELECT 
                u.user_id,
                u.name,
                u.gamer_tag,
                u.avatar,
                COALESCE(SUM(s.points), 0) as total_points,
                COUNT(s.match_id) as matches_played,
                SUM(CASE WHEN s.result = 'win' THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN s.result = 'draw' THEN 1 ELSE 0 END) as draws,
                SUM(CASE WHEN s.result = 'lose' THEN 1 ELSE 0 END) as losses
            FROM party_participants pp
            JOIN users u ON pp.user_id = u.user_id
            LEFT JOIN scores s ON u.user_id = s.user_id 
                AND s.match_id IN (
                    SELECT match_id FROM matches WHERE party_id = ?
                )
            WHERE pp.party_id = ?
            GROUP BY u.user_id
            ORDER BY total_points DESC, wins DESC, u.name ASC
        ");
        $leaderboardStmt->execute([$partyId, $partyId]);
        $leaderboard = $leaderboardStmt->fetchAll();
        
        // Maç geçmişi
        $matchesStmt = $pdo->prepare("
            SELECT 
                m.*,
                g.title as game_title
            FROM matches m
            JOIN games g ON m.game_id = g.game_id
            WHERE m.party_id = ?
            ORDER BY m.match_date DESC
        ");
        $matchesStmt->execute([$partyId]);
        $matches = $matchesStmt->fetchAll();
        
        sendResponse([
            'success' => true,
            'party' => $party,
            'games' => $games,
            'slots' => $slots,
            'participants' => $participants,
            'leaderboard' => $leaderboard,
            'matches' => $matches
        ]);
        
    } catch (PDOException $e) {
        sendError('Veritabanı hatası: ' . $e->getMessage());
    }
}
?>
