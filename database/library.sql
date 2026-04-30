-- ============================================================
--  Libraread — Library Management System
--  Database Schema  v2.0
--  Engine : InnoDB  (ACID-compliant, FK support, row-level locks)
--  Charset: utf8mb4 / utf8mb4_unicode_ci  (full Unicode + emoji)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ============================================================
--  Drop in dependency order (children before parents)
-- ============================================================
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `borrow_records`;
DROP TABLE IF EXISTS `books`;
DROP TABLE IF EXISTS `book_categories`;
DROP TABLE IF EXISTS `auth_sessions`;
DROP TABLE IF EXISTS `users`;

-- ============================================================
--  1.  users
--      Single unified auth table for admins, librarians,
--      and students.  Passwords are always bcrypt hashed.
-- ============================================================
CREATE TABLE `users` (
    `user_id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `full_name`        VARCHAR(120)     NOT NULL,
    `email`            VARCHAR(180)     NOT NULL,
    `password_hash`    VARCHAR(255)     NOT NULL          COMMENT 'bcrypt via password_hash()',
    `role`             ENUM('admin','librarian','student') NOT NULL DEFAULT 'student',
    `is_active`        TINYINT(1)       NOT NULL DEFAULT 1 COMMENT '0=deactivated',
    `reset_token`      VARCHAR(64)      DEFAULT NULL,
    `reset_expires_at` DATETIME         DEFAULT NULL,
    `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`user_id`),
    UNIQUE  KEY `uq_users_email`        (`email`),
    KEY         `idx_users_role`        (`role`),
    KEY         `idx_users_is_active`   (`is_active`),
    KEY         `idx_users_reset_token` (`reset_token`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Unified auth table — admins, librarians, students';

-- ============================================================
--  2.  auth_sessions
--      Persistent "Remember Me" sessions.
--      One row per device/token; cleaned by cron or on login.
-- ============================================================
CREATE TABLE `auth_sessions` (
    `session_id`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NOT NULL,
    `token_hash`  VARCHAR(64)  NOT NULL          COMMENT 'SHA-256 of the cookie token',
    `ip_address`  VARCHAR(45)  DEFAULT NULL      COMMENT 'IPv4 or IPv6',
    `user_agent`  VARCHAR(255) DEFAULT NULL,
    `expires_at`  DATETIME     NOT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`session_id`),
    UNIQUE  KEY `uq_sessions_token`   (`token_hash`),
    KEY         `idx_sessions_user`   (`user_id`),
    KEY         `idx_sessions_expiry` (`expires_at`),

    CONSTRAINT `fk_sessions_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Persistent remember-me tokens';

-- ============================================================
--  3.  book_categories
--      Lookup table — avoids free-text category mismatches.
-- ============================================================
CREATE TABLE `book_categories` (
    `category_id`   SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(80)       NOT NULL,
    `description`   VARCHAR(255)      DEFAULT NULL,
    `created_at`    DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`category_id`),
    UNIQUE  KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Book genre / subject lookup';

-- ============================================================
--  4.  books
-- ============================================================
CREATE TABLE `books` (
    `book_id`          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `category_id`      SMALLINT UNSIGNED DEFAULT NULL,
    `title`            VARCHAR(255)      NOT NULL,
    `author`           VARCHAR(160)      NOT NULL,
    `isbn`             VARCHAR(20)       DEFAULT NULL,
    `publisher`        VARCHAR(120)      DEFAULT NULL,
    `published_year`   YEAR              DEFAULT NULL,
    `total_copies`     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `available_copies` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `description`      TEXT              DEFAULT NULL,
    `is_active`        TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at`       DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`book_id`),
    UNIQUE  KEY `uq_books_isbn`            (`isbn`),
    KEY         `idx_books_category`       (`category_id`),
    KEY         `idx_books_title`          (`title`),
    KEY         `idx_books_author`         (`author`),
    KEY         `idx_books_available`      (`available_copies`),
    KEY         `idx_books_active`         (`is_active`),
    FULLTEXT KEY `ft_books_title_author`   (`title`, `author`),

    CONSTRAINT `fk_books_category`
        FOREIGN KEY (`category_id`) REFERENCES `book_categories` (`category_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Book catalogue';

-- ============================================================
--  5.  borrow_records
--      ACID note: available_copies is decremented/incremented
--      atomically inside stored transactions in PHP (BEGIN /
--      COMMIT with SELECT … FOR UPDATE on the books row).
-- ============================================================
CREATE TABLE `borrow_records` (
    `borrow_id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `book_id`      INT UNSIGNED NOT NULL,
    `borrowed_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `due_date`     DATE         NOT NULL,
    `returned_at`  DATETIME     DEFAULT NULL  COMMENT 'NULL = not yet returned',
    `notes`        VARCHAR(255) DEFAULT NULL,
    `created_by`   INT UNSIGNED DEFAULT NULL  COMMENT 'Librarian who logged the borrow',

    PRIMARY KEY (`borrow_id`),
    KEY `idx_borrow_user`       (`user_id`),
    KEY `idx_borrow_book`       (`book_id`),
    KEY `idx_borrow_due`        (`due_date`),
    KEY `idx_borrow_returned`   (`returned_at`),
    KEY `idx_borrow_active`     (`user_id`, `returned_at`),  -- covers "active borrows per user"

    CONSTRAINT `fk_borrow_user`
        FOREIGN KEY (`user_id`)    REFERENCES `users` (`user_id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_borrow_book`
        FOREIGN KEY (`book_id`)    REFERENCES `books` (`book_id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_borrow_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Borrow / return transactions';

-- ============================================================
--  6.  audit_logs
--      Append-only trail of important mutations.
--      No FK on user_id so the log survives user deletion.
-- ============================================================
CREATE TABLE `audit_logs` (
    `log_id`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED    DEFAULT NULL  COMMENT 'Actor (NULL = system)',
    `action`      VARCHAR(60)     NOT NULL      COMMENT 'e.g. user.create, book.borrow',
    `target_type` VARCHAR(40)     DEFAULT NULL  COMMENT 'e.g. users, books',
    `target_id`   INT UNSIGNED    DEFAULT NULL,
    `detail`      JSON            DEFAULT NULL  COMMENT 'Before/after snapshot or extra context',
    `ip_address`  VARCHAR(45)     DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`log_id`),
    KEY `idx_audit_user`   (`user_id`),
    KEY `idx_audit_action` (`action`),
    KEY `idx_audit_target` (`target_type`, `target_id`),
    KEY `idx_audit_time`   (`created_at`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Immutable audit trail — never UPDATE or DELETE rows here';

-- ============================================================
--  Re-enable FK checks
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  SEED DATA
-- ============================================================

-- Book categories
INSERT INTO `book_categories` (`name`, `description`) VALUES
    ('Fiction',          'Novels and short stories'),
    ('Non-Fiction',      'Factual literature'),
    ('Science',          'Natural and applied sciences'),
    ('Technology',       'Computing, engineering and IT'),
    ('History',          'World and local history'),
    ('Philosophy',       'Ethics, logic and metaphysics'),
    ('Mathematics',      'Pure and applied mathematics'),
    ('Literature',       'Poetry, drama and literary criticism'),
    ('Reference',        'Encyclopedias, dictionaries, atlases'),
    ('Self-Help',        'Personal development and wellness');

-- ============================================================
--  Seed users — passwords are REAL bcrypt hashes
--
--  admin@libraread.com   → password: Admin@1234
--  lib@libraread.com     → password: Libr@1234
--  student@libraread.com → password: Student@1234
--
--  Generated with: password_hash('...', PASSWORD_BCRYPT)
-- ============================================================
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `is_active`) VALUES
    ('System Administrator',
     'admin@libraread.com',
     '$2y$12$eImiTXuWVxfM37uY4JANjOe5XlHs1G6QalFJRKhqakHl6acC8Guke',
     'admin', 1),

    ('Maria Santos',
     'lib@libraread.com',
     '$2y$12$TKh8H1.PfiQ8K/IdRuhUoeW7Y1tD4TFgqN0O.LB6p4MJhiV1xSECS',
     'librarian', 1),

    ('Juan dela Cruz',
     'student@libraread.com',
     '$2y$12$WBOfoRTqHqAZT.JoXvZRp.zRDHB5q9XAEnCEqJJZOm7Lj0eeVLXeO',
     'student', 1);

-- Sample books
INSERT INTO `books` (`category_id`, `title`, `author`, `isbn`, `publisher`, `published_year`, `total_copies`, `available_copies`) VALUES
    (1, 'Noli Me Tangere',                 'José Rizal',              '978-971-11-0000-1', 'National Commission',  1887, 5, 5),
    (1, 'El Filibusterismo',               'José Rizal',              '978-971-11-0000-2', 'National Commission',  1891, 4, 4),
    (2, 'Ang Sining ng Digma',             'Sun Tzu (trans.)',         '978-971-11-0000-3', 'Adarna House',         1910, 3, 3),
    (3, 'A Brief History of Time',         'Stephen Hawking',          '978-0-553-38016-3', 'Bantam Books',         1988, 3, 3),
    (4, 'Clean Code',                      'Robert C. Martin',         '978-0-13-235088-4', 'Prentice Hall',        2008, 4, 4),
    (4, 'The Pragmatic Programmer',        'David Thomas',             '978-0-13-595705-9', 'Addison-Wesley',       2019, 3, 3),
    (5, 'Sapiens: A Brief History',        'Yuval Noah Harari',        '978-0-06-231609-7', 'Harper Perennial',     2015, 4, 4),
    (6, 'Meditations',                     'Marcus Aurelius',          '978-0-14-044140-6', 'Penguin Classics',     180,  2, 2),
    (10,'Atomic Habits',                   'James Clear',              '978-0-7352-1129-2', 'Avery',                2018, 5, 5),
    (8, 'The Great Gatsby',                'F. Scott Fitzgerald',      '978-0-7432-7356-5', 'Scribner',             1925, 3, 3);

-- Sample borrow records (student borrows two books)
INSERT INTO `borrow_records` (`user_id`, `book_id`, `borrowed_at`, `due_date`, `returned_at`) VALUES
    (3, 1, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 3  DAY), NULL),
    (3, 4, DATE_SUB(NOW(), INTERVAL 5  DAY), DATE_ADD(CURDATE(), INTERVAL 9  DAY), NULL),
    (3, 9, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 13 DAY), DATE_SUB(NOW(), INTERVAL 14 DAY));

-- Update available_copies to reflect the two active borrows above
UPDATE `books` SET `available_copies` = 4 WHERE `book_id` = 1;
UPDATE `books` SET `available_copies` = 2 WHERE `book_id` = 4;

-- ============================================================
--  Useful views (read-only helpers for PHP queries)
-- ============================================================

-- Active borrows with user + book info
CREATE OR REPLACE VIEW `v_active_borrows` AS
    SELECT
        br.borrow_id,
        br.borrowed_at,
        br.due_date,
        DATEDIFF(CURDATE(), br.due_date) AS overdue_days,
        u.user_id,
        u.full_name,
        u.email,
        b.book_id,
        b.title,
        b.author
    FROM `borrow_records` br
    JOIN `users`  u ON u.user_id = br.user_id
    JOIN `books`  b ON b.book_id = br.book_id
    WHERE br.returned_at IS NULL;

-- Overdue borrows only
CREATE OR REPLACE VIEW `v_overdue_borrows` AS
    SELECT * FROM `v_active_borrows`
    WHERE overdue_days > 0;