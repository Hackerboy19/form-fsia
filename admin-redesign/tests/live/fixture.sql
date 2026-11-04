-- A small copy of the Hostinger database shape, built from the field names the forms post.
-- Dates are VARCHAR like many old PHP tables, so broken values such as 0000-00-00 can be stored.
SET NAMES utf8mb4;

CREATE TABLE savemissindia (
  id INT AUTO_INCREMENT PRIMARY KEY, fname VARCHAR(120), mobile VARCHAR(20), email VARCHAR(120), dob VARCHAR(20), age VARCHAR(5),
  birthday VARCHAR(5), birthmonth VARCHAR(10), birthyear VARCHAR(5), city VARCHAR(60), state VARCHAR(60), landing VARCHAR(60),
  photo VARCHAR(200), created_at DATETIME
);
INSERT INTO savemissindia (fname, mobile, email, dob, age, city, state, landing, photo, created_at) VALUES
 ('Riya Sharma', '9876500001', 'riya@example.com', '2003-04-05', '23', 'New Delhi', 'Delhi', 'Instagram', 'riya.jpg', NOW() - INTERVAL 1 DAY),
 ('Kavya Joshi', '+91 98765 00002', 'kavya@example.com', '2001-01-10', '25', 'Jaipur', 'Rajasthan', 'Google', 'kavya.jpg', NOW() - INTERVAL 3 DAY),
 ('NULL', '98765', 'broken-email', '0000-00-00', '19', 'Dwarka', 'Delhi', '', '', NOW() - INTERVAL 5 DAY);

CREATE TABLE savemrsindia (
  id INT AUTO_INCREMENT PRIMARY KEY, fname VARCHAR(120), mobile VARCHAR(20), email VARCHAR(120), dob VARCHAR(20), age VARCHAR(5),
  city VARCHAR(60), state VARCHAR(60), landing VARCHAR(60), category VARCHAR(60), reg_type VARCHAR(30), created_at DATETIME
);
INSERT INTO savemrsindia (fname, mobile, email, dob, age, city, state, landing, category, reg_type, created_at) VALUES
 ('Meera Nair', '9876500003', 'meera@example.com', '1990-01-15', '36', 'Kochi', 'Kerala', 'Facebook', 'Classic', 'Self', NOW() - INTERVAL 2 DAY);

-- Shared by several business/event forms; older rows still hold the fields old form versions asked for.
-- Its id and date columns are not called id / created_at, to check that the API finds them.
CREATE TABLE savefashiondesigner (
  sno INT AUTO_INCREMENT PRIMARY KEY, fname VARCHAR(120), mobile VARCHAR(20), email VARCHAR(120), dob VARCHAR(20), age VARCHAR(5),
  city VARCHAR(60), state VARCHAR(60), fsia_source VARCHAR(60), regtype VARCHAR(30), education VARCHAR(60), totalmarks VARCHAR(10),
  instname VARCHAR(100), workexpr VARCHAR(30), address VARCHAR(200), kyc VARCHAR(300), comment TEXT, password VARCHAR(100), reg_date DATETIME
);
INSERT INTO savefashiondesigner (fname, mobile, email, dob, age, city, state, fsia_source, regtype, education, totalmarks, instname, workexpr, address, kyc, comment, password, reg_date) VALUES
 ('Diya Das', '9876500004', 'diya@example.com', '1999-02-02', '27', 'Kolkata', 'West Bengal', 'Fashion Designer', 'Designer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NOW() - INTERVAL 4 DAY),
 ('Kavya Rao', '9876500005', 'kavyarao@example.com', '1998-03-03', '28', 'New Delhi', 'Delhi', 'Celebrity PR Agency', '', 'Graduate', '80%', 'City College', '3 years', 'Hauz Khas, Delhi', 'kyc_front.jpg, kyc_back.jpg', 'Has done two shows.', 'plain-old-password', NOW() - INTERVAL 200 DAY);

CREATE TABLE saveworld (
  id INT AUTO_INCREMENT PRIMARY KEY, regtype VARCHAR(30), fname VARCHAR(120), dob VARCHAR(20), age VARCHAR(5), state VARCHAR(60), city VARCHAR(60),
  country VARCHAR(60), email VARCHAR(120), ccode VARCHAR(6), mobile_local VARCHAR(20), mobile VARCHAR(25), ncategory VARCHAR(60), created_at DATETIME
);
INSERT INTO saveworld (regtype, fname, dob, age, state, city, country, email, ccode, mobile_local, mobile, ncategory, created_at) VALUES
 ('Miss World', 'Asha Thapa', '2002-07-07', '24', 'Bagmati', 'Kathmandu', 'Nepal', 'asha@example.com', '+977', '9812345678', '+9779812345678', 'Miss World 2026', NOW() - INTERVAL 6 HOUR);

CREATE TABLE more_pages (
  id INT AUTO_INCREMENT PRIMARY KEY, page_name VARCHAR(20), meta_title VARCHAR(200), descritpion TEXT, meta_keyword TEXT,
  og_title VARCHAR(200), og_description TEXT, og_image VARCHAR(200), app_fee VARCHAR(40), fee_title VARCHAR(100),
  fee_heading VARCHAR(100), fee_subtitle TEXT, journey_note TEXT
);
INSERT INTO more_pages (page_name, meta_title, descritpion, app_fee, fee_title) VALUES
 ('100', 'Miss Delhi 2026 Registration | Forever Star India', 'Register for Miss Delhi 2026.', '₹ 1,999', 'Audition fee'),
 ('46', 'Miss Rajasthan 2026 Registration | Forever Star India', '', '₹ 1,999', 'Audition fee');

CREATE TABLE otp_verifications (
  id INT AUTO_INCREMENT PRIMARY KEY, mobile VARCHAR(20), otp_code VARCHAR(10), expires_at DATETIME, is_verified TINYINT(1) DEFAULT 0, created_at DATETIME
);
INSERT INTO otp_verifications (mobile, otp_code, expires_at, is_verified, created_at) VALUES
 ('9876500001', '123456', NOW() - INTERVAL 1 DAY, 1, NOW() - INTERVAL 1 DAY),
 ('9876500002', '654321', NOW() - INTERVAL 2 DAY, 0, NOW() - INTERVAL 2 DAY);

CREATE TABLE city (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(60));
