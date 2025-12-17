<?php
session_start();
require_once 'config.php';

// Check if student is logged in
if (!isset($_SESSION['student'])) {
    header('Location: login.php');
    exit();
}

$student = $_SESSION['student'];
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Fetch courses for student's level
$courses = [];
try {
    $stmt = $conn->prepare("SELECT course_code, course_name, credits FROM courses WHERE level = ?");
    $stmt->execute([$student['level']]);
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching courses: " . $e->getMessage());
}

// Handle course registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_courses'])) {
    if (isset($_POST['courses'])) {
        $selectedCourses = $_POST['courses'];
        $student_id = $student['id'];
        
        try {
            $conn->beginTransaction();
            
            // Remove existing registrations
            $deleteStmt = $conn->prepare("DELETE FROM student_courses WHERE student_id = ?");
            $deleteStmt->execute([$student_id]);
            
            // Insert new registrations
            $insertStmt = $conn->prepare("INSERT INTO student_courses (student_id, course_code) VALUES (?, ?)");
            foreach ($selectedCourses as $courseCode) {
                $insertStmt->execute([$student_id, $courseCode]);
            }
            
            $conn->commit();
            $_SESSION['registered_courses'] = $selectedCourses;
            
            echo "<script>alert('Courses registered successfully!');</script>";
            
        } catch (PDOException $e) {
            $conn->rollBack();
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

// Fetch registered courses
$registeredCourses = [];
try {
    $stmt = $conn->prepare("SELECT c.course_code FROM student_courses sc JOIN courses c ON sc.course_code = c.course_code WHERE sc.student_id = ?");
    $stmt->execute([$student['id']]);
    $registeredCourses = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {
    // Handle error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - <?php echo htmlspecialchars(ucfirst($page)); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .registered-section {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .courses-table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .courses-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 16px;
        }
        .courses-table thead {
            background-color:  #2c3e50;
            color: white;
        }
        .courses-table th {
            padding: 20px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .courses-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.3s ease;
        }
        .courses-table tbody tr:last-child {
            border-bottom: none;
        }
        .courses-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .courses-table td {
            padding: 20px;
            color: #555;
        }
        .courses-table .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .badge-credits {
            background-color: #e0f7fa;
            color: #00796b;
        }
        .badge-level {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        .total-credits-summary {
            margin-top: 30px;
            padding: 30px;
            background: #2c3e50;
            color: white;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .total-credits-summary h4 {
            font-size: 22px;
            font-weight: 600;
        }
        .total-credits-summary p {
            font-size: 32px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2>Student Portal</h2>
            </div>
            <ul class="nav-menu">
                <li><a href="home.php?page=home" class="nav-link <?php echo $page === 'home' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="home.php?page=courses" class="nav-link <?php echo $page === 'courses' ? 'active' : ''; ?>">Courses</a></li>
                <li><a href="home.php?page=registered" class="nav-link <?php echo $page === 'registered' ? 'active' : ''; ?>">My Courses</a></li>
                <li><a href="home.php?page=about" class="nav-link <?php echo $page === 'about' ? 'active' : ''; ?>">About</a></li>
                <li><a href="logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="main-content">
        <?php if ($page === 'home'): ?>
            <section class="home-section">
                <h1>Welcome, <?php echo htmlspecialchars($student['name']); ?>!</h1>
                <div class="student-info">
                    <h2>Your Information</h2>
                    <div class="info-card">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
                        <p><strong>Matricule Number:</strong> <?php echo htmlspecialchars($student['matricule_number']); ?></p>
                        <p><strong>Level:</strong> <?php echo htmlspecialchars($student['level']); ?></p>
                        <p><strong>Gender:</strong> <?php echo $student['sex'] === 'M' ? 'Male' : 'Female'; ?></p>
                        <p><strong>Age:</strong> <?php echo htmlspecialchars($student['age']); ?></p>
                        <p><strong>Registered Courses:</strong> <?php echo count($registeredCourses); ?> course(s)</p>
                    </div>
                </div>
            </section>

        <?php elseif ($page === 'courses'): ?>
            <section class="courses-section">
                <h1>Course Registration</h1>
                <p class="level-info">Available courses for Level <?php echo htmlspecialchars($student['level']); ?></p>
                
                <form method="POST" action="">
                    <div class="courses-grid">
                        <?php if (!empty($courses)): ?>
                            <?php foreach ($courses as $course): ?>
                                <div class="course-card">
                                    <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                                    <p><strong>Code:</strong> <?php echo htmlspecialchars($course['course_code']); ?></p>
                                    <p><strong>Credits:</strong> <?php echo htmlspecialchars($course['credits']); ?></p>
                                    <label class="course-checkbox">
                                        <input type="checkbox" name="courses[]" value="<?php echo htmlspecialchars($course['course_code']); ?>"
                                               <?php echo in_array($course['course_code'], $registeredCourses) ? 'checked' : ''; ?>>
                                        Register for this course
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty-message">No courses available for your level.</p>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="register_courses" class="submit-btn">Save Course Registration</button>
                </form>
            </section>

        <?php elseif ($page === 'registered'): ?>
            <section class="registered-section">
                <h1>My Registered Courses</h1>
                
                <?php if (!empty($registeredCourses)): ?>
                    <?php 
                    $totalCredits = 0;
                    $courseDetails = [];
                    
                    foreach ($registeredCourses as $courseCode) {
                        $stmt = $conn->prepare("SELECT course_name, credits, level FROM courses WHERE course_code = ?");
                        $stmt->execute([$courseCode]);
                        if ($course = $stmt->fetch()) {
                            $courseDetails[] = $course;
                            $totalCredits += $course['credits'];
                        }
                    }
                    ?>
                    
                    <div class="courses-table-container">
                        <table class="courses-table">
                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Credits</th>
                                    <th>Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registeredCourses as $index => $courseCode): ?>
                                    <?php if (isset($courseDetails[$index])): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($courseCode); ?></td>
                                        <td><?php echo htmlspecialchars($courseDetails[$index]['course_name']); ?></td>
                                        <td><span class="badge badge-credits"><?php echo $courseDetails[$index]['credits']; ?> credits</span></td>
                                        <td><span class="badge badge-level">Level <?php echo $courseDetails[$index]['level']; ?></span></td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="total-credits-summary">
                        <h4>Total Registered Credits</h4>
                        <p><?php echo $totalCredits; ?></p>
                    </div>
                    
                <?php else: ?>
                    <div class="empty-courses-message">
                        <h3>📝 No Courses Registered</h3>
                        <p>You haven't registered for any courses yet. Start by selecting courses from your level.</p>
                        <a href="home.php?page=courses" class="btn btn-primary">Register Courses Now</a>
                    </div>
                <?php endif; ?>
            </section>

        <?php elseif ($page === 'about'): ?>
            <section class="about-section">
                <h1>About Student Portal</h1>
                <div class="about-content">
                    <p>Welcome to the Student Portal, a comprehensive platform designed to streamline course registration and academic management for students.</p>
                    
                    <h2>Our Mission</h2>
                    <p>To provide students with an intuitive, secure, and efficient platform for managing their academic journey, from course registration to progress tracking.</p>
                    
                    <h2>Key Features</h2>
                    <ul>
                        <li>Easy student registration and profile management</li>
                        <li>Level-based course selection system</li>
                        <li>Secure login and data protection</li>
                        <li>Real-time course registration and updates</li>
                        <li>Academic progress tracking and reporting</li>
                    </ul>
                    
                    <h2>How It Works</h2>
                    <p>Register your account, select your academic level, and choose from available courses. The system automatically tracks your registrations and provides detailed reports on your academic progress.</p>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Student Portal. All rights reserved.</p>
    </footer>
</body>
</html>