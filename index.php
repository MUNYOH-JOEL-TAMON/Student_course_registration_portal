<?php
session_start();
require_once 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $level = filter_input(INPUT_POST, 'level', FILTER_VALIDATE_INT, ["options" => ["min_range" => 200, "max_range" => 400]]);
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $matricule_number = trim(filter_input(INPUT_POST, 'matricule_number', FILTER_SANITIZE_STRING));
    $sex = filter_input(INPUT_POST, 'sex', FILTER_SANITIZE_STRING);
    $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 100]]);
    $password = $_POST['password'];

    // Basic validation
    if (empty($level)) $errors[] = "Level is invalid.";
    if (empty($name)) $errors[] = "Full name is required.";
    if (empty($matricule_number)) $errors[] = "Matricule number is required.";
    if (!in_array($sex, ['M', 'F'])) $errors[] = "Gender is invalid.";
    if (empty($age)) $errors[] = "Age is invalid.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters long.";

    // If no validation errors, proceed with database operations
    if (empty($errors)) {
        try {
            // Check if matricule number already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE matricule_number = ?");
            $stmt->execute([$matricule_number]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "A student with this matricule number already exists.";
            } else {
                // Hash the password for security
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new student record
                $insertStmt = $conn->prepare(
                    "INSERT INTO students (name, matricule_number, level, sex, age, password_hash) VALUES (?, ?, ?, ?, ?, ?)"
                );
                
                $insertStmt->execute([$name, $matricule_number, $level, $sex, $age, $hashed_password]);
                
                // Fetch the new student's data to store in session
                $newStudentStmt = $conn->prepare("SELECT id, name, matricule_number, level, sex, age FROM students WHERE matricule_number = ?");
                $newStudentStmt->execute([$matricule_number]);
                $student = $newStudentStmt->fetch();
                
                // Store student data in session and redirect to home
                $_SESSION['student'] = $student;
                header('Location: home.php');
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 450px;
        }

        .auth-box {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-header {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .auth-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .auth-header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .auth-form {
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4CAF50;
            background: white;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
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
            text-transform: none;
            font-size: 16px;
        }

        .radio-label input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: #4CAF50;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 17px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(76, 175, 80, 0.3);
        }

        .btn-secondary {
            background: #667eea;
            color: white;
            display: inline-block;
            width: auto;
            padding: 12px 25px;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .auth-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .auth-footer p {
            color: #666;
            margin-bottom: 15px;
            font-size: 16px;
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

        .error-message p {
            margin: 5px 0;
        }

        .error-message p:first-child {
            margin-top: 0;
        }

        .error-message p:last-child {
            margin-bottom: 0;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Remove the extra styles that conflict */
        .registration-form,
        .header,
        .form-section,
        .section-label,
        .level-select,
        .input-field,
        .gender-group,
        .gender-option,
        .gender-label,
        .age-input,
        .form-note,
        .submit-btn,
        .sub-label {
            all: unset;
        }

        /* Responsive Design */
        @media (max-width: 576px) {
            .auth-form {
                padding: 30px 20px;
            }
            
            .auth-header {
                padding: 30px 20px;
            }
            
            .auth-header h1 {
                font-size: 26px;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 10px;
            }
            
            body {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1>Student Registration</h1>
                <p>Create your account to access the portal</p>
            </div>
            
            <div class="auth-form">
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="level">Level</label>
                        <select id="level" name="level" required>
                            <option value="">-- Select Level --</option>
                            <option value="200" <?php echo isset($_POST['level']) && $_POST['level'] == '200' ? 'selected' : ''; ?>>200</option>
                            <option value="300" <?php echo isset($_POST['level']) && $_POST['level'] == '300' ? 'selected' : ''; ?>>300</option>
                            <option value="400" <?php echo isset($_POST['level']) && $_POST['level'] == '400' ? 'selected' : ''; ?>>400</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" 
                               placeholder="Enter your full name"
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="matricule_number">Matricule Number</label>
                        <input type="text" id="matricule_number" name="matricule_number"
                               placeholder="Enter your matricule number"
                               value="<?php echo isset($_POST['matricule_number']) ? htmlspecialchars($_POST['matricule_number']) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label>Gender</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="sex" value="M" 
                                       <?php echo isset($_POST['sex']) && $_POST['sex'] == 'M' ? 'checked' : ''; ?> required>
                                Male
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="sex" value="F"
                                       <?php echo isset($_POST['sex']) && $_POST['sex'] == 'F' ? 'checked' : ''; ?> required>
                                Female
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="age">Age</label>
                        <input type="number" id="age" name="age" 
                               placeholder="Enter your age" min="1" max="100"
                               value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password"
                               placeholder="Enter password (min 6 characters)"
                               minlength="6" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </form>
                
                <div class="auth-footer">
                    <p>Already have an account?</p>
                    <a href="login.php" class="btn btn-secondary">Login to Your Account</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>