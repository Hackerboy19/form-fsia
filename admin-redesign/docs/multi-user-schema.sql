-- Proposed MySQL tables for team access in the live PHP panel.
-- A proposal, not a migration: check every name against the live database before running anything.
-- The registration tables (savemissindia, savemrsindia, ...) are not changed; the new tables point at them.

CREATE TABLE admin_users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  username      VARCHAR(60)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,              -- password_hash(), never plain text
  role          VARCHAR(30)  NOT NULL,              -- admin | manager | caller | content | accounts | viewer
  active        TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Which states a member may work on. No rows = all states.
CREATE TABLE admin_user_states (
  user_id INT UNSIGNED NOT NULL,
  state   VARCHAR(60)  NOT NULL,
  PRIMARY KEY (user_id, state),
  FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Editable permission matrix. The admin role is checked in code and always has everything.
CREATE TABLE admin_role_permissions (
  role       VARCHAR(30) NOT NULL,
  permission VARCHAR(60) NOT NULL,                  -- e.g. registrations.delete, payments.confirm
  PRIMARY KEY (role, permission)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Work and payment data per registration, kept apart from the form tables.
-- source_table + source_id identify the row in the table the form saved to.
CREATE TABLE registration_work (
  source_table VARCHAR(60)  NOT NULL,
  source_id    INT UNSIGNED NOT NULL,
  status       VARCHAR(20)  NOT NULL DEFAULT 'new', -- new | contacted | shortlisted | paid | rejected
  assigned_to  INT UNSIGNED NULL,
  notes        TEXT NULL,
  offer_name   VARCHAR(80)  NULL,
  amount       INT UNSIGNED NULL,                   -- whole rupees
  payment_url  VARCHAR(500) NULL,
  payment_status VARCHAR(10) NOT NULL DEFAULT 'none', -- none | sent | paid
  payment_ref  VARCHAR(80)  NULL,
  sent_at      DATETIME NULL,
  paid_at      DATETIME NULL,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (source_table, source_id),
  FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Who did what. Written by the server, never by the browser.
CREATE TABLE admin_audit_log (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NULL,
  action     VARCHAR(20)  NOT NULL,                 -- status | assign | payment | page | export | delete | team | login
  summary    VARCHAR(500) NOT NULL,
  ip         VARCHAR(45)  NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (user_id), KEY (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
