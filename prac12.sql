CREATE DATABASE event_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE event_manager;

CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  event_date DATE NOT NULL,
  location VARCHAR(255) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
