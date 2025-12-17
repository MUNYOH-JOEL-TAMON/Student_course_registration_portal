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
    <style>
        /* Internal CSS for login.php */
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

        .auth-container {
            width: 100%;
            max-width: 450px;
        }

        .auth-box {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .auth-header {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 35px 30px;
            text-align: center;
        }

        .auth-header h1 {
            font-size: 32px;
            margin-bottom: 8px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .auth-header p {
            font-size: 16px;
            opacity: 0.95;
            font-weight: 400;
        }

        .auth-form {
            padding: 35px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            color: #333;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
            background: white;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.15);
        }

        .form-group input::placeholder {
            color: #95a5a6;
            font-size: 15px;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            margin-top: 10px;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #45a049, #3d8b40);
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(76, 175, 80, 0.25);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: #667eea;
            color: white;
            display: inline-block;
            width: auto;
            padding: 12px 25px;
            margin-top: 10px;
            font-size: 16px;
        }

        .btn-secondary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(102, 126, 234, 0.25);
        }

        .auth-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .auth-footer p {
            color: #666;
            margin-bottom: 12px;
            font-size: 15px;
        }

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            border-left: 4px solid #2e7d32;
            animation: fadeIn 0.5s ease-out;
        }

        .error-message {
            background: #ffebee;
            color: #d32f2f;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            border-left: 4px solid #d32f2f;
            animation: shake 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Separator styling */
        .separator {
            height: 1px;
            background: linear-gradient(to right, transparent, #ddd, transparent);
            margin: 25px 0;
            display: none; /* Hidden as per your original code structure */
        }

        /* Responsive Design */
        @media (max-width: 576px) {
            body {
                padding: 15px;
            }
            
            .auth-form {
                padding: 25px 20px;
            }
            
            .auth-header {
                padding: 25px 20px;
            }
            
            .auth-header h1 {
                font-size: 26px;
            }
            
            .btn-secondary {
                width: 100%;
                text-align: center;
            }
            
            .form-group input {
                padding: 12px 15px;
            }
        }

        @media (max-width: 400px) {
            .auth-header h1 {
                font-size: 22px;
            }
            
            .auth-header p {
                font-size: 14px;
            }
            
            .btn {
                padding: 14px;
                font-size: 16px;
            }
        }
    </style>
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