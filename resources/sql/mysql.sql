-- #!mysql

-- #{ factions.table
CREATE TABLE IF NOT EXISTS factions (
    id INT PRIMARY KEY AUTO_INCREMENT,  
    name VARCHAR(255) NOT NULL UNIQUE,
    creation_date BIGINT NOT NULL,
    leader_xuid VARCHAR(255) NOT NULL,
    home TEXT DEFAULT NULL,
    power INT NOT NULL DEFAULT 0,
    money DOUBLE NOT NULL DEFAULT 0.0,
    kills INT NOT NULL DEFAULT 0,
    freeze_power_time INT NOT NULL DEFAULT 0,
    INDEX idx_factions_power (power),
    INDEX idx_factions_money (money),
    INDEX idx_factions_kills (kills)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- #}

-- #{ factions.insert
-- # :name string
-- # :creation_date int
-- # :leader_xuid string
-- # :home ?string
-- # :power int
-- # :money float
-- # :kills int
-- # :freeze_power_time int
INSERT INTO factions (name, creation_date, leader_xuid, home, power, money, kills, freeze_power_time)
VALUES (:name, :creation_date, :leader_xuid, :home, :power, :money, :kills, :freeze_power_time)
    ON DUPLICATE KEY UPDATE
                         leader_xuid = VALUES(leader_xuid),
                         home = VALUES(home),
                         power = VALUES(power),
                         money = VALUES(money),
                         kills = VALUES(kills),
                         freeze_power_time = VALUES(freeze_power_time);
-- #}

-- #{ factions.get_by_id
-- # :id int
SELECT * FROM factions WHERE id = :id;
-- #}

-- #{ factions.get_by_name
-- # :name string
SELECT * FROM factions WHERE name = :name;
-- #}

-- #{ factions.get_all
SELECT * FROM factions;
-- #}

-- #{ factions.delete
-- # :id int
DELETE FROM factions WHERE id = :id;
-- #}

-- #{ factions.update_leader
-- # :id int
-- # :leader_xuid string
UPDATE factions SET leader_xuid = :leader_xuid WHERE id = :id;
-- #}

-- #{ factions.update_home
-- # :id int
-- # :home ?string
UPDATE factions SET home = :home WHERE id = :id;
-- #}

-- #{ factions.update_power
-- # :id int
-- # :power int
UPDATE factions SET power = :power WHERE id = :id;
-- #}

-- #{ factions.update_freeze_power_time
-- # :id int
-- # :freeze_power_time int
UPDATE factions SET freeze_power_time = :freeze_power_time WHERE id = :id;
-- #}

-- #{ factions.update_money
-- # :id int
-- # :money float
UPDATE factions SET money = :money WHERE id = :id;
-- #}

-- #{ factions.update_kills
-- # :id int
-- # :kills int
UPDATE factions SET kills = :kills WHERE id = :id;
-- #}

-- #{ factions.update_all
-- # :id int
-- # :name string
-- # :creation_date int
-- # :leader_xuid string
-- # :home ?string
-- # :power int
-- # :money float
-- # :kills int
-- # :freeze_power_time int
UPDATE factions SET name = :name, creation_date = :creation_date, leader_xuid = :leader_xuid, home = :home, power = :power, money = :money, kills = :kills, freeze_power_time = :freeze_power_time WHERE id = :id;
-- #}

-- #{ factions.get_top_by_power
-- # :limit int
-- # :offset int
SELECT id, name, power, money, kills FROM factions ORDER BY power DESC LIMIT :limit OFFSET :offset;
-- #}

-- #{ factions.get_top_by_kills
-- # :limit int
-- # :offset int
SELECT id, name, power, money, kills FROM factions ORDER BY kills DESC LIMIT :limit OFFSET :offset;
-- #}

-- #{ factions.get_top_by_money
-- # :limit int
-- # :offset int
SELECT id, name, power, money, kills FROM factions ORDER BY money DESC LIMIT :limit OFFSET :offset;
-- #}

-- #{ factions.count_all
SELECT COUNT(id) AS total_factions FROM factions;
-- #}

-- #{ members.table
CREATE TABLE IF NOT EXISTS members (
                                       faction_id INT NOT NULL,
                                       player_xuid VARCHAR(255) NOT NULL,
    player_name VARCHAR(255) NOT NULL,
    role ENUM('leader', 'coleader', 'member') NOT NULL DEFAULT 'member',
    PRIMARY KEY (faction_id, player_xuid),
    FOREIGN KEY (faction_id) REFERENCES factions(id) ON DELETE CASCADE,
    INDEX idx_members_player_xuid (player_xuid)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- #}

-- #{ members.insert
-- # :faction_id int
-- # :player_xuid string
-- # :player_name string
-- # :role string
INSERT INTO members (faction_id, player_xuid, player_name, role)
VALUES (:faction_id, :player_xuid, :player_name, :role)
    ON DUPLICATE KEY UPDATE role = VALUES(role), player_name = VALUES(player_name);
-- #}

-- #{ members.get_by_faction
-- # :faction_id int
SELECT * FROM members WHERE faction_id = :faction_id;
-- #}

-- #{ members.get_by_player
-- # :player_xuid string
SELECT * FROM members WHERE player_xuid = :player_xuid;
-- #}

-- #{ members.get_faction_id_by_xuid
-- # :player_xuid string
SELECT faction_id FROM members WHERE player_xuid = :player_xuid;
-- #}

-- #{ members.update_role
-- # :faction_id int
-- # :player_xuid string
-- # :role string
UPDATE members SET role = :role WHERE faction_id = :faction_id AND player_xuid = :player_xuid;
-- #}

-- #{ members.update_name
-- # :player_xuid string
-- # :player_name string
UPDATE members SET player_name = :player_name WHERE player_xuid = :player_xuid;
-- #}

-- #{ members.delete
-- # :faction_id int
-- # :player_xuid string
DELETE FROM members WHERE faction_id = :faction_id AND player_xuid = :player_xuid;
-- #}

-- #{ members.delete_all_from_faction
-- # :faction_id int
DELETE FROM members WHERE faction_id = :faction_id;
-- #}

-- #{ alliances.table
CREATE TABLE IF NOT EXISTS alliances (
                                         faction_id INT NOT NULL,
                                         ally_id INT NOT NULL,
                                         status ENUM('pending', 'accepted', 'denied') NOT NULL DEFAULT 'pending',
    created_at BIGINT NOT NULL,
    PRIMARY KEY (faction_id, ally_id),
    FOREIGN KEY (faction_id) REFERENCES factions(id) ON DELETE CASCADE,
    FOREIGN KEY (ally_id) REFERENCES factions(id) ON DELETE CASCADE,
    INDEX idx_alliances_ally_id (ally_id)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- #}

-- #{ alliances.insert
-- # :faction_id int
-- # :ally_id int
-- # :status string
-- # :created_at int
INSERT INTO alliances (faction_id, ally_id, status, created_at)
VALUES (:faction_id, :ally_id, :status, :created_at)
    ON DUPLICATE KEY UPDATE status = VALUES(status);
-- #}

-- #{ alliances.get_by_faction
-- # :faction_id int
SELECT * FROM alliances WHERE faction_id = :faction_id;
-- #}

-- #{ alliances.get_by_ally
-- # :ally_id int
SELECT * FROM alliances WHERE ally_id = :ally_id;
-- #}

-- #{ alliances.get_status
-- # :faction_id int
-- # :ally_id int
SELECT status, created_at FROM alliances WHERE faction_id = :faction_id AND ally_id = :ally_id;
-- #}

-- #{ alliances.get_all_accepted
SELECT faction_id, ally_id FROM alliances WHERE status = 'accepted';
-- #}

-- #{ alliances.update_status
-- # :faction_id int
-- # :ally_id int
-- # :status string
UPDATE alliances SET status = :status WHERE faction_id = :faction_id AND ally_id = :ally_id;
-- #}

-- #{ alliances.delete
-- # :faction_id int
-- # :ally_id int
DELETE FROM alliances WHERE faction_id = :faction_id AND ally_id = :ally_id;
-- #}

-- #{ alliances.delete_all_for_faction
-- # :faction_id int
DELETE FROM alliances WHERE faction_id = :faction_id OR ally_id = :faction_id;
-- #}

-- #{ claims.table
CREATE TABLE IF NOT EXISTS claims (
                                      faction_id INT NOT NULL,
                                      chunk_x INT NOT NULL,
                                      chunk_z INT NOT NULL,
                                      world_name VARCHAR(255) NOT NULL,
    claimed_at BIGINT NOT NULL,
    PRIMARY KEY (chunk_x, chunk_z, world_name),
    FOREIGN KEY (faction_id) REFERENCES factions(id) ON DELETE CASCADE,
    INDEX idx_claims_faction_id (faction_id)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- #}

-- #{ claims.insert
-- # :faction_id int
-- # :chunk_x int
-- # :chunk_z int
-- # :world_name string
-- # :claimed_at int
INSERT IGNORE INTO claims (faction_id, chunk_x, chunk_z, world_name, claimed_at)
VALUES (:faction_id, :chunk_x, :chunk_z, :world_name, :claimed_at);
-- #}

-- #{ claims.get_by_faction
-- # :faction_id int
SELECT * FROM claims WHERE faction_id = :faction_id;
-- #}

-- #{ claims.get_by_location
-- # :chunk_x int
-- # :chunk_z int
-- # :world_name string
SELECT * FROM claims WHERE chunk_x = :chunk_x AND chunk_z = :chunk_z AND world_name = :world_name;
-- #}

-- #{ claims.delete
-- # :chunk_x int
-- # :chunk_z int
-- # :world_name string
DELETE FROM claims WHERE chunk_x = :chunk_x AND chunk_z = :chunk_z AND world_name = :world_name;
-- #}

-- #{ claims.delete_all_from_faction
-- # :faction_id int
DELETE FROM claims WHERE faction_id = :faction_id;
-- #}

-- #{ claims.get_all
SELECT * FROM claims;
-- #}

-- #{ indices.create_factions_power_index
-- # :dummy
SELECT 1;
-- #}

-- #{ indices.create_factions_money_index
-- # :dummy
SELECT 1;
-- #}

-- #{ indices.create_factions_kills_index
-- # :dummy
SELECT 1;
-- #}

-- #{ indices.create_player_xuid_index
-- # :dummy
SELECT 1;
-- #}

-- #{ indices.create_faction_id_index
-- # :dummy
SELECT 1;
-- #}

-- #{ indices.create_claims_faction_id_index
-- # :dummy
SELECT 1;
-- #}

-- #{ indices.create_alliances_ally_id_index
-- # :dummy
SELECT 1;
-- #}

-- #{ player_cooldowns.table
CREATE TABLE IF NOT EXISTS player_cooldowns (
    player_xuid VARCHAR(255) PRIMARY KEY,
    last_disband_time BIGINT NOT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- #}

-- #{ player_cooldowns.set
-- # :player_xuid string
-- # :last_disband_time int
INSERT INTO player_cooldowns (player_xuid, last_disband_time)
VALUES (:player_xuid, :last_disband_time)
ON DUPLICATE KEY UPDATE last_disband_time = VALUES(last_disband_time);
-- #}

-- #{ player_cooldowns.get
-- # :player_xuid string
SELECT last_disband_time FROM player_cooldowns WHERE player_xuid = :player_xuid;
-- #}

-- #{ faction_permissions.table
CREATE TABLE IF NOT EXISTS faction_permissions (
    faction_id INT NOT NULL,
    role VARCHAR(50) NOT NULL,
    permission VARCHAR(100) NOT NULL,
    granted INT NOT NULL DEFAULT 1,
    PRIMARY KEY (faction_id, role, permission)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- #}

-- #{ faction_permissions.get_by_faction
-- # :faction_id int
SELECT role, permission, granted FROM faction_permissions WHERE faction_id = :faction_id;
-- #}

-- #{ faction_permissions.set
-- # :faction_id int
-- # :role string
-- # :permission string
-- # :granted int
INSERT INTO faction_permissions (faction_id, role, permission, granted)
VALUES (:faction_id, :role, :permission, :granted)
ON DUPLICATE KEY UPDATE granted = VALUES(granted);
-- #}

-- #{ faction_permissions.delete_all_from_faction
-- # :faction_id int
DELETE FROM faction_permissions WHERE faction_id = :faction_id;
-- #}

-- #{ subclaims.table
CREATE TABLE IF NOT EXISTS subclaims (
    id INT PRIMARY KEY AUTO_INCREMENT,
    faction_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    world_name VARCHAR(100) NOT NULL,
    min_x INT NOT NULL,
    min_y INT NOT NULL,
    min_z INT NOT NULL,
    max_x INT NOT NULL,
    max_y INT NOT NULL,
    max_z INT NOT NULL,
    min_role VARCHAR(50) NOT NULL DEFAULT 'coleader'
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- #}

-- #{ subclaims.get_by_faction
-- # :faction_id int
SELECT * FROM subclaims WHERE faction_id = :faction_id;
-- #}

-- #{ subclaims.get_all
SELECT * FROM subclaims;
-- #}

-- #{ subclaims.insert
-- # :faction_id int
-- # :name string
-- # :world_name string
-- # :min_x int
-- # :min_y int
-- # :min_z int
-- # :max_x int
-- # :max_y int
-- # :max_z int
-- # :min_role string
INSERT INTO subclaims (faction_id, name, world_name, min_x, min_y, min_z, max_x, max_y, max_z, min_role)
VALUES (:faction_id, :name, :world_name, :min_x, :min_y, :min_z, :max_x, :max_y, :max_z, :min_role);
-- #}

-- #{ subclaims.delete
-- # :faction_id int
-- # :name string
DELETE FROM subclaims WHERE faction_id = :faction_id AND name = :name;
-- #}

-- #{ subclaims.update_role
-- # :faction_id int
-- # :name string
-- # :min_role string
UPDATE subclaims SET min_role = :min_role WHERE faction_id = :faction_id AND name = :name;
-- #}

-- #{ subclaims.delete_all_from_faction
-- # :faction_id int
DELETE FROM subclaims WHERE faction_id = :faction_id;
-- #}

-- #{ faction_audit_logs.table
CREATE TABLE IF NOT EXISTS faction_audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    faction_id INT NOT NULL,
    actor_xuid VARCHAR(255) NOT NULL,
    actor_name VARCHAR(255) NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NOT NULL,
    created_at BIGINT NOT NULL,
    INDEX idx_audit_faction (faction_id, created_at)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- #}

-- #{ faction_audit_logs.insert
-- # :faction_id int
-- # :actor_xuid string
-- # :actor_name string
-- # :action string
-- # :details string
-- # :created_at int
INSERT INTO faction_audit_logs (faction_id, actor_xuid, actor_name, action, details, created_at)
VALUES (:faction_id, :actor_xuid, :actor_name, :action, :details, :created_at);
-- #}

-- #{ faction_audit_logs.get_by_faction
-- # :faction_id int
-- # :limit int
-- # :offset int
SELECT * FROM faction_audit_logs WHERE faction_id = :faction_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset;
-- #}

-- #{ faction_audit_logs.count_by_faction
-- # :faction_id int
SELECT COUNT(id) AS total FROM faction_audit_logs WHERE faction_id = :faction_id;
-- #}

