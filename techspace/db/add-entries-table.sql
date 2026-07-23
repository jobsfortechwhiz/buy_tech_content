-- ─────────────────────────────────────────────────────────────────────────────
--  NOTE: This file is kept for reference only.
--
--  The entries table is now included in the main setup.sql file.
--  If you have already run setup.sql, you do NOT need to run this.
--
--  Only run the query below if you ran the OLD setup.sql (before July 2025)
--  and your database is missing the entries table.
-- ─────────────────────────────────────────────────────────────────────────────

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
