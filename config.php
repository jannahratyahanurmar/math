<?php
/**
 * Database Configuration
 * 
 * SECURITY: This file should be:
 * 1. Added to .gitignore (never commit credentials)
 * 2. Ideally placed outside the web root
 * 
 * For production, consider using environment variables instead.
 */

return [
    'host' => 'localhost',
    'dbname' => 'math_quiz',
    'username' => 'root',
    'password' => '', // Set your password here
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
