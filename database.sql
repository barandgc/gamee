-- Game+ Party Hub Veritabanı
-- MySQL veritabanı ve tabloları

CREATE DATABASE IF NOT EXISTS game_party_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE game_party_hub;

-- Kullanıcılar tablosu
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    gamer_tag VARCHAR(50) UNIQUE NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Oyunlar tablosu
CREATE TABLE games (
    game_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    genre VARCHAR(50) NOT NULL,
    max_players INT NOT NULL,
    image VARCHAR(255) DEFAULT 'default-game.png'
);

-- Partiler tablosu
CREATE TABLE parties (
    party_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    host_id INT NOT NULL,
    party_date DATE NOT NULL,
    status ENUM('planning', 'voting', 'scheduled', 'completed') DEFAULT 'planning',
    selected_game_id INT NULL,
    selected_slot_id INT NULL,
    invite_code VARCHAR(20) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (host_id) REFERENCES users(user_id),
    FOREIGN KEY (selected_game_id) REFERENCES games(game_id)
);

-- Parti oyun önerileri
CREATE TABLE party_games (
    id INT PRIMARY KEY AUTO_INCREMENT,
    party_id INT NOT NULL,
    game_id INT NOT NULL,
    FOREIGN KEY (party_id) REFERENCES parties(party_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id),
    UNIQUE KEY unique_party_game (party_id, game_id)
);

-- Oyun oyları
CREATE TABLE game_votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    party_id INT NOT NULL,
    game_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (party_id) REFERENCES parties(party_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY unique_vote (party_id, game_id, user_id)
);

-- Zaman slotları
CREATE TABLE slots (
    slot_id INT PRIMARY KEY AUTO_INCREMENT,
    party_id INT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_date DATE NOT NULL,
    FOREIGN KEY (party_id) REFERENCES parties(party_id) ON DELETE CASCADE
);

-- Slot oyları
CREATE TABLE slot_votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slot_id INT NOT NULL,
    user_id INT NOT NULL,
    choice ENUM('yes', 'no') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (slot_id) REFERENCES slots(slot_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY unique_slot_vote (slot_id, user_id)
);

-- Maçlar
CREATE TABLE matches (
    match_id INT PRIMARY KEY AUTO_INCREMENT,
    party_id INT NOT NULL,
    game_id INT NOT NULL,
    match_date DATETIME NOT NULL,
    status ENUM('scheduled', 'in_progress', 'completed') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (party_id) REFERENCES parties(party_id),
    FOREIGN KEY (game_id) REFERENCES games(game_id)
);

-- Skorlar
CREATE TABLE scores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    match_id INT NOT NULL,
    user_id INT NOT NULL,
    score INT NOT NULL DEFAULT 0,
    result ENUM('win', 'lose', 'draw') NOT NULL,
    points INT NOT NULL DEFAULT 0, -- 3 for win, 1 for draw, 0 for lose
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(match_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Parti katılımcıları
CREATE TABLE party_participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    party_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (party_id) REFERENCES parties(party_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY unique_participant (party_id, user_id)
);

-- Örnek veriler
INSERT INTO users (name, gamer_tag, avatar) VALUES
('Ahmet Yılmaz', 'ahmet_gamer', 'avatar1.png'),
('Zehra Kaya', 'zehra_pro', 'avatar2.png'),
('Mehmet Demir', 'mehmet_legend', 'avatar3.png'),
('Ayşe Çelik', 'ayse_master', 'avatar4.png'),
('Ali Özer', 'ali_champion', 'avatar5.png');

INSERT INTO games (title, genre, max_players) VALUES
-- Spor Oyunları
('NBA 2K24', 'Sports', 4),
('PES 2024', 'Sports', 4),
('Rocket League', 'Sports', 8),

-- Battle Royale
('PUBG Mobile', 'Battle Royale', 4, 'pubg.jpg'),
('Fortnite', 'Battle Royale', 4, 'fortnite.jpg'),
('Apex Legends', 'Battle Royale', 3, 'apex.jpg'),
('Call of Duty: Warzone', 'Battle Royale', 4, 'warzone.jpg'),

-- FPS Oyunları
('Counter-Strike 2', 'FPS', 10, 'cs2.jpg'),
('Valorant', 'FPS', 10, 'valorant.jpg'),
('Overwatch 2', 'FPS', 6, 'overwatch2.jpg'),
('Rainbow Six Siege', 'FPS', 10, 'r6siege.jpg'),

-- MOBA
('League of Legends', 'MOBA', 10, 'lol.jpg'),
('Dota 2', 'MOBA', 10, 'dota2.jpg'),

-- Yarış
('Gran Turismo 7', 'Racing', 8, 'gt7.jpg'),
('F1 23', 'Racing', 22, 'f1-23.jpg'),

-- Aksiyon/Macera
('GTA V Online', 'Action', 8, 'gtav.jpg'),
('Minecraft', 'Sandbox', 10, 'minecraft.jpg'),
('Among Us', 'Social', 10, 'among-us.jpg'),

-- Mobil Oyunlar
('Mobile Legends', 'MOBA', 10, 'mobile-legends.jpg'),
('Free Fire', 'Battle Royale', 4, 'free-fire.jpg'),
('Brawl Stars', 'Action', 6, 'brawl-stars.jpg');
