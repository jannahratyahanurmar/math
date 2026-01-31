# 🧮 Math Quiz

A fast-paced, accessible math quiz application for practicing addition. Features timed questions, score tracking, and a leaderboard.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

## ✨ Features

- ⏱️ **Timed Questions** - 15 seconds per question with auto-submit
- 🕐 **Quiz Timer** - 20-minute total quiz session
- 🏆 **Leaderboard** - Top 10 scores displayed
- ♿ **Fully Accessible** - WCAG 2.1 AA compliant
- 🔒 **Secure** - CSRF protection & prepared statements
- 📱 **Responsive** - Works on all devices

## 🚀 Quick Start

### Prerequisites

- PHP 7.4+
- MySQL 5.7+
- Web server (Apache/Nginx) or [Laragon](https://laragon.org/)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/math-quiz.git
   cd math-quiz
   ```

2. **Create the database**
   ```bash
   mysql -u root -p < math_quiz.sql
   ```
   Or import `math_quiz.sql` via phpMyAdmin.

3. **Configure database credentials**
   
   Edit `index.php` and update these lines:
   ```php
   $host = 'localhost';
   $dbname = 'math_quiz';
   $username = 'root';
   $password = 'your_password';
   ```

4. **Start your server and visit**
   ```
   http://localhost/math-quiz
   ```

## 📁 Project Structure

```
math-quiz/
├── index.php          # Main application (frontend + backend)
├── math_quiz.sql      # Database schema
└── README.md
```

## 🗄️ Database Schema

```sql
CREATE TABLE quiz_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100),
    correct_answers INT DEFAULT 0,
    total_questions INT DEFAULT 0,
    score_percentage DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 🎮 How to Play

1. Answer addition questions (e.g., `5 + 3 = ?`)
2. You have **15 seconds** per question
3. Total quiz time is **20 minutes**
4. Save your score to the leaderboard when finished
5. Compete for the top 10!

## ♿ Accessibility

This app is built with accessibility in mind:

- ✅ Skip navigation link
- ✅ Screen reader announcements for timers
- ✅ ARIA labels and roles
- ✅ Keyboard navigation
- ✅ High contrast mode support
- ✅ Reduced motion support

## 🔐 Security Features

- CSRF token protection
- PDO prepared statements
- Input validation & sanitization
- XSS prevention with `htmlspecialchars()`

## 📄 License

MIT License - feel free to use this project for learning or personal use.

## 🤝 Contributing

Contributions are welcome! Feel free to open issues or submit pull requests.

---

Made with ❤️ for math learners everywhere
