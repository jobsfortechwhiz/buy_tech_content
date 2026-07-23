-- ─────────────────────────────────────────────────────────────────────────────
--  TechSpace – Full Database Setup
--  Run this ONCE in phpMyAdmin → SQL tab.
--  Creates ALL 3 tables: admin_users, documents, entries.
-- ─────────────────────────────────────────────────────────────────────────────

-- 1. Admin users
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(100) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin → username: admin | password: Admin@1234
-- (Change via /db/create-admin.php then delete that file)
INSERT IGNORE INTO `admin_users` (`username`, `password`)
VALUES ('admin', '$2y$10$NGBMves0l3bgiXGC0EheD.eggaFmWGFTjUYxUFm4CQXdqGVJtKKBu');

-- 2. Documents (stores the full raw text of every uploaded file)
CREATE TABLE IF NOT EXISTS `documents` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`             VARCHAR(255) NOT NULL,
  `description`       TEXT,
  `category`          VARCHAR(100),
  `file_type`         VARCHAR(10)  NOT NULL DEFAULT 'txt',
  `original_filename` VARCHAR(255),
  `content`           LONGTEXT     NOT NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Entries (one row per numbered item parsed from the uploaded file)
CREATE TABLE IF NOT EXISTS `entries` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `document_id`  INT UNSIGNED NOT NULL,
  `entry_number` INT UNSIGNED NOT NULL,
  `content`      TEXT         NOT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_doc_num` (`document_id`, `entry_number`),
  CONSTRAINT `fk_entries_doc` FOREIGN KEY (`document_id`)
    REFERENCES `documents`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
