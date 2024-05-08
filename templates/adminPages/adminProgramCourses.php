<?php
// error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/php-error.log');

// config file
$config = require_once '../../backend/php/config.php';

// start session
session_start();

// check if user is logged in
if (!isset($_SESSION['email'])) {
    header('Location: ../index.php');
    exit();
} else {
    // connect to database
    if ($config['db']['type'] === 'sqlite') {
        $db = new SQLite3($config['db']['sqlite']['path']);
    } elseif ($config['db']['type'] === 'mysql') {
        $dsn = "mysql:host={$config['db']['mysql']['host']};dbname={$config['db']['mysql']['dbname']}";
        $db = new PDO($dsn, $config['db']['mysql']['username'], $config['db']['mysql']['password']);
    }

    // Query for course table
    $queryCourse = "SELECT * FROM course";
    $stmtCourse = $db->prepare($queryCourse);
    $resultCourse = $stmtCourse->execute();
    $courses = fetchAllRows($resultCourse);

    // Query for program table
    $queryProgram = "SELECT * FROM program";
    $stmtProgram = $db->prepare($queryProgram);
    $resultProgram = $stmtProgram->execute();
    $programs = fetchAllRows($resultProgram);

    // Query for programCourse table
    $queryProgramCourse = "SELECT * FROM programCourses";
    $stmtProgramCourse = $db->prepare($queryProgramCourse);
    $resultProgramCourse = $stmtProgramCourse->execute();
    $programCourses = fetchAllRows($resultProgramCourse);

    // Get selected program key
    $selectedProgramKey = $_GET['program'] ?? null;
    if ($selectedProgramKey) {
        $queryProgramCourses = "SELECT * FROM programCourses WHERE ProgramKey = :programKey";
        $stmtProgramCourses = $db->prepare($queryProgramCourses);
        $stmtProgramCourses->bindValue(':programKey', $selectedProgramKey, SQLITE3_TEXT);
        $resultProgramCourses = $stmtProgramCourses->execute();
        $programCourses = fetchAllRows($resultProgramCourses);
    } else {
        $programCourses = [];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['addCourse'])) {
            addCourse($db);
        } elseif (isset($_POST['removeCourse'])) {
            removeCourse($db);
        }

        $programKey = $_POST['programKey'];
        $courseKey = $_POST['courseKey'];

        header('Location: ' . $_SERVER['PHP_SELF'] . '?program=' . $programKey);
    }
}

function logAction($action) {
    // Log all actions taken by the user to single a txt file. If txt file does not exist, create it.
    // Log format: [timestamp] [email] [action]
    $log = fopen('../../backend/log/log.txt', 'a');
    fwrite($log, '[' . date('Y-m-d H:i:s') . '] ' . $_SESSION['email'] . '  ' . $action . PHP_EOL);
    fclose($log);
}

function addCourse($db) {
    $programKey = $_POST['programKey'];
    $courseKey = $_POST['courseKey'];

    $query = "INSERT INTO programCourses (ProgramKey, CourseKey) VALUES (:programKey, :courseKey)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':programKey', $programKey, SQLITE3_TEXT);
    $stmt->bindValue(':courseKey', $courseKey, SQLITE3_TEXT);
    $stmt->execute();

    logAction('Added course ' . $courseKey . ' to program ' . $programKey);
}

function removeCourse($db) {
    $programKey = $_POST['programKey'];
    $courseKey = $_POST['courseKey'];

    $query = "DELETE FROM programCourses WHERE ProgramKey = :programKey AND CourseKey = :courseKey";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':programKey', $programKey, SQLITE3_TEXT);
    $stmt->bindValue(':courseKey', $courseKey, SQLITE3_TEXT);
    $stmt->execute();

    logAction('Removed course ' . $courseKey . ' from program ' . $programKey);
}

function fetchAllRows($result) {
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Program/Courses</title>
    <link rel="stylesheet" href="../../static/css/headerFooter.css">
    <link rel="stylesheet" href="../../static/css/adminProgramCourses.css">
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
            <a href="#">Programs</a>
            <div class="dropdown">
                <a href="adminCourses.php">Courses</a>
                <div class="dropdownContent">
                    <a href="adminCompetency.php">Competencies</a>
                    <a href="adminSection.php">Section</a>
                </div>
            </div>
            <a href="#">Program/Courses</a>
        </nav>
        <div class="userBox">
            <button onclick="myFunction()" class="userDropdownButton">
                <img src="../../static/img/userProfile.jpg" alt="User" >
                <?php
                echo $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];
                ?>
            </button>
            <div id="userDropdown" class="dropdownContent">
                <a href="#">Profile</a>
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
            <div class="gridParent">
                <div class="programSelector">
                    <h2>Program Courses Management</h2>
                    <form id="programForm">
                        <label for="program">Program:</label>
                        <select id="program" name="program">
                            <option value="" selected hidden="hidden">Select a program</option>
                            <?php
                            foreach ($programs as $program) {
                                echo "<option value='" . htmlspecialchars($program['ProgramKey']) . "'";
                                if ($selectedProgramKey == $program['ProgramKey']) {
                                    echo " selected";
                                }
                                echo ">" . htmlspecialchars($program['ProgramName']) . "</option>";
                            }
                            ?>
                        </select>
                        <button id="programSelectorButton">Select</button>
                        <button id="resetButton">Reset</button>
                    </form>
                </div>
                <div class="coursesTable">
                    <h2>Available Courses</h2>
                    <table>
                        <thead>
                        <tr>
                            <th hidden="hidden">Course ID</th>
                            <th>Course Key</th>
                            <th>Course Name</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <?php
                        if (!$selectedProgramKey) {
                            echo '<tbody hidden="hidden">';
                        } else {
                            echo '<tbody>';
                        }
                        ?>
                        <!-- Display courses that are not in the selected program -->
                        <?php
                        foreach ($courses as $course) {
                            $isCourseInProgram = false;
                            foreach ($programCourses as $programCourse) {
                                if ($programCourse['CourseKey'] == $course['CourseKey']) {
                                    $isCourseInProgram = true;
                                    break;
                                }
                            }
                            if (!$isCourseInProgram) {
                                echo '<tr>';
                                echo '<td hidden="hidden">' . $course['CourseID'] . '</td>';
                                echo '<td>' . $course['CourseKey'] . '</td>';
                                echo '<td>' . $course['CourseName'] . '</td>';
                                ?>
                                <td>
                                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                        <input type="hidden" name="programKey" value="<?php echo $selectedProgramKey; ?>">
                                        <input type="hidden" name="courseKey" value="<?php echo $course['CourseKey']; ?>">
                                        <button type="submit" name="addCourse" class="addButton">Add</button>
                                    </form>
                                </td>
                                <?php
                                echo '</tr>';
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <div class="selectedProgram" hidden="hidden">
                    <table>
                        <thead>
                        <tr>
                            <th hidden="hidden">Program ID</th>
                            <th>Program Key</th>
                            <th>Program Name</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- Display selected program -->
                        <?php
                        foreach ($programs as $program) {
                            if ($program['ProgramKey'] == $selectedProgramKey) {
                                echo '<tr>';
                                echo '<td hidden="hidden">' . $program['ProgramID'] . '</td>';
                                echo '<td>' . $program['ProgramKey'] . '</td>';
                                echo '<td>' . $program['ProgramName'] . '</td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <div class="addedCourses">
                    <h2>Assigned Courses</h2>
                    <table>
                        <thead>
                        <tr>
                            <th hidden="hidden">Course ID</th>
                            <th>Course Key</th>
                            <th>Course Name</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- Display courses that are in the selected program -->
                        <?php
                        foreach ($courses as $course) {
                            $isCourseInProgram = false;
                            foreach ($programCourses as $programCourse) {
                                if ($programCourse['CourseKey'] == $course['CourseKey']) {
                                    $isCourseInProgram = true;
                                    break;
                                }
                            }
                            if ($isCourseInProgram) {
                                echo '<tr>';
                                echo '<td hidden="hidden">' . $course['CourseID'] . '</td>';
                                echo '<td>' . $course['CourseKey'] . '</td>';
                                echo '<td>' . $course['CourseName'] . '</td>';
                                ?>
                                <td>
                                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                        <input type="hidden" name="programKey" value="<?php echo $selectedProgramKey; ?>">
                                        <input type="hidden" name="courseKey" value="<?php echo $course['CourseKey']; ?>">
                                        <button type="submit" name="removeCourse" class="removeButton">Remove</button>
                                    </form>
                                </td>
                                <?php
                                echo '</tr>';
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <div class="programCoursesTable" hidden="hidden">
                    <table>
                        <thead>
                        <tr>
                            <th hidden="hidden">Program Course ID</th>
                            <th>Program Key</th>
                            <th>Course Key</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($programCourses as $programCourse): ?>
                            <tr>
                                <td hidden="hidden"><?php echo $programCourse['ProgramCourseID']; ?></td>
                                <td><?php echo $programCourse['ProgramKey']; ?></td>
                                <td><?php echo $programCourse['CourseKey']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
    <script src="../../static/js/adminProgramCourses.js"></script>
</body>
</html>