
-- Blessed Board - Database Schema


CREATE DATABASE IF NOT EXISTS blessed_board
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE blessed_board;


-- Stores admin accounts (the people who manage the system)
CREATE TABLE IF NOT EXISTS admins (
  admin_id   INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name  VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Stores users who submit prayer requests
CREATE TABLE IF NOT EXISTS users (
  user_id        INT AUTO_INCREMENT PRIMARY KEY,
  full_name      VARCHAR(200) NOT NULL,
  email          VARCHAR(150) NOT NULL UNIQUE,
  contact_number VARCHAR(20)  DEFAULT NULL,
  created_at     DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Stores the types of prayer requests (Family, Health, Career, etc.)
CREATE TABLE IF NOT EXISTS categories (
  category_id   INT AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(100) NOT NULL,
  description   TEXT         DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pre-load the default categories so the form works right away
INSERT IGNORE INTO categories (category_name, description) VALUES
  ('Family',          'Requests related to family matters'),
  ('Health',          'Healing and health-related prayers'),
  ('Career',          'Job, work, and career guidance'),
  ('Financial',       'Financial blessings and stability'),
  ('Spiritual Growth','Faith and spiritual development'),
  ('Other',           'General prayer requests');


-- Stores all submitted prayer requests
-- Linked to users (who submitted) and categories (what type)
-- Status tracks progress: Pending -> In Progress -> Answered
CREATE TABLE IF NOT EXISTS prayer_requests (
  request_id   INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT          NOT NULL,
  category_id  INT          NOT NULL,
  title        VARCHAR(255) NOT NULL,
  description  TEXT         NOT NULL,
  status       ENUM('Pending','In Progress','Answered') DEFAULT 'Pending',
  submitted_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)     REFERENCES users(user_id)          ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Records every action an admin does on a prayer request (audit trail)
CREATE TABLE IF NOT EXISTS admin_logs (
  log_id      INT AUTO_INCREMENT PRIMARY KEY,
  admin_id    INT          NOT NULL,
  request_id  INT          NOT NULL,
  action      VARCHAR(100) NOT NULL,
  remarks     TEXT         DEFAULT NULL,
  action_date DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id)   REFERENCES admins(admin_id)            ON DELETE CASCADE,
  FOREIGN KEY (request_id) REFERENCES prayer_requests(request_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Records every time an admin exports a report (CSV, PDF, etc.)
CREATE TABLE IF NOT EXISTS admin_download_logs (
  download_id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id    INT          NOT NULL,
  action      VARCHAR(100) NOT NULL,
  remarks     TEXT         DEFAULT NULL,
  action_date DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX (admin_id),
  FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;





-- QUERY 1: High-Activity Users Admin Report


SELECT 
    a.first_name AS admin_first_name,
    a.last_name  AS admin_last_name,
    al.action    AS admin_action,
    pr.title     AS request_title,
    c.category_name,
    u.full_name  AS requester_name,
    al.action_date AS admin_action_date
FROM admin_logs al
JOIN admins          a  ON al.admin_id    = a.admin_id
JOIN prayer_requests pr ON al.request_id  = pr.request_id
JOIN categories      c  ON pr.category_id = c.category_id
JOIN users           u  ON pr.user_id     = u.user_id
WHERE u.user_id IN (
    -- Get users whose request count is above average
    SELECT user_id 
    FROM prayer_requests 
    GROUP BY user_id 
    HAVING COUNT(request_id) > (
        -- Calculate the average number of requests per user
        SELECT AVG(req_count) 
        FROM (
            SELECT COUNT(request_id) AS req_count 
            FROM prayer_requests 
            GROUP BY user_id
        ) AS UserRequestCounts
    )
)
ORDER BY al.action_date DESC;



-- QUERY 2: Category Analytics Dashboard


WITH CategoryStats AS (
    SELECT 
        category_id,
        COUNT(request_id) AS total_requests,
        MIN(submitted_at) AS earliest_request_date,
        MAX(submitted_at) AS latest_request_date
    FROM prayer_requests
    GROUP BY category_id
)
SELECT 
    c.category_name,
    cs.total_requests,
    DATE(cs.earliest_request_date) AS earliest_request_date,
    DATE(cs.latest_request_date)   AS latest_request_date,
    -- Most recent person who submitted in this category
    (SELECT u.full_name 
     FROM prayer_requests pr
     JOIN users u ON pr.user_id = u.user_id
     WHERE pr.category_id = c.category_id
     ORDER BY pr.submitted_at DESC 
     LIMIT 1) AS latest_requester_name,
    -- Percentage of requests this month vs last 2 months
    (
        SELECT ROUND(
            (COUNT(CASE WHEN MONTH(pr.submitted_at) = MONTH(CURRENT_DATE) THEN 1 END) * 100.0 / 
            NULLIF(COUNT(*), 0)), 2
        )
        FROM prayer_requests pr 
        WHERE pr.category_id = c.category_id
        AND pr.submitted_at >= DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH)
    ) AS this_month_percentage
FROM categories c
JOIN CategoryStats cs ON c.category_id = cs.category_id
ORDER BY cs.total_requests DESC;



-- QUERY 3: Monthly Prayer Request Computation


WITH MonthlyRequestStats AS (
    SELECT 
        DATE_FORMAT(submitted_at, '%Y-%m') AS report_month,
        category_id,
        COUNT(request_id)       AS total_monthly_requests,
        COUNT(DISTINCT user_id) AS unique_users_monthly
    FROM prayer_requests
    GROUP BY DATE_FORMAT(submitted_at, '%Y-%m'), category_id
)
SELECT 
    mrs.report_month,
    c.category_name,
    mrs.total_monthly_requests,
    mrs.unique_users_monthly,
    -- % change compared to the previous month
    (
        SELECT ROUND(
            ((current_month.total_requests - prev_month.total_requests) * 100.0 / 
            NULLIF(prev_month.total_requests, 0)), 2
        )
        FROM MonthlyRequestStats prev_month
        WHERE prev_month.category_id = mrs.category_id
        AND prev_month.report_month = DATE_FORMAT(DATE_SUB(mrs.report_month + '-01', INTERVAL 1 MONTH), '%Y-%m')
    ) AS month_over_month_growth,
    -- % of requests that were marked Answered that month
    (
        SELECT ROUND(
            (SUM(CASE WHEN pr.status = 'Answered' THEN 1 ELSE 0 END) * 100.0 / 
            NULLIF(COUNT(*), 0)), 2
        )
        FROM prayer_requests pr
        WHERE DATE_FORMAT(pr.submitted_at, '%Y-%m') = mrs.report_month
        AND pr.category_id = mrs.category_id
    ) AS answer_rate_percentage
FROM MonthlyRequestStats mrs
JOIN categories c ON mrs.category_id = c.category_id
ORDER BY mrs.report_month DESC, mrs.total_monthly_requests DESC;