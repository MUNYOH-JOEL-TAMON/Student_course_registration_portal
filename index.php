<?php
session_start();
require_once 'config.php'; // Include database configuration

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
    
    // Validation (same as before)
    if (empty($_POST['level'])) $errors[] = "Level is required";
    if (empty($_POST['name'])) $errors[] = "Name is required";
    if (empty($_POST['matricule_number'])) $errors[] = "Matricule number is required";
    if (empty($_POST['sex'])) $errors[] = "Gender is required";
    if (empty($_POST['age']) || !is_numeric($_POST['age']) || $_POST['age'] < 0) $errors[] = "Valid age is required";
    if (empty($_POST['password']) || strlen($_POST['password']) < 6) $errors[] = "Password must be at least 6 characters";
    
    // Check if matricule number already exists
    if (empty($errors)) {
        $matricule = $_POST['matricule_number'];
        $checkStmt = $conn->prepare("SELECT id FROM students WHERE matricule_number = ?");
        $checkStmt->bind_param("s", $matricule);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            $errors[] = "Matricule number already exists";
        }
        $checkStmt->close();
    }
    
    if (empty($errors)) {
        // Prepare and execute INSERT statement
        $stmt = $conn->prepare("INSERT INTO students (level, name, matricule_number, sex, age, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
        
        $level = $_POST['level'];
        $name = $_POST['name'];
        $matricule_number = $_POST['matricule_number'];
        $sex = $_POST['sex'];
        $age = $_POST['age'];
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $stmt->bind_param("ssssis", $level, $name, $matricule_number, $sex, $age, $password_hash);
        
        if ($stmt->execute()) {
            // Get the inserted student ID
            $student_id = $stmt->insert_id;
            
            // Store student data in session
            $_SESSION['student'] = array(
                'id' => $student_id,
                'level' => $level,
                'name' => $name,
                'matricule_number' => $matricule_number,
                'sex' => $sex,
                'age' => $age
            );
            
            $stmt->close();
            
            // Redirect to home page
            header('Location: home.php');
            exit();
        } else {
            $errors[] = "Registration failed: " . $conn->error;
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
    <div class="registration-container">
        <div class="registration-form">
            <h1>Student Registration Form</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <p class="error"><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Form fields remain the same -->
                <?php foreach ($studentFields as $fieldName => $fieldInfo): ?>
                    <div class="form-group">
                        <label for="<?php echo $fieldName; ?>"><?php echo $fieldInfo['label']; ?></label>
                        
                        <?php if ($fieldInfo['type'] === 'select'): ?>
                            <select id="<?php echo $fieldName; ?>" name="<?php echo $fieldName; ?>" required>
                                <option value="">-- Select <?php echo $fieldInfo['label']; ?> --</option>
                                <?php foreach ($fieldInfo['options'] as $option): ?>
                                    <option value="<?php echo $option; ?>" <?php echo isset($_POST[$fieldName]) && $_POST[$fieldName] == $option ? 'selected' : ''; ?>>
                                        <?php echo $option; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        
                        <?php elseif ($fieldInfo['type'] === 'radio'): ?>
                            <div class="radio-group">
                                <?php foreach ($fieldInfo['options'] as $value => $label): ?>
                                    <label class="radio-label">
                                        <input type="radio" name="<?php echo $fieldName; ?>" value="<?php echo $value; ?>" 
                                               required <?php echo isset($_POST[$fieldName]) && $_POST[$fieldName] == $value ? 'checked' : ''; ?>>
                                        <?php echo $label; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        
                        <?php else: ?>
                            <input type="<?php echo $fieldInfo['type']; ?>" 
                                   id="<?php echo $fieldName; ?>" 
                                   name="<?php echo $fieldName; ?>" 
                                   placeholder="<?php echo $fieldInfo['placeholder']; ?>" 
                                   value="<?php echo isset($_POST[$fieldName]) ? htmlspecialchars($_POST[$fieldName]) : ''; ?>"
                                   required>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <button type="submit" class="register-btn">Register</button>
                <div style="text-align: center; margin-top: 20px;">
    <p style="color: #666;">Already have an account?</p>
    <a href="login.php" style="display: inline-block; background: #4CAF50; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin-top: 10px;">
        Login Here
    </a>
</div>
            </form>
        </div>
    </div>
</body>
</html>