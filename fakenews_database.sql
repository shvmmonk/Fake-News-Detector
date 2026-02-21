-- ============================================================
--   FAKE NEWS DETECTION DATABASE SYSTEM
--   For DBMS Class Project | MySQL (XAMPP)
--   How to use: Open phpMyAdmin → Import this file
-- ============================================================

-- Step 1: Create and select the database
CREATE DATABASE IF NOT EXISTS fakenews_db;
USE fakenews_db;

-- ============================================================
--  TABLE 1: users
--  Stores all users (admins, fact-checkers, regular users)
-- ============================================================
CREATE TABLE users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50) NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,          -- store hashed passwords in real apps
    role        ENUM('admin','fact_checker','user') DEFAULT 'user',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
--  TABLE 2: categories
--  News categories like Politics, Health, Science, etc.
-- ============================================================
CREATE TABLE categories (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(50) NOT NULL UNIQUE,
    description   TEXT
);

-- ============================================================
--  TABLE 3: sources
--  News websites / publishers
-- ============================================================
CREATE TABLE sources (
    source_id        INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(100) NOT NULL,
    website_url      VARCHAR(255),
    credibility_score TINYINT DEFAULT 50,   -- 0 (very bad) to 100 (very trusted)
    country          VARCHAR(50),
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
--  TABLE 4: articles
--  The main news articles being analyzed
-- ============================================================
CREATE TABLE articles (
    article_id    INT AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(500) NOT NULL,
    content       TEXT NOT NULL,
    author        VARCHAR(100),
    published_at  DATETIME,
    url           VARCHAR(500),
    source_id     INT,
    category_id   INT,
    submitted_by  INT,                        -- user who submitted this article
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (source_id)    REFERENCES sources(source_id)    ON DELETE SET NULL,
    FOREIGN KEY (category_id)  REFERENCES categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (submitted_by) REFERENCES users(user_id)        ON DELETE SET NULL
);

-- ============================================================
--  TABLE 5: verifications
--  Fact-checkers verify articles and give a verdict
-- ============================================================
CREATE TABLE verifications (
    verification_id  INT AUTO_INCREMENT PRIMARY KEY,
    article_id       INT NOT NULL,
    checked_by       INT NOT NULL,             -- fact_checker user_id
    verdict          ENUM('real','fake','misleading','unverified') NOT NULL,
    confidence_score TINYINT,                  -- 0-100 how confident the checker is
    explanation      TEXT,
    verified_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(article_id) ON DELETE CASCADE,
    FOREIGN KEY (checked_by) REFERENCES users(user_id)       ON DELETE CASCADE
);

-- ============================================================
--  TABLE 6: evidence
--  Supporting evidence or counter-evidence for a verification
-- ============================================================
CREATE TABLE evidence (
    evidence_id      INT AUTO_INCREMENT PRIMARY KEY,
    verification_id  INT NOT NULL,
    evidence_type    ENUM('supporting','contradicting') NOT NULL,
    description      TEXT NOT NULL,
    source_url       VARCHAR(500),
    FOREIGN KEY (verification_id) REFERENCES verifications(verification_id) ON DELETE CASCADE
);

-- ============================================================
--  TABLE 7: reports
--  Regular users can report suspicious articles
-- ============================================================
CREATE TABLE reports (
    report_id    INT AUTO_INCREMENT PRIMARY KEY,
    article_id   INT NOT NULL,
    reported_by  INT NOT NULL,
    reason       VARCHAR(255) NOT NULL,
    status       ENUM('pending','reviewed','dismissed') DEFAULT 'pending',
    reported_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id)   REFERENCES articles(article_id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by)  REFERENCES users(user_id)       ON DELETE CASCADE
);

-- ============================================================
--  TABLE 8: tags
--  Keywords/tags for articles (many-to-many)
-- ============================================================
CREATE TABLE tags (
    tag_id  INT AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE article_tags (
    article_id  INT NOT NULL,
    tag_id      INT NOT NULL,
    PRIMARY KEY (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES articles(article_id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id)     REFERENCES tags(tag_id)         ON DELETE CASCADE
);

-- ============================================================
--  SAMPLE DATA
-- ============================================================

-- Users
INSERT INTO users (username, email, password, role) VALUES
('admin_raj',      'raj@fakenews.com',    'hashed_pass_1', 'admin'),
('checker_priya',  'priya@fakenews.com',  'hashed_pass_2', 'fact_checker'),
('checker_amit',   'amit@fakenews.com',   'hashed_pass_3', 'fact_checker'),
('user_aryan',     'aryan@gmail.com',     'hashed_pass_4', 'user'),
('user_sneha',     'sneha@gmail.com',     'hashed_pass_5', 'user'),
('user_vikram',    'vikram@gmail.com',    'hashed_pass_6', 'user');

-- Categories
INSERT INTO categories (name, description) VALUES
('Politics',    'News related to government and political events'),
('Health',      'Medical and health-related news'),
('Science',     'Scientific discoveries and research'),
('Technology',  'Tech industry news and innovations'),
('Sports',      'Sports events and athlete news'),
('Finance',     'Economy, markets, and financial news');

-- Sources
INSERT INTO sources (name, website_url, credibility_score, country) VALUES
('Times of India',    'https://timesofindia.com',   85, 'India'),
('The Hindu',         'https://thehindu.com',        90, 'India'),
('WhatsApp Forward',  NULL,                           10, 'Unknown'),
('NewsXFake',         'https://newsxfake.net',        15, 'Unknown'),
('NDTV',              'https://ndtv.com',             80, 'India'),
('BBC News',          'https://bbc.com/news',         92, 'UK');

-- Articles
INSERT INTO articles (title, content, author, published_at, url, source_id, category_id, submitted_by) VALUES
('COVID Vaccine Causes Magnetism in Humans',
 'A viral claim states that COVID-19 vaccines make people magnetic. Videos show spoons sticking to injection sites.',
 'Unknown', '2024-01-15 10:00:00', NULL, 3, 2, 4),

('India GDP Grows at 7.2% in Q3 2024',
 'India recorded a GDP growth rate of 7.2% in the third quarter of 2024, driven by manufacturing and services.',
 'Ramesh Gupta', '2024-02-01 09:30:00', 'https://timesofindia.com/gdp-q3', 1, 6, 5),

('5G Towers Spread Coronavirus',
 'Conspiracy theory claims 5G towers are responsible for spreading the coronavirus disease globally.',
 'Anonymous', '2024-01-20 14:00:00', NULL, 4, 4, 4),

('Scientists Discover Water on Mars',
 'NASA researchers have confirmed evidence of liquid water beneath the south polar ice cap of Mars.',
 'Dr. Sarah Johnson', '2024-03-10 11:00:00', 'https://bbc.com/news/mars-water', 6, 3, 6),

('Eating Garlic Cures Cancer, Study Claims',
 'A WhatsApp message claims a new study proves eating raw garlic every day can completely cure cancer.',
 'Unknown', '2024-02-25 08:00:00', NULL, 3, 2, 4),

('Election Results Rigged in Uttar Pradesh',
 'Unverified social media posts claim electronic voting machines were tampered with in UP elections.',
 'Anonymous', '2024-04-05 16:00:00', NULL, 4, 1, 6);

-- Verifications
INSERT INTO verifications (article_id, checked_by, verdict, confidence_score, explanation, verified_at) VALUES
(1, 2, 'fake', 98,
 'Multiple scientific bodies including WHO and CDC have debunked this claim. Magnetism requires iron content far beyond what vaccines contain.',
 '2024-01-16 12:00:00'),

(2, 3, 'real', 95,
 'GDP figures confirmed by Ministry of Finance official press release and Reserve Bank of India data.',
 '2024-02-02 10:00:00'),

(3, 2, 'fake', 99,
 '5G uses radio waves which cannot carry or transmit biological viruses. This is physically impossible.',
 '2024-01-21 09:00:00'),

(4, 3, 'real', 90,
 'Verified through NASA official website and multiple peer-reviewed journal publications.',
 '2024-03-11 13:00:00'),

(5, 2, 'fake', 97,
 'No credible study supports this claim. Garlic has some antioxidant properties but cannot cure cancer.',
 '2024-02-26 11:00:00'),

(6, 3, 'misleading', 75,
 'Some technical issues with EVMs were reported but no conclusive evidence of widespread rigging found.',
 '2024-04-06 14:00:00');

-- Evidence
INSERT INTO evidence (verification_id, evidence_type, description, source_url) VALUES
(1, 'contradicting', 'WHO official statement debunking vaccine magnetism myth', 'https://who.int/vaccine-myths'),
(1, 'contradicting', 'Physics analysis showing vaccines lack magnetic material',  'https://sciencedirect.com/magnetism-vaccines'),
(2, 'supporting',    'RBI Monetary Policy Report Q3 2024',                        'https://rbi.org.in/q3-report'),
(3, 'contradicting', 'IEEE paper on radio wave biology interaction',               'https://ieee.org/5g-biology'),
(4, 'supporting',    'NASA Press Release on Mars water discovery',                 'https://nasa.gov/mars-water'),
(5, 'contradicting', 'National Cancer Institute position on garlic and cancer',    'https://cancer.gov/garlic-myth'),
(6, 'supporting',    'Election Commission report on EVM issues',                   'https://eci.gov.in/report-2024');

-- Reports
INSERT INTO reports (article_id, reported_by, reason, status) VALUES
(1, 5, 'This is spreading dangerous health misinformation', 'reviewed'),
(3, 6, 'Complete lie about 5G and virus connection',        'reviewed'),
(5, 4, 'False medical claim could harm people',            'reviewed'),
(6, 5, 'Unverified political claim',                       'pending');

-- Tags
INSERT INTO tags (name) VALUES
('vaccine'), ('5G'), ('cancer'), ('NASA'), ('election'), ('GDP'), ('coronavirus'), ('Mars'), ('health-myth'), ('conspiracy');

-- Article Tags
INSERT INTO article_tags (article_id, tag_id) VALUES
(1, 1), (1, 7), (1, 9),
(2, 6),
(3, 2), (3, 7), (3, 10),
(4, 4), (4, 8),
(5, 3), (5, 9),
(6, 5), (6, 10);


-- ============================================================
--  USEFUL QUERIES FOR YOUR PROJECT / VIVA
-- ============================================================

-- Q1: Show all articles with their verdict
SELECT 
    a.article_id,
    a.title,
    s.name AS source,
    c.name AS category,
    v.verdict,
    v.confidence_score,
    v.verified_at
FROM articles a
LEFT JOIN sources      s ON a.source_id    = s.source_id
LEFT JOIN categories   c ON a.category_id  = c.category_id
LEFT JOIN verifications v ON a.article_id  = v.article_id
ORDER BY v.verified_at DESC;

-- Q2: Count of fake vs real vs misleading articles
SELECT verdict, COUNT(*) AS total
FROM verifications
GROUP BY verdict;

-- Q3: Most reported articles
SELECT a.title, COUNT(r.report_id) AS report_count
FROM articles a
JOIN reports r ON a.article_id = r.article_id
GROUP BY a.article_id
ORDER BY report_count DESC;

-- Q4: Fact-checkers and how many articles they verified
SELECT u.username, COUNT(v.verification_id) AS articles_checked
FROM users u
JOIN verifications v ON u.user_id = v.checked_by
GROUP BY u.user_id
ORDER BY articles_checked DESC;

-- Q5: Sources with lowest credibility that had fake articles
SELECT s.name, s.credibility_score, COUNT(v.verification_id) AS fake_articles
FROM sources s
JOIN articles a ON s.source_id = a.source_id
JOIN verifications v ON a.article_id = v.article_id
WHERE v.verdict = 'fake'
GROUP BY s.source_id
ORDER BY s.credibility_score ASC;

-- Q6: Search articles by tag
SELECT a.title, t.name AS tag, v.verdict
FROM articles a
JOIN article_tags at2 ON a.article_id = at2.article_id
JOIN tags        t    ON at2.tag_id   = t.tag_id
LEFT JOIN verifications v ON a.article_id = v.article_id
WHERE t.name = 'vaccine';

-- Q7: Articles with all their supporting evidence
SELECT 
    a.title,
    v.verdict,
    e.evidence_type,
    e.description
FROM articles a
JOIN verifications v ON a.article_id    = v.article_id
JOIN evidence      e ON v.verification_id = e.verification_id
ORDER BY a.article_id;

-- ============================================================
--  VIEWS (Advanced - great for viva!)
-- ============================================================

-- View 1: Full article summary for dashboard
CREATE OR REPLACE VIEW vw_article_summary AS
SELECT 
    a.article_id,
    a.title,
    a.author,
    s.name          AS source_name,
    s.credibility_score,
    c.name          AS category,
    v.verdict,
    v.confidence_score,
    u.username      AS checked_by,
    v.verified_at
FROM articles a
LEFT JOIN sources       s  ON a.source_id     = s.source_id
LEFT JOIN categories    c  ON a.category_id   = c.category_id
LEFT JOIN verifications v  ON a.article_id    = v.article_id
LEFT JOIN users         u  ON v.checked_by    = u.user_id;

-- View 2: Pending unverified articles
CREATE OR REPLACE VIEW vw_unverified_articles AS
SELECT a.article_id, a.title, a.author, s.name AS source, a.created_at
FROM articles a
LEFT JOIN sources s ON a.source_id = s.source_id
WHERE a.article_id NOT IN (SELECT article_id FROM verifications);

-- ============================================================
--  STORED PROCEDURE (Extra credit!)
-- ============================================================

DELIMITER $$

CREATE PROCEDURE GetVerdictStats()
BEGIN
    SELECT 
        verdict,
        COUNT(*)                          AS total_articles,
        ROUND(AVG(confidence_score), 1)  AS avg_confidence
    FROM verifications
    GROUP BY verdict
    ORDER BY total_articles DESC;
END$$

DELIMITER ;

-- Run the procedure like this:
-- CALL GetVerdictStats();

-- ============================================================
--  TRIGGER: Auto-update report status when article is verified
-- ============================================================

DELIMITER $$

CREATE TRIGGER after_verification_insert
AFTER INSERT ON verifications
FOR EACH ROW
BEGIN
    UPDATE reports
    SET status = 'reviewed'
    WHERE article_id = NEW.article_id AND status = 'pending';
END$$

DELIMITER ;

-- ============================================================
--  END OF FILE
--  Import this in phpMyAdmin: Database → Import → Choose File
-- ============================================================
