<?php
// Modify error reporting for production use
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/php-error.log');

$config = require_once '../../backend/php/config.php';

session_start();

if (!isset($_SESSION['email'])) {
    header('Location: ../index.php');
    exit;
} elseif ($_SESSION['accountType'] !== 'Admin' && $_SESSION['accountType'] !== 'Root' && $_SESSION['accountType'] !== 'Staff') {
    header('Location: ../staffPages/profPage.php');
    exit;
} else {
    if ($config['db']['type'] === 'sqlite') {
        $db = new SQLite3($config['db']['sqlite']['path']);
    } elseif ($config['db']['type'] === 'mysql') {
        $dsn = "mysql:host={$config['db']['mysql']['host']};dbname={$config['db']['mysql']['dbname']}";
        $db = new PDO($dsn, $config['db']['mysql']['username'], $config['db']['mysql']['password']);
    }

    // Clean up courses by checking if competency exists in the competency table
    $error = cleanUpCourses();

    $stmt = $db->prepare('SELECT * FROM course');
    $result = $stmt->execute();
    $courses = $result->fetchArray(SQLITE3_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['updateCourseID'], $_POST['updateCourseKey'], $_POST['updateCourseName'], $_POST['updateCompetencyKey'], $_POST['updateObjectiveDescription'], $_POST['updateEvaluationInstrument'], $_POST['updateCompetencyMetric'])) {
            updateCourse();
        } elseif (isset($_POST['delete'])) {
            deleteCourse();
        } elseif (isset($_POST['courseKey'], $_POST['courseName'], $_POST['competencyKey'])) {
            addCourse();
        }
    }

    $courses = []; // Initialize the $courses variable.
    if (isset($_POST['sort'])) {
        $courses = sortTable(); // Save sorted array to the $courses variable
    } else {
        $stmt = $db->prepare('SELECT * FROM course');
        $result = $stmt->execute();
        $courses = fetchAllRows($result);
    }
}

function logAction($action) {
    // Log all actions taken by the user to single a txt file. If txt file does not exist, create it.
    // Log Format: [Date-Time] [Log Level] [User Email] [Transaction ID] [Action] [Status] [Message]
    $log = fopen('../../backend/log/log.txt', 'a');
    fwrite($log, '[' . date('Y-m-d H:i:s') . '] [INFO] ' . $_SESSION['email'] . ' - ' . $action . ' - Success' . PHP_EOL);
    fclose($log);
}

function addCourse() {
    global $db;
    $stmt = $db->prepare('INSERT INTO course (CourseKey, CourseName, CompetencyKey) VALUES (:courseKey, :courseName, :competencyKey)');
    $stmt->bindValue(':courseKey', $_POST['courseKey'], SQLITE3_TEXT);
    $stmt->bindValue(':courseName', $_POST['courseName'], SQLITE3_TEXT);
    $stmt->bindValue(':competencyKey', $_POST['competencyKey'], SQLITE3_TEXT);
    $stmt->execute();

    // if user typed in course key that already exists, display error message
    if ($db->lastErrorCode() === 19) {
        header('Location: adminCourses.php?error=' . urlencode('Course key already exists.'));
    } else {
        logAction('Added course: ' . $_POST['courseKey']);
        header('Location: adminCourses.php');
    }
    exit;
}

function updateCourse() {
    global $db;
    $stmt = $db->prepare('UPDATE course SET CourseKey = :courseKey, CourseName = :courseName, CompetencyKey = :competencyKey WHERE CourseID = :courseID');
    $stmt->bindValue(':courseKey', $_POST['updateCourseKey'], SQLITE3_TEXT);
    $stmt->bindValue(':courseName', $_POST['updateCourseName'], SQLITE3_TEXT);
    $stmt->bindValue(':competencyKey', $_POST['updateCompetencyKey'], SQLITE3_TEXT);
    $stmt->bindValue(':courseID', $_POST['updateCourseID'], SQLITE3_INTEGER);
    $stmt->execute();

    // if user typed in course key that already exists, display error message
    if ($db->lastErrorCode() === 19) {
        header('Location: adminCourses.php?error=' . urlencode('Course key already exists.'));
    } else {
        logAction('Updated course: ' . $_POST['updateCourseKey']);
        header('Location: adminCourses.php');
    }
    exit;
}

function deleteCourse() {
    global $db;
    $stmt = $db->prepare('SELECT * FROM competency WHERE CompetencyID = :competencyID');
    $stmt->bindValue(':courseID', $_POST['delete'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $section = $result->fetchArray(SQLITE3_ASSOC);

    logAction('Deleted course: ' . $section['CourseKey']);

    $stmt = $db->prepare('DELETE FROM course WHERE CourseID = :courseID');
    $stmt->bindValue(':courseID', $_POST['delete'], SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: adminCourses.php');
    exit;
}

function sortTable() {
    global $db;
    $allowed_keys = ['CourseKey', 'CourseName', 'CompetencyKey'];
    $sort = isset($_POST['sort']) && in_array($_POST['sort'], $allowed_keys) ? $_POST['sort'] : 'CourseKey';

    $stmt = $db->prepare("SELECT * FROM course ORDER BY $sort");
    $result = $stmt->execute();
    $courses = fetchAllRows($result);
    return $courses;
}

function fetchAllRows($result) {
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}

function cleanUpCourses() {
    // count number of courses that do not have a competency
    $counter = 0;

    // Check all courses and make competency null if it does not exist in the competency table
    global $db;
    $stmt = $db->prepare('SELECT * FROM course');
    $result = $stmt->execute();
    $courses = fetchAllRows($result);

    foreach ($courses as $course) {
        $stmt = $db->prepare('SELECT * FROM competency WHERE CompetencyKey = :competencyKey');
        $stmt->bindValue(':competencyKey', $course['CompetencyKey'], SQLITE3_TEXT);
        $result = $stmt->execute();
        $competency = $result->fetchArray(SQLITE3_ASSOC);

        if (!$competency) {
            if ($course['CompetencyKey'] !== null) {
                $stmt = $db->prepare('UPDATE course SET CompetencyKey = NULL WHERE CourseID = :courseID');
                $stmt->bindValue(':courseID', $course['CourseID'], SQLITE3_INTEGER);
                $stmt->execute();
            }

            $counter++;
        }
    }

    // Generate error message if there are courses without a competency
    if ($counter > 0) {
        // if more than 1 course does not have a competency, display plural message
        if ($counter > 1) {
            return 'There are ' . $counter . ' courses that do not have a competency. Please assign a competency to each course.';
        } else {
            return 'There is ' . $counter . ' course that does not have a competency. Please assign a competency to each course.';
        }
    }
    return null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Courses Manager</title>
    <link rel="stylesheet" href="../../static/css/headerFooter.css">
    <link rel="stylesheet" href="../../static/css/adminCourses.css">
</head>
<body>
    <div id="headerNav">
        <div class="logo">
            <a href="adminDashboard.php">
                <img src="../../static/img/inter-logo-full.png" alt="Inter CurricuLab">
            </a>
        </div>
        <nav>
            <a href="adminDashboard.php">Dashboard</a>
            <a href="adminTerms.php">Terms</a>
            <a href="adminPrograms.php">Programs</a>
            <div class="dropdown">
                <a href="#">Courses</a>
                <div class="navDropdownContent">
                    <a href="adminCompetency.php">Competencies</a>
                    <a href="adminSection.php">Section</a>
                </div>
            </div>
            <a class="navDivider"></a>
            <a href="adminProgramCourses.php">Program/Courses</a>
            <a href="adminTermCourses.php" class="active">Term/Courses</a>
        </nav>
        <div class="userBox">
            <button onclick="myFunction()" class="userDropdownButton">
                <img src="../../static/img/userProfile.jpg" alt="User" >
                <?php
                echo $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];
                ?>
            </button>
            <div id="userDropdown" class="dropdownContent">
                <a href="adminProfile.php">Profile</a>
                <?php
                if ($_SESSION['accountType'] === 'Admin' || $_SESSION['accountType'] === 'Root') {
                    echo '<a href="adminLogger.php">Logger</a>';
                }
                ?>
                <a href="adminUsers.php">Users</a>
                <a id="logout">Log Out</a>
            </div>
        </div>
    </div>
    <div id="container">
        <div class="container-upperBox">
            <div class="coursesForm">
                <?php if (isset($_GET['error'])): ?>
                    <div class="error">
                        <p><?php echo htmlspecialchars($_GET['error']); ?></p>
                    </div>
                <?php endif; ?>
                <?php
                if ($error !== null) {
                    echo '<div class="error">';
                    echo '<p>' . htmlspecialchars($error) . '</p>';
                    echo '</div>';
                }
                ?>
                <div class="header">
                    <h2>Courses Manager</h2>
                </div>
                <div class="form-container">
                    <form method="POST">
                        <div class="inputBox">
                            <label for="courseKey">Course Key:</label>
                            <input type="text" name="courseKey" id="courseKey" placeholder="Course Key" required>
                        </div>
                        <div class="inputBox">
                            <label for="courseName">Course Name:</label>
                            <input type="text" name="courseName" id="courseName" placeholder="Course Name" required>
                        </div>
                        <div class="inputBox">
                            <label for="competencyKey">Competency ID:</label>
                            <select name="competencyKey" id="competencyKey">
                                <option value="" hidden="hidden">Select a Competency</option>
                                <?php
                                $stmt = $db->prepare('SELECT CompetencyKey FROM competency');
                                $result = $stmt->execute();
                                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($row['CompetencyKey']) . '">' . htmlspecialchars($row['CompetencyKey']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="inputBox">
                            <button type="submit">Add Course</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="coursesTable">
                <table>
                    <tr>
                        <th hidden="hidden">Course ID</th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="CourseKey">Course Key</button>
                            </form>
                        </th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="CourseName">Course Name</button>
                            </form>
                        </th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="CompetencyKey">Competency ID</button>
                            </form>
                        </th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    foreach ($courses as $row) {
                        echo '<tr>';
                        echo '<td hidden>' . htmlspecialchars($row['CourseID'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['CourseKey'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['CourseName'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['CompetencyKey'] ?? '') . '</td>';
                        echo '<td>';
                        ?>
                        <div class="actionButtons">
                            <button class="updateButton">Update</button>
                            <form method="POST">
                                <input type="hidden" name="delete" value="<?php echo htmlspecialchars($row['CourseID'] ?? ''); ?>">
                                <button class="deleteButton" type="submit">Delete</button>
                            </form>
                        </div>
                        <?php
                        echo '</td>';
                        echo '</tr>';
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>
    <div id="updateModal" class="modal">
        <div class="modalContainer">
            <div class="closeButton">
                <span class="close">&times;</span>
            </div>
            <div class="coursesFormUpdate">
                <div class="header">
                    <h2>Update Course</h2>
                </div>
                <div class="form-container">
                    <form method="POST">
                        <div class="inputBox" hidden="hidden">
                            <label for="updateCourseID" hidden="hidden">Course ID:</label>
                            <input type="text" name="updateCourseID" id="updateCourseID" placeholder="Course ID" required hidden="hidden">
                        </div>
                        <?php
                        // If user is Root or Admin, let them update the Course Key
                        if ($_SESSION['accountType'] === 'Root' || $_SESSION['accountType'] === 'Admin') {
                            echo '<div class="inputBox">';
                            echo '<label for="updateCourseKey">Course Key:</label>';
                            echo '<input type="text" name="updateCourseKey" id="updateCourseKey" placeholder="Course Key" required>';
                            echo '<p class="warning">WARNING: Changing the course key can have significant implications. Proceed with caution.</p>';
                            echo '</div>';
                        } else {
                            echo '<div class="inputBox">';
                            echo '<label for="updateCourseKey" hidden="hidden">Course Key:</label>';
                            echo '<input type="text" name="updateCourseKey" id="updateCourseKey" placeholder="Course Key" required hidden="hidden">';
                            echo '</div>';
                        }
                        ?>
                        <div class="inputBox">
                            <label for="updateCourseName">Course Name:</label>
                            <input type="text" name="updateCourseName" id="updateCourseName" placeholder="Course Name" required>
                        </div>
                        <div class="inputBox">
                            <label for="updateCompetencyKey">Competency ID:</label>
                            <select name="updateCompetencyKey" id="updateCompetencyKey">
                                <option value="" hidden="hidden" selected>Select Account Type</option>
                                <?php
                                $stmt = $db->prepare('SELECT CompetencyKey FROM competency');
                                $result = $stmt->execute();
                                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($row['CompetencyKey']) . '">' . htmlspecialchars($row['CompetencyKey']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="inputBox">
                            <button type="submit">Update Course</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
    <script src="../../static/js/adminCourses.js"></script>
</body>
</html>