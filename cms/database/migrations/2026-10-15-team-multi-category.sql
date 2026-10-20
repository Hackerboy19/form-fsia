-- A team member can sit in several categories ("Leadership, Jury") or in one
-- that is not a page section ("Special Guests"). Safe to run more than once.
ALTER TABLE team_members MODIFY category VARCHAR(255) NOT NULL DEFAULT 'Mentors';
