<?php
session_start();
require_once 'config.php'; // Include PDO database configuration

// Student array with structure
$studentFields = array(
    'level' => array('label' => 'Level', 'type' => 'select', 'options' => array('200', '300', '400')),
    'name' => array('label' => 'Full Name', 'type' => 'text', 'placeholder' => 'Enter your full name'),
    'matricule_number' => array('label' => 'Matricule Number', 'type' => 'text', 'placeholder' => 'Enter your matricule number'),
    'sex' => array('label' => 'Gender', 'type' => 'radio', 'options' => array('M' => 'Male', 'F' => 'Female')),
    'age' => array('label' => 'Age', 'type' => 'number', 'placeholder' => 'Enter your age'),
    'password' => array('label' => 'Password', 'type' => 'password', 'placeholder' => 'Enter your password')
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = array();
    
    // Validation
    if (empty($_POST['level'])) $errors[] = "Level is required";
    if (empty($_POST['name'])) $errors[] = "Name is required";
    if (empty($_POST['matricule_number'])) $errors[] = "Matricule number is required";
    if (empty($_POST['sex'])) $errors[] = "Gender is required";
    if (empty($_POST['age']) || !is_numeric($_POST['age']) || $_POST['age'] < 0) $errors[] = "Valid age is required";
    if (empty($_POST['password']) || strlen($_POST['password']) < 6) $errors[] = "Password must be at least 6 characters";
    
    // Check if matricule number already exists
    if (empty($errors)) {
        $matricule = $_POST['matricule_number'];
        
        try {
            $checkStmt = $conn->prepare("SELECT id FROM students WHERE matricule_number = ?");
            $checkStmt->execute([$matricule]);
            
            if ($checkStmt->rowCount() > 0) {
                $errors[] = "Matricule number already exists";
            }
        } catch(PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    if (empty($errors)) {
        try {
            // Prepare and execute INSERT statement with PDO
            $stmt = $conn->prepare("INSERT INTO students (level, name, matricule_number, sex, age, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
            
            $level = $_POST['level'];
            $name = $_POST['name'];
            $matricule_number = $_POST['matricule_number'];
            $sex = $_POST['sex'];
            $age = $_POST['age'];
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            // Execute with array of parameters
            $stmt->execute([$level, $name, $matricule_number, $sex, $age, $password_hash]);
            
            // Get the inserted student ID
            $student_id = $conn->lastInsertId();
            
            // Store student data in session
            $_SESSION['student'] = array(
                'id' => $student_id,
                'level' => $level,
                'name' => $name,
                'matricule_number' => $matricule_number,
                'sex' => $sex,
                'age' => $age
            );
            
            // Redirect to home page
            header('Location: home.php');
            exit();
            
        } catch(PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .registration-container {
            width: 100%;
            max-width: 500px;
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
        
        .registration-form {
            padding: 40px;
        }
        
        .registration-form h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
        }
        
        .error-messages {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
        
        .error {
            margin: 5px 0;
            font-size: 14px;
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
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input::placeholder {
            color: #999;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 5px;
        }
        
        .radio-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: normal;
            color: #555;
        }
        
        .radio-label input[type="radio"] {
            width: auto;
            margin: 0;
        }
        
        .register-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            letter-spacing: 0.5px;
        }
        
        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .register-btn:active {
            transform: translateY(0);
        }
        
        .login-section {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .login-section p {
            color: #666;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .login-btn {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 14px 35px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .login-btn:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
            text-decoration: none;
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
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .registration-form {
                padding: 30px 20px;
            }
            
            .registration-form h1 {
                font-size: 24px;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="registration-form">
            <h1>Student Registration</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <p class="error"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registrationForm">
                <?php foreach ($studentFields as $fieldName => $fieldInfo): ?>
                    <div class="form-group">
                        <label for="<?php echo $fieldName; ?>"><?php echo $fieldInfo['label']; ?>:</label>
                        
                        <?php if ($fieldInfo['type'] === 'select'): ?>
                            <select id="<?php echo $fieldName; ?>" name="<?php echo $fieldName; ?>" required>
                                <option value="">-- Select <?php echo $fieldInfo['label']; ?> --</option>
                                <?php foreach ($fieldInfo['options'] as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>" 
                                        <?php echo isset($_POST[$fieldName]) && $_POST[$fieldName] == $option ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        
                        <?php elseif ($fieldInfo['type'] === 'radio'): ?>
                            <div class="radio-group">
                                <?php foreach ($fieldInfo['options'] as $value => $label): ?>
                                    <label class="radio-label">
                                        <input type="radio" 
                                               name="<?php echo $fieldName; ?>" 
                                               value="<?php echo htmlspecialchars($value); ?>" 
                                               required 
                                               <?php echo isset($_POST[$fieldName]) && $_POST[$fieldName] == $value ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        
                        <?php elseif ($fieldInfo['type'] === 'password'): ?>
                            <div class="password-container">
                                <input type="password" 
                                       id="<?php echo $fieldName; ?>" 
                                       name="<?php echo $fieldName; ?>" 
                                       placeholder="<?php echo $fieldInfo['placeholder']; ?>" 
                                       value="<?php echo isset($_POST[$fieldName]) ? htmlspecialchars($_POST[$fieldName]) : ''; ?>"
                                       required
                                       minlength="6">
                                <button type="button" class="toggle-password" onclick="togglePassword('<?php echo $fieldName; ?>')">👁️</button>
                            </div>
                        
                        <?php else: ?>
                            <input type="<?php echo $fieldInfo['type']; ?>" 
                                   id="<?php echo $fieldName; ?>" 
                                   name="<?php echo $fieldName; ?>" 
                                   placeholder="<?php echo $fieldInfo['placeholder']; ?>" 
                                   value="<?php echo isset($_POST[$fieldName]) ? htmlspecialchars($_POST[$fieldName]) : ''; ?>"
                                   required
                                   <?php echo $fieldInfo['type'] === 'number' ? 'min="1" max="100"' : ''; ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <button type="submit" class="register-btn">Create Account</button>
            </form>
            
            <div class="login-section">
                <p>Already have an account?</p>
                <a href="login.php" class="login-btn">Login to Your Account</a>
                <p style="margin-top: 15px; font-size: 14px; color: #888;">
                    Returning students can login with their matricule number and password
                </p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleBtn = passwordInput.nextElementSibling;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '👁️‍🗨️';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }
        
        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const age = document.getElementById('age').value;
            
            // Validate password length
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return;
            }
            
            // Validate age
            if (age < 1 || age > 100) {
                e.preventDefault();
                alert('Please enter a valid age (1-100)');
                return;
            }
        });
        
        // Auto-focus on first field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('level').focus();
        });
        
        // Age input validation
        document.getElementById('age').addEventListener('input', function(e) {
            if (this.value < 1) this.value = 1;
            if (this.value > 100) this.value = 100;
        });
    </script>
</body>
</html>