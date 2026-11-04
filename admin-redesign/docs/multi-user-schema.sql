-- Tables the admin API needs. api/install.php creates exactly these (from schema_sql() in api/lib.php).
-- The existing form tables are not changed.

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL, role VARCHAR(30) NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1,
  failed_logins INT UNSIGNED NOT NULL DEFAULT 0, locked_until DATETIME NULL, last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_user_states (
  user_id INT UNSIGNED NOT NULL, state VARCHAR(60) NOT NULL, PRIMARY KEY (user_id, state),
  FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_role_permissions (
  role VARCHAR(30) NOT NULL, permission VARCHAR(60) NOT NULL, PRIMARY KEY (role, permission)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS registration_work (
  source_table VARCHAR(64) NOT NULL, source_id INT UNSIGNED NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'new', assigned_to INT UNSIGNED NULL, notes TEXT NULL,
  offer_name VARCHAR(80) NULL, amount INT UNSIGNED NULL, payment_url VARCHAR(500) NULL,
  payment_status VARCHAR(10) NOT NULL DEFAULT 'none', payment_ref VARCHAR(80) NULL,
  sent_at DATETIME NULL, paid_at DATETIME NULL, deleted_at DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (source_table, source_id),
  FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NULL, action VARCHAR(20) NOT NULL,
  summary VARCHAR(500) NOT NULL, ip VARCHAR(45) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (user_id), KEY (created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
