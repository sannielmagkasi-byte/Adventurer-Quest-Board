-- ============================================
-- Adventurer Guild Registration & Quest Board
-- Database Setup Script (setup.sql)
-- Run this script in phpMyAdmin or MySQL terminal
-- ============================================

-- 1. Create the database
CREATE DATABASE IF NOT EXISTS quest_board_db;
USE quest_board_db;

-- 2. Create guilds table (no dependency)
CREATE TABLE IF NOT EXISTS guilds (
    guild_id INT AUTO_INCREMENT PRIMARY KEY,
    guild_name VARCHAR(100) NOT NULL UNIQUE,
    guild_description TEXT NULL,
    guild_color VARCHAR(7) NULL DEFAULT '#c8b061',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create users table (references guilds)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('guild_master', 'adventurer') NOT NULL DEFAULT 'adventurer',
    bio TEXT NULL,
    class VARCHAR(50) NULL,
    level INT NOT NULL DEFAULT 1,
    guild_id INT NULL,
    avatar VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guild_id) REFERENCES guilds(guild_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Create quests table (references users)
CREATE TABLE IF NOT EXISTS quests (
    quest_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    difficulty ENUM('EASY', 'MEDIUM', 'HARD', 'LEGENDARY') NOT NULL,
    reward VARCHAR(100) NOT NULL,
    status ENUM('OPEN', 'IN_PROGRESS', 'COMPLETED') NOT NULL DEFAULT 'OPEN',
    assigned_to INT NULL,
    created_by INT NOT NULL,
    location VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Create quest_applications table (references quests and users)
CREATE TABLE IF NOT EXISTS quest_applications (
    app_id INT AUTO_INCREMENT PRIMARY KEY,
    quest_id INT NOT NULL,
    applicant_id INT NOT NULL,
    status ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (quest_id) REFERENCES quests(quest_id) ON DELETE CASCADE,
    FOREIGN KEY (applicant_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (quest_id, applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Create wallets table (references users)
CREATE TABLE IF NOT EXISTS wallets (
    wallet_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    balance DECIMAL(12, 2) NOT NULL DEFAULT 100.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Create transactions table (references users and quests)
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    type ENUM('REWARD', 'SPEND', 'TRANSFER', 'ADJUSTMENT') NOT NULL,
    description VARCHAR(255) NOT NULL,
    quest_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (quest_id) REFERENCES quests(quest_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- 8. Insert Default Guilds
INSERT INTO guilds (guild_name, guild_description, guild_color) VALUES 
('Hunters Guild', 'The Hunters Guild is a prestigious order of skilled trackers, beast slayers, and archers. Members specialize in tracking down dangerous creatures and protecting settlements from monster threats.', '#2e7d32'),
('Mages Guild', 'The Mages Guild is an ancient academy of arcane knowledge. Members study spellcraft, potion brewing, and the mysteries of the universe. They are the realm\'s foremost experts in magical theory.', '#1565c0'),
('Iron Vanguard', 'The Iron Vanguard is a military order of heavily armored warriors and paladins. Members serve as the first line of defense against invading armies and massive beasts.', '#c62828'),
('Shadow Syndicate', 'The Shadow Syndicate is a covert organization of rogues, assassins, and infiltrators. Members operate in the shadows, handling delicate missions that require stealth and precision.', '#4a148c'),
('Verdant Circle', 'The Verdant Circle is a guild of druids, rangers, and nature guardians. Members protect the wildlands and maintain the balance between civilization and nature.', '#558b2f');

-- 9. Insert Default Admin (Guild Master) User
-- Password for default admin is: password
INSERT INTO users (username, email, password, full_name, role, class, level, bio, guild_id) VALUES 
('guild_master', 'admin@adventurers-guild.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'High Commander Aldric', 'guild_master', 'Paladin', 50, 'The founder and leader of the Adventurer\'s Guild. Protects the realm from great threats.', 3);

-- 10. Insert Sample Adventurers
-- Password for all sample users is: password
INSERT INTO users (username, email, password, full_name, role, class, level, bio, guild_id) VALUES 
('shadow_strike', 'shadow@quest.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kaelen Nightblade', 'adventurer', 'Rogue', 25, 'A stealthy rogue who strikes from the shadows. Expert in lockpicking and stealth.', 4),
('arcane_wisdom', 'arcane@quest.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Elara Moonwhisper', 'adventurer', 'Wizard', 30, 'A powerful sorceress specializing in arcane magic and ancient lore.', 2),
('iron_heart', 'iron@quest.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Thorgar Ironheart', 'adventurer', 'Warrior', 35, 'A mighty warrior with unbreakable courage and immense strength.', 3),
('green_arrow', 'green@quest.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lyanna Swiftwind', 'adventurer', 'Ranger', 22, 'A skilled ranger who navigates the wildlands with ease. Her arrows never miss.', 1),
('druid_keeper', 'druid@quest.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Orion Mosswood', 'adventurer', 'Druid', 18, 'A young druid who communicates with the spirits of the forest.', 5);

-- 11. Insert Sample Quests
INSERT INTO quests (title, description, difficulty, reward, status, created_by, location) VALUES 
('Slay the Fire Drake', 'A fire-breathing drake has been terrorizing the northern village of Emberwood. The village elder seeks a brave adventurer to slay the beast and restore peace to the region.', 'HARD', '1500 Gold & Rare Dragon Scale', 'OPEN', 1, 'Emberwood Village'),
('Retrieve the Lost Artifact', 'An ancient magical artifact has been stolen by bandits from the Royal Museum. Recover the artifact and return it to the museum curator.', 'MEDIUM', '500 Gold & Museum Membership', 'OPEN', 1, 'Bandit Hideout, Darkwood'),
('Escort the Merchant Caravan', 'A wealthy merchant needs protection for his caravan traveling through the dangerous Whispering Pass. Escort the caravan safely to the destination.', 'EASY', '200 Gold & Merchant Discount', 'IN_PROGRESS', 1, 'Whispering Pass'),
('Clear the Haunted Crypt', 'The crypt beneath the old church has been overrun by undead creatures. Clear the crypt and ensure the spirits can rest in peace.', 'MEDIUM', '400 Gold & Holy Relic', 'OPEN', 1, 'Old Town Church'),
('Find the Legendary Sword', 'Legends speak of a sword of immense power hidden deep within the Caverns of Doom. Only the bravest adventurer can retrieve it.', 'LEGENDARY', '3000 Gold & Sword of Legends', 'COMPLETED', 1, 'Caverns of Doom'),
('Protect the Harvest Festival', 'The annual Harvest Festival is approaching, but rumors of goblin raids threaten the celebration. Guard the festival grounds and ensure the villagers can celebrate in peace.', 'MEDIUM', '350 Gold & Festival Feast', 'OPEN', 1, 'Greenfield Village'),
('Investigate the Dark Ritual', 'Strange lights have been seen emanating from the abandoned tower on the hill. Investigate the source and determine if a dark ritual is underway.', 'HARD', '800 Gold & Arcane Knowledge', 'OPEN', 1, 'Abandoned Tower');

-- 12. Assign a quest to an adventurer (sample data)
UPDATE quests SET assigned_to = 3, status = 'COMPLETED' WHERE quest_id = 5;

-- 13. Create wallets for all users
INSERT INTO wallets (user_id, balance) VALUES 
(1, 5000.00),
(2, 850.00),
(3, 1200.00),
(4, 450.00),
(5, 300.00),
(6, 100.00);

-- 14. Insert sample transactions
INSERT INTO transactions (user_id, amount, type, description, quest_id) VALUES 
(1, 5000.00, 'ADJUSTMENT', 'Initial Guild Master endowment', NULL),
(2, 850.00, 'REWARD', 'Completed: Retrieve the Lost Artifact', 2),
(3, 1200.00, 'REWARD', 'Completed: Find the Legendary Sword', 5),
(4, 450.00, 'REWARD', 'Completed: Escort the Merchant Caravan', 3),
(5, 300.00, 'REWARD', 'Completed: Clear the Haunted Crypt', 4),
(2, -100.00, 'SPEND', 'Purchased healing potions at the apothecary', NULL),
(3, -200.00, 'SPEND', 'Bought new armor at the blacksmith', NULL),
(4, -50.00, 'SPEND', 'Tavern lodging and meals', NULL);

-- 15. Insert sample quest applications
INSERT INTO quest_applications (quest_id, applicant_id, status, applied_at) VALUES 
(1, 2, 'PENDING', NOW()),
(1, 5, 'PENDING', NOW()),
(6, 4, 'PENDING', NOW()),
(7, 3, 'APPROVED', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(7, 2, 'REJECTED', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- For approved applications, also mark quest as in progress
UPDATE quests SET status = 'IN_PROGRESS' WHERE quest_id = 7;

-- ============================================
-- Login credentials:
-- Username: guild_master
-- Password: password
--
-- Sample adventurer accounts (password: password):
-- shadow_strike, arcane_wisdom, iron_heart, green_arrow, druid_keeper
-- ============================================
