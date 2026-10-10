-- FSIA headless CMS: schema + seed data.
-- MySQL 5.7.8+ / MariaDB 10.2.7+ (JSON columns). Run once:
--   mysql -u <user> -p <database> < cms/database/schema.sql

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- SEO metadata, one row per managed page. page_slug is the page's file name
-- without ".php" (index, about, our-teams, news-coverage, special-news-coverage).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pages_seo (
  page_slug        VARCHAR(64)  NOT NULL,
  meta_title       VARCHAR(255) NOT NULL DEFAULT '',
  meta_description VARCHAR(500) NOT NULL DEFAULT '',
  meta_keywords    VARCHAR(500) NOT NULL DEFAULT '',
  og_title         VARCHAR(255) NOT NULL DEFAULT '',
  og_description   VARCHAR(500) NOT NULL DEFAULT '',
  og_image_url     VARCHAR(512) NOT NULL DEFAULT '',
  canonical_url    VARCHAR(512) NOT NULL DEFAULT '',
  updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (page_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Free-form content blocks per page (hero, intro, mission ...). `content` holds
-- the block's fields as a JSON object; the admin panel defines which fields
-- each block has.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS page_sections (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  page_slug   VARCHAR(64)  NOT NULL,
  section_key VARCHAR(64)  NOT NULL,
  content     JSON         NOT NULL,
  image_url   VARCHAR(512) NOT NULL DEFAULT '',
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_page_section (page_slug, section_key),
  CONSTRAINT fk_sections_page FOREIGN KEY (page_slug)
    REFERENCES pages_seo (page_slug) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Team directory (our-teams.php).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS team_members (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(150) NOT NULL,
  designation   VARCHAR(150) NOT NULL DEFAULT '',
  category      ENUM('Leadership','Core Team','Mentors','Anchors','Media') NOT NULL,
  image_url     VARCHAR(512) NOT NULL DEFAULT '',
  social_links  JSON         NULL,
  display_order INT          NOT NULL DEFAULT 0,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_team_listing (is_active, category, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- News articles. Standard -> news-coverage.php, Special -> special-news-coverage.php,
-- Top10 -> a "top 10" strip on either page. A future published_date schedules
-- the article: the public API hides it until that day.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS news_coverage (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title              VARCHAR(255) NOT NULL,
  slug               VARCHAR(191) NOT NULL,
  news_type          ENUM('Standard','Special','Top10') NOT NULL DEFAULT 'Standard',
  featured_image_url VARCHAR(512) NOT NULL DEFAULT '',
  content_html       MEDIUMTEXT   NOT NULL,
  published_date     DATE         NOT NULL,
  created_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_news_slug (slug),
  KEY idx_news_listing (news_type, published_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Seed: the five managed pages (sections need these rows because of the FK).
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO pages_seo (page_slug, meta_title, meta_description, canonical_url) VALUES
  ('index',                 'Forever Star India | Beauty Pageants & Award Shows', 'India''s biggest platform for beauty pageants and award shows.', 'https://www.fsia.in/'),
  ('about',                 'About Us | Forever Star India',                      'The story, mission and people behind Forever Star India.',      'https://www.fsia.in/about.php'),
  ('our-teams',             'Our Team | Forever Star India',                      'Meet the team behind Forever Star India.',                     'https://www.fsia.in/our-teams.php'),
  ('news-coverage',         'News Coverage | Forever Star India',                 'Press and media coverage of Forever Star India events.',       'https://www.fsia.in/news-coverage.php'),
  ('special-news-coverage', 'Special Coverage | Forever Star India',              'Special features and exclusive coverage from Forever Star India.', 'https://www.fsia.in/special-news-coverage.php');

-- Seed: current team roster from our-teams.php. Only inserted into an empty table.
INSERT INTO team_members (name, designation, category, image_url, display_order)
SELECT * FROM (
  SELECT 'Rajesh Agarwal (Astro Raj)' AS name, 'CEO & Founder' AS designation, 'Leadership' AS category, '/static/media/Rajesh-Agarwal-CEO-FSIA.62467c70a0bb0c9d33a7.jpg' AS image_url, 1 AS display_order
  UNION ALL SELECT 'Jaya Chauhan', 'Director FSIA', 'Leadership', '/static/media/Jaya-Chauhan-Director-FSIA.28ac489a966ef9db5df8.jpg', 2
  UNION ALL SELECT 'Vaibhav Sharma', 'Digital Marketing Manager', 'Core Team', '/static/media/Vaibhav-Sharma.jpg', 1
  UNION ALL SELECT 'Kamlesh Kumawat', 'Data Analyst', 'Core Team', '/assets_new/img/team/Kamlesh.webp', 2
  UNION ALL SELECT 'Monisha', 'Project Coordinator', 'Core Team', '/static/media/Monisha.jpg', 3
  UNION ALL SELECT 'Ayushi', 'Mentor', 'Mentors', '/static/media/Ayushi.jpg', 1
  UNION ALL SELECT 'Yasmeen', 'Mentor', 'Mentors', '/static/media/Yasmeen.jpg', 2
  UNION ALL SELECT 'Shie Lobo', 'Pageant Director and Choreographer', 'Mentors', '/assets_new/img/team/Shie%20Lobo.webp', 3
  UNION ALL SELECT 'Sakshi Shekhar', 'Nutritionist & Life Coach', 'Mentors', '/static/media/Sakshi-Shekhar-Nutritionist-&-Life-Coach.743ddb067c91475fcc7d.jpg', 4
  UNION ALL SELECT 'Saloni Gusain', 'Rampwalk Expert', 'Mentors', '/static/media/Saloni-Gusain-Rampwalk-Expert.155f2b2ce2616cf6f96d.jpg', 5
  UNION ALL SELECT 'Sunidhi Khare', 'Trainer and Mentor', 'Mentors', '/static/media/Sunidhi-Khare-Trainer-and-Mentor.f7b3a5601a10286d5dd6.jpg', 6
  UNION ALL SELECT 'Rajlaxmi Chavan', 'Trainer and Mentor', 'Mentors', '/static/media/Rajlaxmi-Chavan-Trainer-&-Mentor.868df85dc6ccb17534e7.jpg', 7
  UNION ALL SELECT 'Garima Saxena', 'Trainer and Mentor', 'Mentors', '/static/media/Garima-Saxena-Trainer-and-Mentor.4bc07128ac56849d822b.jpg', 8
  UNION ALL SELECT 'Shilpi Benarjee', 'Trainer and Mentor', 'Mentors', '/static/media/Shilpi-Benarjee-Trainer-and-Mentor.775e8467ae9d167e2429.jpg', 9
  UNION ALL SELECT 'Anshu Khanna', 'Trainer and Mentor', 'Mentors', '/static/media/Anshu-Khanna-Trainer-and-Mentor.57e3bb31faf87b66094d.jpg', 10
  UNION ALL SELECT 'Manushi Parmar', 'Trainer and Mentor', 'Mentors', '/static/media/Manushi-Parmar-Trainer-&-Mentor.1c965377bd264f6a7b22.jpg', 11
  UNION ALL SELECT 'Ujjwal Pareek', 'Anchor', 'Anchors', '/static/media/Ujjwal%20Pareek.e1a51c8824e7e7794af0.jpeg', 1
  UNION ALL SELECT 'Ruchita Sharma', 'Anchor', 'Anchors', '/static/media/Ruchita%20Sharma.cfc9054460f620215939.jpg', 2
  UNION ALL SELECT 'Jainika', 'Anchor', 'Anchors', '/static/media/Jainika-Anchor.b9512d00c1edbefc7c09.jpg', 3
  UNION ALL SELECT 'Ajit Singh', 'Anchor', 'Anchors', '/static/media/Ajit-Singh-Anchor.54cc2a07e71b974d9a14.jpg', 4
  UNION ALL SELECT 'Aryan', 'Photographer and Videographer', 'Media', '/static/media/Aryan.7557ad9c4f74422a72f7.jpg', 1
  UNION ALL SELECT 'Deval', 'Photographer and Videographer', 'Media', '/static/media/Deval.ac93b5aa19d442358d9c.jfif', 2
  UNION ALL SELECT 'Himanshu', 'Photographer and Videographer', 'Media', '/static/media/Himanshu.b5ae62adfed79caa1615.jpg', 3
  UNION ALL SELECT 'RV', 'Photographer and Videographer', 'Media', '/static/media/RV.a87bb957d5618c37a048.jpg', 4
  UNION ALL SELECT 'Sachin', 'Photographer and Videographer', 'Media', '/static/media/Sachin.6b457d8b0c30c142d233.jpg', 5
  UNION ALL SELECT 'Saurav', 'Photographer and Videographer', 'Media', '/static/media/Saurav.2a22aef898bec5202f9a.jpg', 6
  UNION ALL SELECT 'Sunil', 'Photographer and Videographer', 'Media', '/static/media/Sunil.dbcf2638e8bed855f997.jpg', 7
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM team_members);
