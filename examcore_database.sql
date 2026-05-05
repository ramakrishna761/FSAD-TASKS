-- ============================================================
--  ExamCore v2.0 — Full MySQL Database
--  Run this file first, then start server.js
-- ============================================================

CREATE DATABASE IF NOT EXISTS examcore
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE examcore;

-- ============================================================
-- 1. USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  username      VARCHAR(60)     NOT NULL UNIQUE,
  password_hash VARCHAR(255)    NOT NULL,
  display_name  VARCHAR(100)    NOT NULL,
  email         VARCHAR(150)    UNIQUE,
  role          ENUM('student','admin') NOT NULL DEFAULT 'student',
  is_active     TINYINT(1)      NOT NULL DEFAULT 1,
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_username (username),
  INDEX idx_role     (role)
) ENGINE=InnoDB;

-- ============================================================
-- 2. QUESTIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS questions (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  question_text TEXT            NOT NULL,
  category      VARCHAR(80)     NOT NULL DEFAULT 'General',
  difficulty    ENUM('easy','medium','hard') NOT NULL DEFAULT 'easy',
  is_active     TINYINT(1)      NOT NULL DEFAULT 1,
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_category   (category),
  INDEX idx_difficulty (difficulty),
  INDEX idx_active     (is_active)
) ENGINE=InnoDB;

-- ============================================================
-- 3. OPTIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS options (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  question_id   INT UNSIGNED    NOT NULL,
  option_key    CHAR(1)         NOT NULL,
  option_text   VARCHAR(255)    NOT NULL,
  is_correct    TINYINT(1)      NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_question_key (question_id, option_key),
  INDEX idx_question (question_id),
  CONSTRAINT fk_options_question
    FOREIGN KEY (question_id) REFERENCES questions(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 4. EXAMS
-- ============================================================
CREATE TABLE IF NOT EXISTS exams (
  id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  title           VARCHAR(150)    NOT NULL DEFAULT 'ExamCore Assessment',
  total_questions INT UNSIGNED    NOT NULL DEFAULT 12,
  time_limit_sec  INT UNSIGNED    NOT NULL DEFAULT 120,
  pass_percentage DECIMAL(5,2)    NOT NULL DEFAULT 50.00,
  is_active       TINYINT(1)      NOT NULL DEFAULT 1,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ============================================================
-- 5. EXAM_ATTEMPTS
-- ============================================================
CREATE TABLE IF NOT EXISTS exam_attempts (
  id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED    NOT NULL,
  exam_id         INT UNSIGNED    NOT NULL DEFAULT 1,
  score           INT UNSIGNED    NOT NULL DEFAULT 0,
  total_questions INT UNSIGNED    NOT NULL DEFAULT 12,
  percentage      DECIMAL(6,2)    NOT NULL DEFAULT 0.00,
  status          ENUM('PASS','FAIL','INCOMPLETE') NOT NULL DEFAULT 'INCOMPLETE',
  time_taken_sec  INT UNSIGNED,
  started_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  submitted_at    DATETIME,
  PRIMARY KEY (id),
  INDEX idx_user       (user_id),
  INDEX idx_exam       (exam_id),
  INDEX idx_status     (status),
  INDEX idx_percentage (percentage),
  CONSTRAINT fk_attempt_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_attempt_exam
    FOREIGN KEY (exam_id) REFERENCES exams(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 6. ATTEMPT_ANSWERS
-- ============================================================
CREATE TABLE IF NOT EXISTS attempt_answers (
  id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  attempt_id      INT UNSIGNED    NOT NULL,
  question_id     INT UNSIGNED    NOT NULL,
  selected_option CHAR(1),
  is_correct      TINYINT(1)      NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_attempt_question (attempt_id, question_id),
  INDEX idx_attempt  (attempt_id),
  INDEX idx_question (question_id),
  CONSTRAINT fk_answer_attempt
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_answer_question
    FOREIGN KEY (question_id) REFERENCES questions(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 7. LEADERBOARD
-- ============================================================
CREATE TABLE IF NOT EXISTS leaderboard (
  id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED    NOT NULL,
  exam_id         INT UNSIGNED    NOT NULL DEFAULT 1,
  best_score      INT UNSIGNED    NOT NULL DEFAULT 0,
  best_percentage DECIMAL(6,2)    NOT NULL DEFAULT 0.00,
  total_attempts  INT UNSIGNED    NOT NULL DEFAULT 0,
  total_passed    INT UNSIGNED    NOT NULL DEFAULT 0,
  last_attempt_at DATETIME,
  updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_exam (user_id, exam_id),
  INDEX idx_best_pct (best_percentage DESC),
  CONSTRAINT fk_leader_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_leader_exam
    FOREIGN KEY (exam_id) REFERENCES exams(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TRIGGER — auto-update leaderboard after every attempt
-- ============================================================
DELIMITER $$

CREATE TRIGGER trg_after_attempt_insert
AFTER INSERT ON exam_attempts
FOR EACH ROW
BEGIN
  INSERT INTO leaderboard (user_id, exam_id, best_score, best_percentage,
                           total_attempts, total_passed, last_attempt_at)
  VALUES (NEW.user_id, NEW.exam_id, NEW.score, NEW.percentage,
          1, IF(NEW.status='PASS',1,0), NEW.submitted_at)
  ON DUPLICATE KEY UPDATE
    best_score      = IF(NEW.percentage > best_percentage, NEW.score,      best_score),
    best_percentage = IF(NEW.percentage > best_percentage, NEW.percentage,  best_percentage),
    total_attempts  = total_attempts + 1,
    total_passed    = total_passed   + IF(NEW.status='PASS',1,0),
    last_attempt_at = NEW.submitted_at;
END$$

DELIMITER ;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Exam config
INSERT INTO exams (id, title, total_questions, time_limit_sec, pass_percentage)
VALUES (1, 'ExamCore Web Fundamentals', 12, 120, 50.00);

-- Questions
INSERT INTO questions (id, question_text, category, difficulty) VALUES
( 1, 'JavaScript is?',                                'JavaScript', 'easy'),
( 2, 'CSS used for?',                                 'CSS',        'easy'),
( 3, 'HTML stands for?',                              'HTML',       'easy'),
( 4, 'HTML is used to?',                              'HTML',       'easy'),
( 5, 'Which attribute is used for image source?',     'HTML',       'easy'),
( 6, 'CSS stands for?',                               'CSS',        'easy'),
( 7, 'Which property is used to change text color?',  'CSS',        'easy'),
( 8, 'Which CSS property controls text size?',        'CSS',        'easy'),
( 9, "How do you select an element with id 'demo'?",  'CSS',        'easy'),
(10, "How do you select elements with class 'box'?",  'CSS',        'easy'),
(11, 'Which property is used for background color?',  'CSS',        'easy'),
(12, 'Which CSS property is used to make text bold?', 'CSS',        'easy');

-- Options
INSERT INTO options (question_id, option_key, option_text, is_correct) VALUES
(1,'A','Language',1),(1,'B','DB',0),(1,'C','OS',0),(1,'D','Tool',0),
(2,'A','Structure',0),(2,'B','Style',1),(2,'C','Logic',0),(2,'D','Data',0),
(3,'A','Hyper Trainer',0),(3,'B','Hyper Text Markup',1),(3,'C','High Text',0),(3,'D','None',0),
(4,'A','Design',0),(4,'B','Structure web pages',1),(4,'C','Database',0),(4,'D','Programming',0),
(5,'A','href',0),(5,'B','src',1),(5,'C','link',0),(5,'D','path',0),
(6,'A','Color Style Sheets',0),(6,'B','Cascading Style Sheets',1),(6,'C','Creative Style System',0),(6,'D','Computer Style Sheet',0),
(7,'A','font-color',0),(7,'B','text-color',0),(7,'C','color',1),(7,'D','background',0),
(8,'A','font-size',1),(8,'B','text-style',0),(8,'C','size',0),(8,'D','font-style',0),
(9,'A','#demo',1),(9,'B','.demo',0),(9,'C','demo',0),(9,'D','*demo',0),
(10,'A','#box',0),(10,'B','.box',1),(10,'C','box',0),(10,'D','*box',0),
(11,'A','bgcolor',0),(11,'B','background-color',1),(11,'C','color',0),(11,'D','background-style',0),
(12,'A','font-weight',1),(12,'B','text-bold',0),(12,'C','bold',0),(12,'D','font-style',0);

-- ============================================================
-- USERS — run this Node.js snippet ONCE to generate real hashes
-- then paste the output here instead of the placeholders.
--
--   const bcrypt = require('bcrypt');
--   const users = ['admin123','alice123','bob123','charlie123'];
--   users.forEach(async p => console.log(await bcrypt.hash(p, 12)));
--
-- Placeholder hashes below are NOT valid — replace before going live!
-- ============================================================
INSERT INTO users (id, username, password_hash, display_name, email, role) VALUES
(1,'admin',  '$2b$12$REPLACE_WITH_REAL_BCRYPT_HASH_FOR_admin123',   'Administrator','admin@examcore.io',   'admin'),
(2,'alice',  '$2b$12$REPLACE_WITH_REAL_BCRYPT_HASH_FOR_alice123',   'Alice',        'alice@example.com',  'student'),
(3,'bob',    '$2b$12$REPLACE_WITH_REAL_BCRYPT_HASH_FOR_bob123',     'Bob',          'bob@example.com',    'student'),
(4,'charlie','$2b$12$REPLACE_WITH_REAL_BCRYPT_HASH_FOR_charlie123', 'Charlie',      'charlie@example.com','student');

-- ============================================================
-- VIEWS
-- ============================================================

CREATE OR REPLACE VIEW vw_leaderboard AS
SELECT
  ROW_NUMBER() OVER (ORDER BY l.best_percentage DESC, l.last_attempt_at ASC) AS `rank`,
  u.username, u.display_name,
  l.best_score, l.best_percentage,
  l.total_attempts, l.total_passed, l.last_attempt_at
FROM leaderboard l
JOIN users u ON u.id = l.user_id
WHERE l.exam_id = 1
ORDER BY l.best_percentage DESC;

CREATE OR REPLACE VIEW vw_attempt_history AS
SELECT
  ea.id AS attempt_id, u.username, u.display_name, e.title AS exam_title,
  ea.score, ea.total_questions, ea.percentage, ea.status,
  ea.time_taken_sec, ea.started_at, ea.submitted_at
FROM exam_attempts ea
JOIN users u ON u.id = ea.user_id
JOIN exams e  ON e.id = ea.exam_id
ORDER BY ea.submitted_at DESC;

CREATE OR REPLACE VIEW vw_question_stats AS
SELECT
  q.id AS question_id, q.question_text, q.category, q.difficulty,
  COUNT(aa.id) AS total_answers,
  SUM(aa.is_correct) AS correct_count,
  ROUND(SUM(aa.is_correct)/COUNT(aa.id)*100, 1) AS accuracy_pct
FROM questions q
LEFT JOIN attempt_answers aa ON aa.question_id = q.id
GROUP BY q.id, q.question_text, q.category, q.difficulty
ORDER BY accuracy_pct ASC;
