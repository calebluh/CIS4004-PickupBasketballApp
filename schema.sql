CREATE TABLE IF NOT EXISTS Users ( 
    user_id INT AUTO_INCREMENT PRIMARY KEY,     
    username VARCHAR(50) NOT NULL UNIQUE, 
    password_hash VARCHAR(255) NOT NULL,     
    role ENUM('Admin', 'Standard') NOT NULL DEFAULT 'Standard' 
); 
 
CREATE TABLE IF NOT EXISTS Players ( 
    player_id INT AUTO_INCREMENT PRIMARY KEY,     
    name VARCHAR(100) NOT NULL,     
    player_number INT DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    nickname VARCHAR(50) DEFAULT NULL,
    team VARCHAR(50) DEFAULT "Free Agent",
    biometrics TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
); 
 
CREATE TABLE IF NOT EXISTS Games ( 
    game_id INT AUTO_INCREMENT PRIMARY KEY,     
    game_date DATE NOT NULL,     
    location VARCHAR(100) NOT NULL, 
    format VARCHAR(50) NOT NULL,
    team_score INT DEFAULT 0,
    opp_score INT DEFAULT 0,
    result ENUM('W', 'L') DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
); 
 
CREATE TABLE IF NOT EXISTS Game_Stats ( 
    stat_id INT AUTO_INCREMENT PRIMARY KEY,     
    game_id INT NOT NULL,     
    player_id INT NOT NULL,
    minutes INT DEFAULT 0,     
    points INT DEFAULT 0,     
    rebounds INT DEFAULT 0,     
    assists INT DEFAULT 0,     
    steals INT DEFAULT 0,     
    blocks INT DEFAULT 0,     
    turnovers INT DEFAULT 0, 
    fga INT DEFAULT 0,
    fgm INT DEFAULT 0,
    3pa INT DEFAULT 0,
    3pm INT DEFAULT 0,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE,     
    FOREIGN KEY (player_id) REFERENCES Players(player_id) ON DELETE CASCADE, 
    UNIQUE KEY unique_stat_per_game_player (game_id, player_id) 
);