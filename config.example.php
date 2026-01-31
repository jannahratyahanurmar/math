<?php
/**
 * Database Configuration Example
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to 'config.php'
 * 2. Update the credentials below
 * 3. Never commit config.php to version control
 */

return [
    'host' => 'localhost',
    'dbname' => 'math_quiz',
    'username' => 'root',
    'password' => 'your_password_here', // <-- Change this!
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
