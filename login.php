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
    
    try {
        // Prepare SQL statement with PDO
        $stmt = $conn->prepare("SELECT id, level, name, matricule_number, sex, age, password_hash FROM students WHERE matricule_number = ?");
        $stmt->execute([$matricule]);
        
        if ($stmt->rowCount() === 1) {
            $studentData = $stmt->fetch();
            
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
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
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
    <!-- Keep your existing login styling here -->
</head>
<body>
    <!-- Keep your existing login HTML structure -->
</body>
</html>