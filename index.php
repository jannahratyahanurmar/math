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
        
        .restart-btn:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧮 Math Quiz</h1>
        
        <?php if ($show_results): ?>
            <!-- RESULTS PAGE -->
            <div class="results">
                <h2>🎉 Quiz Complete!</h2>
                
                <div class="result-card">
                    <h3>Your Final Score:</h3>
                    <p style="font-size: 2em; color: #667eea; margin: 10px 0;">
                        <?php echo $_SESSION['correct']; ?> / <?php echo $_SESSION['total']; ?>
                    </p>
                    <p style="font-size: 1.5em; color: #28a745;">
                        <?php 
                        $percentage = $_SESSION['total'] > 0 ? ($_SESSION['correct'] / $_SESSION['total']) * 100 : 0;
                        echo number_format($percentage, 1) . '%';
                        ?>
                    </p>
                </div>
                
                <div class="save-section">
                    <?php if (isset($_SESSION['save_success'])): ?>
                        <p style="color: #28a745; font-size: 1.2em; margin: 10px 0;">✅ Score saved successfully!</p>
                        <?php unset($_SESSION['save_success']); ?>
                    <?php elseif ($_SESSION['total'] > 0): ?>
                        <h3>Save Your Score</h3>
                        <?php if (isset($error_message)): ?>
                            <p style="color: #e74c3c; margin: 10px 0;"><?php echo $error_message; ?></p>
                        <?php endif; ?>
                        <form method="POST" action="index.php?show_results=1">
                            <input type="text" name="user_name" placeholder="Enter your name (e.g., Mehran)" maxlength="50" required>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <button type="submit" name="save_score">💾 Save Score</button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <button onclick="sessionStorage.removeItem('quizStartTime'); location.href='?restart=1';" class="restart-btn">🔄 Start New Quiz</button>
                
                <div class="leaderboard">
                    <h2>🏆 Top 10 Scores</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Name</th>
                                <th>Score</th>
                                <th>Percentage</th>
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
                                <td><?php echo $score['correct_answers'] . '/' . $score['total_questions']; ?></td>
                                <td><?php echo number_format($score['score_percentage'], 1) . '%'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        <?php else: ?>
            <!-- QUIZ PAGE -->
            <div id="totalTimer" class="total-timer">
                Total Time: <span id="totalTimeDisplay">20:00</span>
            </div>
            
            <div class="score">
                Score: <?php echo $_SESSION['correct']; ?> / <?php echo $_SESSION['total']; ?>
            </div>
            
            <div class="timer" id="timer">Time: 15s</div>
            
            <form method="POST" id="quizForm">
                <div class="question">
                    <?php echo "$num1 + $num2 = "; ?>
                    <input type="number" name="answer" id="answerInput" autofocus required>
                </div>
                
                <input type="hidden" name="correct_answer" value="<?php echo $correct_answer; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <p class="auto-submit-notice">⏱️ Auto-submits when timer reaches 0</p>
            </form>
        <?php endif; ?>
    </div>
    
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
        
        // Question countdown timer
        countdownInterval = setInterval(() => {
            timeLeft--;
            timerElement.textContent = `Time: ${timeLeft}s`;
            
            if (timeLeft <= 0 && !formSubmitted) {
                formSubmitted = true;
                clearInterval(countdownInterval);
                clearInterval(totalTimerInterval);
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