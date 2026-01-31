-- Database Schema (Run this first in phpMyAdmin or MySQL)
CREATE DATABASE IF NOT EXISTS math_quiz;
USE math_quiz;

CREATE TABLE IF NOT EXISTS quiz_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100),
    correct_answers INT DEFAULT 0,
    total_questions INT DEFAULT 0,
    score_percentage DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);