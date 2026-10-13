-- Team profile text editable in the admin (Team Directory -> Edit member):
--   bio   = the paragraph in the profile pop-up (and under each leader)
--   quote = the italic line under a leader's name
--   phone = shown as a "Call" button on the profile
-- Safe to run more than once.
ALTER TABLE team_members
  ADD COLUMN IF NOT EXISTS bio   TEXT         NULL AFTER image_url,
  ADD COLUMN IF NOT EXISTS quote VARCHAR(255) NOT NULL DEFAULT '' AFTER bio,
  ADD COLUMN IF NOT EXISTS phone VARCHAR(20)  NOT NULL DEFAULT '' AFTER quote;

-- Copy today's built-in text for the two leaders, only where still empty.
UPDATE team_members SET
  bio   = COALESCE(NULLIF(bio, ''), 'The visionary architect behind Forever Star India (FSIA). Committed to building a non-discriminatory national and international platform where talent receives opportunity before it is judged.'),
  quote = IF(quote = '', 'Talent should receive opportunity before it is judged.', quote)
WHERE category = 'Leadership' AND name LIKE 'Rajesh Agarwal%';

UPDATE team_members SET
  bio   = COALESCE(NULLIF(bio, ''), 'Strategic director overseeing operations and growth. Brings expertise in pageant management, contestant grooming, and community building. Passionate about creating opportunities for women.'),
  quote = IF(quote = '', 'Creating real opportunities for women in every corner of India.', quote),
  phone = IF(phone = '', '9983599666', phone)
WHERE category = 'Leadership' AND name LIKE 'Jaya Chauhan%';
