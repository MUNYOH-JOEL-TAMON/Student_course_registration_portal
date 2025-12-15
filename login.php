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
    $matricule = trim($_POST['matricule_number']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, level, name, matricule_number, sex, age, password_hash FROM students WHERE matricule_number = ?");
    $stmt->bind_param("s", $matricule);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $studentData = $result->fetch_assoc();
        
        if (password_verify($password, $studentData['password_hash'])) {
            $_SESSION['student'] = array(
                'id' => $studentData['id'],
                'level' => $studentData['level'],
                'name' => $studentData['name'],
                'matricule_number' => $studentData['matricule_number'],
                'sex' => $studentData['sex'],
                'age' => $studentData['age']
            );
            
            header('Location: home.php');
            exit();
        } else {
            $error = "Invalid password. Please try again.";
        }
    } else {
        $error = "Student not found. Please check your matricule number.";
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .login-header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .login-form {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
            background: white;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        
        .form-group input::placeholder {
            color: #999;
        }
        
        .error-message {
            background: #ffebee;
            color: #d32f2f;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
            border-left: 4px solid #d32f2f;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border: none;
            padding: 17px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            letter-spacing: 0.5px;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(76, 175, 80, 0.3);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .register-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .register-link p {
            color: #666;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .register-btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 14px 35px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            border: 2px solid #667eea;
        }
        
        .register-btn:hover {
            background: white;
            color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.2);
        }
        
        .password-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 14px;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #888;
            font-size: 14px;
        }
        
        .login-footer a {
            color: #4CAF50;
            text-decoration: none;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
            }
            
            .login-form {
                padding: 30px 20px;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 28px;
            }
        }
        
        /* Loading animation */
        .loading {
            display: none;
            text-align: center;
            margin-top: 10px;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #4CAF50;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Welcome Back</h1>
            <p>Login to access your student portal</p>
        </div>
        
        <div class="login-form">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="matricule_number">Matricule Number</label>
                    <input type="text" 
                           id="matricule_number" 
                           name="matricule_number" 
                           placeholder="Enter your matricule number"
                           value="<?php echo isset($_POST['matricule_number']) ? htmlspecialchars($_POST['matricule_number']) : ''; ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password" 
                               required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
                    </div>
                </div>
                
                <button type="submit" class="login-btn" id="loginButton">
                    <span id="buttonText">Login to Account</span>
                    <div class="loading" id="loading">
                        <span class="spinner"></span>Logging in...
                    </div>
                </button>
            </form>
            
            <div class="register-link">
                <p>Don't have an account yet?</p>
                <a href="index.php" class="register-btn">Create New Account</a>
            </div>
            
            <div class="login-footer">
                <p>Forgot your password? <a href="#" onclick="alert('Contact administrator to reset your password')">Reset here</a></p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '👁️‍🗨️';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }
        
        // Form submission with loading animation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginButton = document.getElementById('loginButton');
            const buttonText = document.getElementById('buttonText');
            const loading = document.getElementById('loading');
            
            // Validate inputs
            const matricule = document.getElementById('matricule_number').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!matricule || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }
            
            // Show loading animation
            buttonText.style.display = 'none';
            loading.style.display = 'block';
            loginButton.disabled = true;
            
            // Form will submit normally
        });
        
        // Auto-focus on matricule field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('matricule_number').focus();
        });
        
        // Enter key to submit
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });
    </script>
</body>
</html>