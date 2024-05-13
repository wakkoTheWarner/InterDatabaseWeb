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

    cleanUpTermCourses($db);

    // query for course table
    $queryCourse = "SELECT * FROM course";
    $stmtCourse = $db->prepare($queryCourse);
    $resultCourse = $stmtCourse->execute();
    $courses = fetchAllRows($resultCourse);

    // query for term table
    $queryTerm = "SELECT * FROM term";
    $stmtTerm = $db->prepare($queryTerm);
    $resultTerm = $stmtTerm->execute();
    $terms = fetchAllRows($resultTerm);

    // query for section table
    $querySection = "SELECT * FROM section";
    $stmtSection = $db->prepare($querySection);
    $resultSection = $stmtSection->execute();
    $sections = fetchAllRows($resultSection);

    // query for term course table
    $queryTermCourse = "SELECT * FROM termCourses";
    $stmtTermCourse = $db->prepare($queryTermCourse);
    $resultTermCourse = $stmtTermCourse->execute();
    $termCourses = fetchAllRows($resultTermCourse);

    // Get selected term key
    $selectedTermKey = $_GET['term'] ?? null;
    if ($selectedTermKey) {
        $queryTermCourses = "SELECT * FROM termCourses WHERE TermKey = :termKey";
        $stmtTermCourses = $db->prepare($queryTermCourses);
        $stmtTermCourses->bindValue(':termKey', $selectedTermKey, SQLITE3_TEXT);
        $resultTermCourses = $stmtTermCourses->execute();
        $termCourses = fetchAllRows($resultTermCourses);
    } else {
        $termCourses = [];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['addCourse'])) {
            addCourse($db);
        } elseif (isset($_POST['removeCourse'])) {
            removeCourse($db);
        }

        $termKey = $_POST['termKey'];
        $courseKey = $_POST['courseKey'];

        header('Location: ' . $_SERVER['PHP_SELF'] . '?term=' . $termKey);
    }
}

function logAction($action) {
    // Log all actions taken by the user to single a txt file. If txt file does not exist, create it.
    // Log Format: [Date-Time] [Log Level] [User Email] [Transaction ID] [Action] [Status] [Message]
    $log = fopen('../../backend/log/log.txt', 'a');
    fwrite($log, '[' . date('Y-m-d H:i:s') . '] [INFO] ' . $_SESSION['email'] . ' - ' . $action . ' - Success' . PHP_EOL);
    fclose($log);
}

function addCourse($db) {
    $termKey = $_POST['termKey'];
    $courseKey = $_POST['courseKey'];

    $query = "INSERT INTO termCourses (TermKey, CourseKey) VALUES (:termKey, :courseKey)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':termKey', $termKey, SQLITE3_TEXT);
    $stmt->bindValue(':courseKey', $courseKey, SQLITE3_TEXT);
    $stmt->execute();

    logAction('Added course ' . $courseKey . ' to term ' . $termKey);
}

function removeCourse($db) {
    $termKey = $_POST['termKey'];
    $courseKey = $_POST['courseKey'];

    $query = "DELETE FROM termCourses WHERE TermKey = :termKey AND CourseKey = :courseKey";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':termKey', $termKey, SQLITE3_TEXT);
    $stmt->bindValue(':courseKey', $courseKey, SQLITE3_TEXT);
    $stmt->execute();

    logAction('Removed course ' . $courseKey . ' from term ' . $termKey);
}

function fetchAllRows($result) {
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}

function cleanUpTermCourses($db) {
    // Get all termCourses
    $queryTermCourses = "SELECT * FROM termCourses";
    $stmtTermCourses = $db->prepare($queryTermCourses);
    $resultTermCourses = $stmtTermCourses->execute();
    $termCourses = fetchAllRows($resultTermCourses);

    foreach ($termCourses as $termCourse) {
        // check if term exists
        $queryTerm = "SELECT * FROM term WHERE TermKey = :termKey";
        $stmtTerm = $db->prepare($queryTerm);
        $stmtTerm->bindValue(':termKey', $termCourse['TermKey'], SQLITE3_TEXT);
        $resultTerm = $stmtTerm->execute();
        $term = $resultTerm->fetchArray(SQLITE3_ASSOC);

        // check if course exists
        $queryCourse = "SELECT * FROM course WHERE CourseKey = :courseKey";
        $stmtCourse = $db->prepare($queryCourse);
        $stmtCourse->bindValue(':courseKey', $termCourse['CourseKey'], SQLITE3_TEXT);
        $resultCourse = $stmtCourse->execute();
        $course = $resultCourse->fetchArray(SQLITE3_ASSOC);

        // if term or course does not exist, delete termCourse
        if (empty($term) || empty($course)) {
            $queryDelete = "DELETE FROM termCourses WHERE TermKey = :termKey AND CourseKey = :courseKey";
            $stmtDelete = $db->prepare($queryDelete);
            $stmtDelete->bindValue(':termKey', $termCourse['TermKey'], SQLITE3_TEXT);
            $stmtDelete->bindValue(':courseKey', $termCourse['CourseKey'], SQLITE3_TEXT);
            $stmtDelete->execute();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Term/Courses</title>
    <link rel="stylesheet" href="../../static/css/headerFooter.css">
    <link rel="stylesheet" href="../../static/css/adminTermCourses.css">
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
                <a href="adminCourses.php">Courses</a>
                <div class="navDropdownContent">
                    <a href="adminCompetency.php">Competencies</a>
                    <a href="adminSection.php">Section</a>
                </div>
            </div>
            <a class="navDivider"></a>
            <a href="adminProgramCourses.php">Program/Courses</a>
            <a href="#" class="active">Term/Courses</a>
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
                <div class="termSelector">
                    <h2>Term Courses Management</h2>
                    <form id="termForm">
                        <label for="term">Term:</label>
                        <select id="term" name="term">
                            <option value="" selected hidden="hidden">Select a term</option>
                            <?php
                            foreach ($terms as $term) {
                                echo '<option value="' . htmlspecialchars($term['TermKey']) . '"';
                                if ($selectedTermKey == $term['TermKey']) {
                                    echo ' selected';
                                }
                                echo '>' . htmlspecialchars($term['TermKey']) . ' | ' . htmlspecialchars($term['TermName']) . '</option>';
                            }
                            ?>
                        </select>
                        <button id="termSelectorButton">Select</button>
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
                        if (!$selectedTermKey) {
                            echo '<tbody hidden="hidden">';
                        } else {
                            echo '<tbody>';
                        }
                        ?>
                        <!-- Display courses that are not in the selected program -->
                        <?php
                        foreach ($courses as $course) {
                            $isCourseInTerm = false;
                            foreach ($termCourses as $termCourse) {
                                if ($termCourse['CourseKey'] == $course['CourseKey']) {
                                    $isCourseInTerm = true;
                                    break;
                                }
                            }
                            if (!$isCourseInTerm) {
                                echo '<tr>';
                                echo '<td hidden="hidden">' . $course['CourseID'] . '</td>';
                                echo '<td>' . $course['CourseKey'] . '</td>';
                                echo '<td>' . $course['CourseName'] . '</td>';
                                ?>
                                <td>
                                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                        <input type="hidden" name="termKey" value="<?php echo $selectedTermKey; ?>">
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
                <div class="addedCourses">
                    <h2>Added Courses</h2>
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
                            <?php
                            foreach ($courses as $course) {
                                $isCourseInTerm = false;
                                foreach ($termCourses as $termCourse) {
                                    if ($course['CourseKey'] === $termCourse['CourseKey']) {
                                        $isCourseInTerm = true;
                                        break;
                                    }
                                }
                                if ($isCourseInTerm) {
                                    echo '<tr>';
                                    echo '<td hidden="hidden">' . $course['CourseID'] . '</td>';
                                    echo '<td>' . $course['CourseKey'] . '</td>';
                                    echo '<td>' . $course['CourseName'] . '</td>';
                                    ?>
                                    <td>
                                        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                            <input type="hidden" name="termKey" value="<?php echo $selectedTermKey; ?>">
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
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
    <script src="../../static/js/adminTermCourses.js"></script>
</body>
</html>
