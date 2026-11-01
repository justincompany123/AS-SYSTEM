
CREATE DATABASE IF NOT EXISTS altar_servers_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE altar_servers_db;

-- New Mass-time-based attendance table.
-- Existing attendance and assignment records are preserved.

CREATE TABLE IF NOT EXISTS mass_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,

    member_id INT NOT NULL,

    mass_date DATE NOT NULL,

    mass_time ENUM(
        '6:00 AM',
        '8:00 AM',
        '10:00 AM',
        '5:00 PM'
    ) NOT NULL,

    attendance_status ENUM(
        'Present',
        'Absent',
        'Late',
        'Excused'
    ) NOT NULL DEFAULT 'Present',

    check_in_time DATETIME DEFAULT NULL,

    attendance_method ENUM(
        'QR Code',
        'Manual'
    ) NOT NULL DEFAULT 'QR Code',

    recorded_by INT DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_member_mass (
        member_id,
        mass_date,
        mass_time
    ),

    INDEX idx_mass_date_time (
        mass_date,
        mass_time
    ),

    INDEX idx_member_id (member_id),

    CONSTRAINT fk_mass_attendance_member
        FOREIGN KEY (member_id)
        REFERENCES members(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
