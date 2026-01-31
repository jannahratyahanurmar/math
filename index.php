<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'math_quiz';
$username = 'root'; // Change as needed
$password = 'Tuhin2@@'; // Change as needed

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Unable to connect to database. Please contact support.");
}

// Initialize session variables
if (!isset($_SESSION['correct'])) {
    $_SESSION['correct'] = 0;
    $_SESSION['total'] = 0;
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle answer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid request. Please refresh the page and try again.');
    }
    
    // Validate and sanitize inputs
    $correct_answer = filter_var($_POST['correct_answer'], FILTER_VALIDATE_INT);
    $user_answer = filter_var($_POST['answer'], FILTER_VALIDATE_INT);
    
    if ($correct_answer !== false && $user_answer !== false) {
        $_SESSION['total']++;
        if ($user_answer == $correct_answer) {
            $_SESSION['correct']++;
        }
    }
}

// Save score to database
if (isset($_POST['save_score']) && isset($_POST['user_name'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid request. Please refresh the page and try again.');
    }
    
    // Validate and sanitize user name
    $user_name = trim($_POST['user_name']);
    if (strlen($user_name) > 0 && strlen($user_name) <= 50) {
        $user_name = htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8');
        $correct = $_SESSION['correct'];
        $total = $_SESSION['total'];
        $percentage = ($total > 0) ? ($correct / $total) * 100 : 0;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO quiz_scores (user_name, correct_answers, total_questions, score_percentage) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_name, $correct, $total, $percentage]);
            
            // Reset session
            $_SESSION['correct'] = 0;
            $_SESSION['total'] = 0;
            
            // Redirect to results with success message
            $_SESSION['save_success'] = true;
            header('Location: index.php?show_results=1');
            exit;
        } catch(PDOException $e) {
            error_log("Error saving score: " . $e->getMessage());
            $error_message = "Failed to save score. Please try again.";
        }
    }
}

// Handle restart
if (isset($_GET['restart'])) {
    $_SESSION['correct'] = 0;
    $_SESSION['total'] = 0;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Regenerate CSRF token
    header('Location: index.php');
    exit;
}

// Check if showing results
$show_results = isset($_GET['show_results']);

if (!$show_results) {
    // Generate new question
    $num1 = rand(1, 9);
    $num2 = rand(1, 9);
    $correct_answer = $num1 + $num2;
}

// Get top scores for leaderboard
try {
    $stmt = $pdo->query("SELECT user_name, correct_answers, total_questions, score_percentage, created_at FROM quiz_scores ORDER BY score_percentage DESC, correct_answers DESC LIMIT 10");
    $top_scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching leaderboard: " . $e->getMessage());
    $top_scores = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Practice addition with timed math quizzes. Track your score and compete on the leaderboard.">
    <title>Math Quiz - Addition Practice</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            font-size: 2em;
        }
        
        .score {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            font-size: 1.2em;
            color: #333;
        }
        
        .question {
            font-size: 3em;
            font-weight: bold;
            color: #333;
            margin: 30px 0;
        }
        
        .timer {
            font-size: 1.5em;
            color: #e74c3c;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        input[type="number"] {
            font-size: 2em;
            padding: 15px;
            width: 200px;
            text-align: center;
            border: 3px solid #667eea;
            border-radius: 10px;
            outline: none;
        }
        
        input[type="number"]:focus {
            border-color: #764ba2;
        }
        
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 1.2em;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        button:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .save-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #eee;
        }
        
        input[type="text"] {
            font-size: 1em;
            padding: 10px;
            border: 2px solid #667eea;
            border-radius: 5px;
            margin: 10px 0;
            width: 100%;
        }
        
        .auto-submit-notice {
            color: #e74c3c;
            font-size: 0.9em;
            margin-top: 10px;
        }
        
        .total-timer {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
            color: #856404;
        }
        
        .results {
            text-align: left;
        }
        
        .results h2 {
            color: #667eea;
            margin: 20px 0;
        }
        
        .result-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 5px solid #667eea;
        }
        
        .leaderboard {
            margin-top: 30px;
        }
        
        .leaderboard table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .leaderboard th, .leaderboard td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .leaderboard th {
            background: #667eea;
            color: white;
        }
        
        .restart-btn {
            background: #28a745;
        }
        
        /* Accessibility Improvements */
        .skip-link {
            position: absolute;
            top: -40px;
            left: 0;
            background: #667eea;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 0 0 5px 0;
            z-index: 100;
            transition: top 0.3s;
        }
        
        .skip-link:focus {
            top: 0;
        }
        
        .visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Enhanced focus indicators */
        button:focus,
        input:focus {
            outline: 3px solid #764ba2;
            outline-offset: 2px;
        }
        
        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .container {
                border: 3px solid #000;
            }
            button {
                border: 2px solid #000;
            }
        }
        
        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            button {
                transition: none;
            }
            button:hover {
                transform: none;
            }
        }
        
        .restart-btn:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <!-- Skip Navigation Link -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Live region for screen reader announcements -->
    <div id="sr-announcements" aria-live="polite" aria-atomic="true" class="visually-hidden"></div>
    
    <main id="main-content" class="container" role="main">
        <h1>🧮 Math Quiz</h1>
        
        <?php if ($show_results): ?>
            <!-- RESULTS PAGE -->
            <section class="results" aria-labelledby="results-heading">
                <h2 id="results-heading"><span aria-hidden="true">🎉</span> Quiz Complete!</h2>
                
                <div class="result-card" role="region" aria-label="Your final score">
                    <h3>Your Final Score:</h3>
                    <p style="font-size: 2em; color: #667eea; margin: 10px 0;" aria-label="Score">
                        <?php echo $_SESSION['correct']; ?> correct out of <?php echo $_SESSION['total']; ?> questions
                    </p>
                    <p style="font-size: 1.5em; color: #28a745;" aria-label="Percentage">
                        <?php 
                        $percentage = $_SESSION['total'] > 0 ? ($_SESSION['correct'] / $_SESSION['total']) * 100 : 0;
                        echo number_format($percentage, 1) . '% accuracy';
                        ?>
                    </p>
                </div>
                
                <div class="save-section" role="region" aria-label="Save your score">
                    <?php if (isset($_SESSION['save_success'])): ?>
                        <p style="color: #28a745; font-size: 1.2em; margin: 10px 0;" role="status">
                            <span aria-hidden="true">✅</span> Score saved successfully!
                        </p>
                        <?php unset($_SESSION['save_success']); ?>
                    <?php elseif ($_SESSION['total'] > 0): ?>
                        <h3 id="save-heading">Save Your Score</h3>
                        <?php if (isset($error_message)): ?>
                            <p style="color: #e74c3c; margin: 10px 0;" role="alert"><?php echo $error_message; ?></p>
                        <?php endif; ?>
                        <form method="POST" action="index.php?show_results=1" aria-labelledby="save-heading">
                            <label for="user_name" class="visually-hidden">Your name</label>
                            <input type="text" 
                                   name="user_name" 
                                   id="user_name"
                                   placeholder="Enter your name (e.g., Mehran)" 
                                   maxlength="50" 
                                   required
                                   aria-required="true">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <button type="submit" name="save_score">
                                <span aria-hidden="true">💾</span> Save Score
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <button onclick="sessionStorage.removeItem('quizStartTime'); location.href='?restart=1';" class="restart-btn">
                    <span aria-hidden="true">🔄</span> Start New Quiz
                </button>
                
                <nav class="leaderboard" aria-labelledby="leaderboard-heading">
                    <h2 id="leaderboard-heading"><span aria-hidden="true">🏆</span> Top 10 Scores</h2>
                    <table role="table" aria-describedby="leaderboard-heading">
                        <caption class="visually-hidden">Leaderboard showing top 10 quiz scores</caption>
                        <thead>
                            <tr>
                                <th scope="col">Rank</th>
                                <th scope="col">Name</th>
                                <th scope="col">Score</th>
                                <th scope="col">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            foreach ($top_scores as $score): 
                            ?>
                            <tr>
                                <td><?php echo $rank++; ?></td>
                                <td><?php echo htmlspecialchars($score['user_name']); ?></td>
                                <td><?php echo $score['correct_answers'] . ' of ' . $score['total_questions']; ?></td>
                                <td><?php echo number_format($score['score_percentage'], 1) . '%'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </nav>
            </section>
            
        <?php else: ?>
            <!-- QUIZ PAGE -->
            <section aria-labelledby="quiz-heading">
                <h2 id="quiz-heading" class="visually-hidden">Math Quiz Question</h2>
                
                <div id="totalTimer" class="total-timer" role="timer" aria-label="Total quiz time remaining">
                    <span class="visually-hidden">Total Time Remaining:</span>
                    <span id="totalTimeDisplay" aria-live="off">20:00</span>
                </div>
                
                <div class="score" role="status" aria-label="Current score">
                    <span class="visually-hidden">Current Score:</span>
                    <?php echo $_SESSION['correct']; ?> correct out of <?php echo $_SESSION['total']; ?> questions
                </div>
                
                <div class="timer" id="timer" role="timer" aria-live="assertive" aria-label="Question time remaining">
                    <span class="visually-hidden">Time remaining for this question:</span>
                    <span id="timerValue">15</span> seconds
                </div>
            
            <form method="POST" id="quizForm" aria-describedby="form-instructions">
                <fieldset style="border: none; padding: 0; margin: 0;">
                    <legend class="visually-hidden">Answer the math question</legend>
                    
                    <div class="question">
                        <label for="answerInput">
                            <span aria-hidden="true"><?php echo "$num1 + $num2 = "; ?></span>
                            <span class="visually-hidden">What is <?php echo $num1; ?> plus <?php echo $num2; ?>?</span>
                        </label>
                        <input type="number" 
                               name="answer" 
                               id="answerInput" 
                               autofocus 
                               required
                               aria-required="true"
                               aria-describedby="timer">
                    </div>
                    
                    <input type="hidden" name="correct_answer" value="<?php echo $correct_answer; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <button type="submit" style="margin-top: 15px;">Submit Answer</button>
                    <p id="form-instructions" class="auto-submit-notice" role="note">
                        <span aria-hidden="true">⏱️</span> Auto-submits when timer reaches 0
                    </p>
                </fieldset>
            </form>
            </section>
        <?php endif; ?>
    </main>
    
    <?php if (!$show_results): ?>
    <script>
        let timeLeft = 15;
        let totalTime = 20 * 60; // 20 minutes in seconds
        const timerElement = document.getElementById('timer');
        const form = document.getElementById('quizForm');
        let countdownInterval = null;
        let totalTimerInterval = null;
        let formSubmitted = false;
        
        // Check if quiz time is up
        if (!sessionStorage.getItem('quizStartTime')) {
            sessionStorage.setItem('quizStartTime', Date.now());
        }
        
        const startTime = parseInt(sessionStorage.getItem('quizStartTime'));
        const elapsed = Math.floor((Date.now() - startTime) / 1000);
        
        if (elapsed >= totalTime) {
            // Time's up! Show results
            window.location.href = '?show_results=1';
        }
        
        // Screen reader announcement helper
        function announce(message) {
            const announcer = document.getElementById('sr-announcements');
            announcer.textContent = '';
            setTimeout(() => { announcer.textContent = message; }, 100);
        }
        
        // Question countdown timer
        const timerValue = document.getElementById('timerValue');
        countdownInterval = setInterval(() => {
            timeLeft--;
            timerValue.textContent = timeLeft;
            
            // Announce critical time points for screen readers
            if (timeLeft === 10) {
                announce('10 seconds remaining');
            } else if (timeLeft === 5) {
                announce('5 seconds remaining');
            }
            
            if (timeLeft <= 0 && !formSubmitted) {
                formSubmitted = true;
                clearInterval(countdownInterval);
                clearInterval(totalTimerInterval);
                announce('Time is up. Submitting answer.');
                // Disable input to prevent typing during submission
                document.getElementById('answerInput').disabled = true;
                form.submit();
            }
        }, 1000);
        
        // Update total timer
        const totalTimerDisplay = document.getElementById('totalTimeDisplay');
        
        totalTimerInterval = setInterval(() => {
            const startTime = parseInt(sessionStorage.getItem('quizStartTime'));
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const remaining = totalTime - elapsed;
            
            if (remaining <= 0) {
                clearInterval(countdownInterval);
                clearInterval(totalTimerInterval);
                window.location.href = '?show_results=1';
                return;
            }
            
            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            totalTimerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }, 1000);
        
        // Clean up intervals on page unload
        window.addEventListener('beforeunload', () => {
            if (countdownInterval) clearInterval(countdownInterval);
            if (totalTimerInterval) clearInterval(totalTimerInterval);
        });
    </script>
    <?php endif; ?>
</body>
</html>
