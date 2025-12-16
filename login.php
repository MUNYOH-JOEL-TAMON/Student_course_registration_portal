<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['student'])) {
    header('Location: home.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = trim($_POST['matricule_number'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($matricule) || empty($password)) {
        $error = "Please enter both matricule number and password";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, level, name, matricule_number, sex, age, password_hash FROM students WHERE matricule_number = ?");
            $stmt->execute([$matricule]);
            
            if ($stmt->rowCount() === 1) {
                $student = $stmt->fetch();
                
                if (password_verify($password, $student['password_hash'])) {
                    $_SESSION['student'] = [
                        'id' => $student['id'],
                        'level' => $student['level'],
                        'name' => $student['name'],
                        'matricule_number' => $student['matricule_number'],
                        'sex' => $student['sex'],
                        'age' => $student['age']
                    ];
                    
                    header('Location: home.php');
                    exit();
                } else {
                    $error = "Invalid password";
                }
            } else {
                $error = "Student not found. Please check your matricule number.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1>Student Login</h1>
                <p>Access your student portal</p>
            </div>
            
            <div class="auth-form">
                <?php if (isset($_GET['message'])): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($_GET['message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="matricule_number">Matricule Number</label>
                        <input type="text" id="matricule_number" name="matricule_number"
                               placeholder="Enter your matricule number"
                               value="<?php echo isset($_POST['matricule_number']) ? htmlspecialchars($_POST['matricule_number']) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password"
                               placeholder="Enter your password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
                
                <div class="auth-footer">
                    <p>Don't have an account?</p>
                    <a href="index.php" class="btn btn-secondary">Create New Account</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>