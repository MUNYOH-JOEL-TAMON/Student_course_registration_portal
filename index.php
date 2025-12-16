<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['student'])) {
    header('Location: home.php');
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $level = trim($_POST['level'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $matricule = trim($_POST['matricule_number'] ?? '');
    $sex = $_POST['sex'] ?? '';
    $age = $_POST['age'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($level)) $errors[] = "Level is required";
    if (empty($name)) $errors[] = "Name is required";
    if (empty($matricule)) $errors[] = "Matricule number is required";
    if (empty($sex)) $errors[] = "Gender is required";
    if (empty($age) || !is_numeric($age) || $age < 1 || $age > 100) $errors[] = "Valid age (1-100) is required";
    if (empty($password) || strlen($password) < 6) $errors[] = "Password must be at least 6 characters";

    // Check if matricule exists
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT id FROM students WHERE matricule_number = ?");
            $stmt->execute([$matricule]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Matricule number already exists";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }

    // Register student
    if (empty($errors)) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO students (level, name, matricule_number, sex, age, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$level, $name, $matricule, $sex, $age, $password_hash]);
            
            $student_id = $conn->lastInsertId();
            
            $_SESSION['student'] = [
                'id' => $student_id,
                'level' => $level,
                'name' => $name,
                'matricule_number' => $matricule,
                'sex' => $sex,
                'age' => $age
            ];
            
            header('Location: home.php');
            exit();
            
        } catch (PDOException $e) {
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