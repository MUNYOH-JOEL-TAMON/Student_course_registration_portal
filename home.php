<?php
session_start();
require_once 'config.php'; // Include database configuration

// Check if student is registered
if (!isset($_SESSION['student'])) {
    header('Location: index.php');
    exit();
}

$student = $_SESSION['student'];

// Fetch courses from database based on student level
$courses = array();
$level = $student['level'];
$courseQuery = $conn->prepare("SELECT course_code, course_name, credits FROM courses WHERE level = ?");
$courseQuery->bind_param("s", $level);
$courseQuery->execute();
$result = $courseQuery->get_result();

while ($row = $result->fetch_assoc()) {
    $courses[] = array(
        'code' => $row['course_code'],
        'name' => $row['course_name'],
        'credits' => $row['credits']
    );
}
$courseQuery->close();

// Handle course registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_courses'])) {
    if (isset($_POST['courses'])) {
        $selectedCourses = $_POST['courses'];
        $student_id = $student['id'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Remove existing registrations for this student
            $deleteStmt = $conn->prepare("DELETE FROM student_courses WHERE student_id = ?");
            $deleteStmt->bind_param("i", $student_id);
            $deleteStmt->execute();
            $deleteStmt->close();
            
            // Insert new course registrations
            $insertStmt = $conn->prepare("INSERT INTO student_courses (student_id, course_code) VALUES (?, ?)");
            
            foreach ($selectedCourses as $courseCode) {
                $insertStmt->bind_param("is", $student_id, $courseCode);
                $insertStmt->execute();
            }
            
            $insertStmt->close();
            $conn->commit();
            
            $_SESSION['registered_courses'] = $selectedCourses;
            $registeredCourses = $selectedCourses;
            
            echo "<script>alert('Courses registered successfully!');</script>";
            
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Error registering courses: " . $conn->error . "');</script>";
        }
    }
}

// Fetch registered courses from database
$registeredCourses = array();
if (isset($student['id'])) {
    $regQuery = $conn->prepare("
        SELECT c.course_code 
        FROM student_courses sc 
        JOIN courses c ON sc.course_code = c.course_code 
        WHERE sc.student_id = ?
    ");
    $regQuery->bind_param("i", $student['id']);
    $regQuery->execute();
    $regResult = $regQuery->get_result();
    
    while ($row = $regResult->fetch_assoc()) {
        $registeredCourses[] = $row['course_code'];
    }
    $regQuery->close();
    
    // Store in session for quick access
    $_SESSION['registered_courses'] = $registeredCourses;
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Additional styling for the registered courses section */
        .credits-badge {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .level-badge {
            display: inline-block;
            background: #e3f2fd;
            color: #1565c0;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .total-credits-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        .total-credits-label {
            font-size: 18px;
            color: #2c3e50;
            font-weight: 600;
        }

        .total-credits-value {
            font-size: 32px;
            color: #4CAF50;
            font-weight: 700;
        }

        .empty-courses-message {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .register-now-btn {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }

        .register-now-btn:hover {
            background: #45a049;
            text-decoration: none;
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
                                    <h3><?php echo htmlspecialchars($course['name']); ?></h3>
                                    <p><strong>Code:</strong> <?php echo htmlspecialchars($course['code']); ?></p>
                                    <p><strong>Credits:</strong> <?php echo htmlspecialchars($course['credits']); ?></p>
                                    <label class="course-checkbox">
                                        <input type="checkbox" name="courses[]" value="<?php echo htmlspecialchars($course['code']); ?>"
                                               <?php echo in_array($course['code'], $registeredCourses) ? 'checked' : ''; ?>>
                                        Register for this course
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No courses available for your level.</p>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="register_courses" class="submit-btn">Save Course Registration</button>
                </form>

            </section>

        <?php elseif ($page === 'registered'): ?>
            <section class="registered-section">
                <h1>📚 My Registered Courses</h1>
                
                <?php if (!empty($registeredCourses)): ?>
                    <div class="courses-table-container">
                        <table class="courses-table">
                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Credits</th>
                                    <th>Level</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalCredits = 0;
                                foreach ($registeredCourses as $courseCode): 
                                    $courseDetailQuery = $conn->prepare("SELECT course_name, credits, level FROM courses WHERE course_code = ?");
                                    $courseDetailQuery->bind_param("s", $courseCode);
                                    $courseDetailQuery->execute();
                                    $courseDetailResult = $courseDetailQuery->get_result();
                                    
                                    if ($courseRow = $courseDetailResult->fetch_assoc()): 
                                        $totalCredits += $courseRow['credits'];
                                ?>
                                    <tr>
                                        <td>
                                            <strong style="color: #2c3e50;"><?php echo htmlspecialchars($courseCode); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($courseRow['course_name']); ?></td>
                                        <td>
                                            <span class="credits-badge">
                                                <?php echo htmlspecialchars($courseRow['credits']); ?> credits
                                            </span>
                                        </td>
                                        <td>
                                            <span class="level-badge">
                                                Level <?php echo htmlspecialchars($courseRow['level']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: #4CAF50; font-weight: 600;">✅ Registered</span>
                                        </td>
                                    </tr>
                                <?php 
                                    endif;
                                    $courseDetailQuery->close();
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="total-credits-box">
                        <div class="total-credits-label">
                            Total Registered Credits
                        </div>
                        <div class="total-credits-value">
                            <?php echo $totalCredits; ?> credits
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <p style="color: #666; font-size: 16px;">
                            <strong>Note:</strong> You can modify your course selection by visiting the 
                            <a href="home.php?page=courses" style="color: #4CAF50; font-weight: 600;">Courses Registration</a> page.
                        </p>
                    </div>
                    
                    <!-- Course Summary Stats -->
                    <div style="margin-top: 40px; display: flex; gap: 20px; flex-wrap: wrap; justify-content: center;">
                        <div style="background: #e8f5e9; padding: 20px; border-radius: 10px; min-width: 200px; text-align: center;">
                            <div style="font-size: 14px; color: #666; margin-bottom: 8px;">Total Courses</div>
                            <div style="font-size: 32px; color: #4CAF50; font-weight: 700;">
                                <?php echo count($registeredCourses); ?>
                            </div>
                        </div>
                        
                        <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; min-width: 200px; text-align: center;">
                            <div style="font-size: 14px; color: #666; margin-bottom: 8px;">Total Credits</div>
                            <div style="font-size: 32px; color: #2196F3; font-weight: 700;">
                                <?php echo $totalCredits; ?>
                            </div>
                        </div>
                        
                        <div style="background: #fff3e0; padding: 20px; border-radius: 10px; min-width: 200px; text-align: center;">
                            <div style="font-size: 14px; color: #666; margin-bottom: 8px;">Average Credits/Course</div>
                            <div style="font-size: 32px; color: #FF9800; font-weight: 700;">
                                <?php echo count($registeredCourses) > 0 ? round($totalCredits / count($registeredCourses), 1) : 0; ?>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="empty-courses-message">
                        <p style="font-size: 24px; color: #666; margin-bottom: 15px;">📝 No Courses Registered</p>
                        <p style="font-size: 16px; color: #888; margin-bottom: 30px;">
                            You haven't registered for any courses yet. Start by selecting courses from your level.
                        </p>
                        <a href="home.php?page=courses" class="register-now-btn">
                            Register Courses Now
                        </a>
                    </div>
                <?php endif; ?>
            </section>

        <?php elseif ($page === 'about'): ?>
            <section class="about-section">
                <h1>About Us</h1>
                <div class="about-content">
                    <p>Welcome to our Student Portal. This platform is designed to provide students with an easy way to register and manage their courses based on their academic level.</p>
                    <h2>Our Features:</h2>
                    <ul>
                        <li>Simple student registration process</li>
                        <li>Level-based course selection</li>
                        <li>Secure login system</li>
                        <li>Course management dashboard</li>
                    </ul>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2025 Student Portal. All rights reserved.</p>
    </footer>
</body>
</html>