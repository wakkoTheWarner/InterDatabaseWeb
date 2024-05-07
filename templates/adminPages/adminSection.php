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
} else {
    if ($config['db']['type'] === 'sqlite') {
        $db = new SQLite3($config['db']['sqlite']['path']);
    } elseif ($config['db']['type'] === 'mysql') {
        $dsn = "mysql:host={$config['db']['mysql']['host']};dbname={$config['db']['mysql']['dbname']}";
        $db = new PDO($dsn, $config['db']['mysql']['username'], $config['db']['mysql']['password']);
    }

    $stmt = $db->prepare('SELECT * FROM section');
    $result = $stmt->execute();
    $sections = $result->fetchArray(SQLITE3_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['updateSectionID'], $_POST['updateSectionKey'], $_POST['updateCourseKey'], $_POST['updateProfessorEmail'])) {
            updateSection();
        } elseif (isset($_POST['delete'])) {
            deleteSection();
        } elseif (isset($_POST['sectionKey'], $_POST['courseKey'], $_POST['professorEmail'])) {
            addSection();
        }
    }

    $sections = []; // Initialize the $sections variable.
    if (isset($_POST['sort'])) {
        $sections = sortTable(); // Save sorted array to the $sections variable
    } else {
        $stmt = $db->prepare('SELECT * FROM section');
        $result = $stmt->execute();
        $sections = fetchAllRows($result);
    }
}

function logAction($action) {
    // Log all actions taken by the user to single a txt file. If txt file does not exist, create it.
    // Log format: [timestamp] [email] [action]
    $log = fopen('../../backend/log/log.txt', 'a');
    fwrite($log, '[' . date('Y-m-d H:i:s') . '] ' . $_SESSION['email'] . '  ' . $action . PHP_EOL);
    fclose($log);
}

function addSection() {
    global $db;
    $stmt = $db->prepare('INSERT INTO section (SectionKey, CourseKey, ProfessorEmail) VALUES (:sectionKey, :courseKey, :professorEmail)');
    $stmt->bindValue(':sectionKey', $_POST['sectionKey'], SQLITE3_TEXT);
    $stmt->bindValue(':courseKey', $_POST['courseKey'], SQLITE3_TEXT);
    $stmt->bindValue(':professorEmail', $_POST['professorEmail'], SQLITE3_TEXT);
    $stmt->execute();

    // if user typed in section key that already exists, display error message
    if ($db->lastErrorCode() === 19) {
        header('Location: adminSection.php?error=' . urlencode('Section key already exists.'));
    } else {
        logAction('Added section: ' . $_POST['sectionKey']);
        header('Location: adminSection.php');
    }
    exit;
}

function updateSection() {
    global $db;
    $stmt = $db->prepare('UPDATE section SET SectionKey = :sectionKey, CourseKey = :courseKey, ProfessorEmail = :professorEmail WHERE SectionID = :sectionID');
    $stmt->bindValue(':sectionKey', $_POST['updateSectionKey'], SQLITE3_TEXT);
    $stmt->bindValue(':courseKey', $_POST['updateCourseKey'], SQLITE3_TEXT);
    $stmt->bindValue(':professorEmail', $_POST['updateProfessorEmail'], SQLITE3_TEXT);
    $stmt->bindValue(':sectionID', $_POST['updateSectionID'], SQLITE3_INTEGER);
    $stmt->execute();

    // if user typed in section key that already exists, display error message
    if ($db->lastErrorCode() === 19) {
        header('Location: adminSection.php?error=' . urlencode('Section key already exists.'));
    } else {
        logAction('Updated section: ' . $_POST['updateSectionKey']);
        header('Location: adminSection.php');
    }
    exit;
}

function deleteSection() {
    global $db;
    // query the section ID to get the section key, course key, and professor email for the logger
    $stmt = $db->prepare('SELECT * FROM section WHERE SectionID = :sectionID');
    $stmt->bindValue(':sectionID', $_POST['delete'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $section = $result->fetchArray(SQLITE3_ASSOC);

    logAction('Deleted section: ' . $section['SectionKey']);

    $stmt = $db->prepare('DELETE FROM section WHERE SectionID = :sectionID');
    $stmt->bindValue(':sectionID', $_POST['delete'], SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: adminSection.php');
    exit;
}

function sortTable() {
    global $db;
    $allowed_keys = ['SectionKey', 'CourseKey', 'ProfessorEmail'];
    $sort = isset($_POST['sort']) && in_array($_POST['sort'], $allowed_keys) ? $_POST['sort'] : 'SectionKey';

    $stmt = $db->prepare("SELECT * FROM section ORDER BY $sort");
    $result = $stmt->execute();
    $sections = fetchAllRows($result);
    return $sections;
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
    <title>Admin Sections Manager</title>
    <link rel="stylesheet" href="../../static/css/headerFooter.css">
    <link rel="stylesheet" href="../../static/css/adminSection.css">
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
                <div class="dropdownContent">
                    <a href="adminCompetency.php">Competencies</a>
                    <a href="#">Section</a>
                </div>
            </div>
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
            <div class="sectionsForm">
                <?php if (isset($_GET['error'])): ?>
                    <div class="error">
                        <p><?php echo htmlspecialchars($_GET['error']); ?></p>
                    </div>
                <?php endif; ?>
                <div class="header">
                    <h2>Sections Manager</h2>
                </div>
                <div class="form-container">
                    <form method="POST">
                        <div class="inputBox">
                            <label for="sectionKey">Section Key:</label>
                            <input type="text" name="sectionKey" id="sectionKey" placeholder="Section Key" required>
                        </div>
                        <div class="inputBox">
                            <label for="courseKey">Course Key:</label>
                            <select name="courseKey" id="courseKey">
                                <option value="" hidden="hidden" selected>Select Course Key</option>
                                <?php
                                $stmt = $db->prepare('SELECT CourseKey FROM course');
                                $result = $stmt->execute();
                                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($row['CourseKey']) . '">' . htmlspecialchars($row['CourseKey']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="inputBox">
                            <label for="professorEmail">Professor Email:</label>
                            <select name="professorEmail" id="professorEmail">
                                <option value="" hidden="hidden" selected>Select Professor Email</option>
                                <?php
                                $stmt = $db->prepare('SELECT Email FROM user WHERE AccountType = "Professor"');
                                $result = $stmt->execute();
                                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($row['Email']) . '">' . htmlspecialchars($row['Email']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="inputBox">
                            <button type="submit">Add Section</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="sectionsTable">
                <table>
                    <tr>
                        <th hidden="hidden">Section ID</th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="SectionKey">Section Key</button>
                            </form>
                        </th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="CourseKey">Course Key</button>
                            </form>
                        </th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="ProfessorEmail">Professor Email</button>
                            </form>
                        </th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    foreach ($sections as $row) {
                        echo '<tr>';
                        echo '<td hidden>' . htmlspecialchars($row['SectionID'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['SectionKey'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['CourseKey'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['ProfessorEmail'] ?? '') . '</td>';
                        echo '<td>';
                        ?>
                        <div class="actionButtons">
                            <button class="updateButton">Update</button>
                            <form method="POST">
                                <input type="hidden" name="delete" value="<?php echo htmlspecialchars($row['SectionID'] ?? ''); ?>">
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
            <div class="sectionsFormUpdate">
                <div class="header">
                    <h2>Update Section</h2>
                </div>
                <div class="form-container">
                    <form method="POST">
                        <div class="inputBox" hidden="hidden">
                            <label for="updateSectionID" hidden="hidden">Section ID:</label>
                            <input type="text" name="updateSectionID" id="updateSectionID" placeholder="Section ID" required hidden="hidden">
                        </div>
                        <?php
                        // If user is Root or Admin, let them update the Section Key
                        if ($_SESSION['accountType'] === 'Root' || $_SESSION['accountType'] === 'Admin') {
                            echo '<div class="inputBox">';
                            echo '<label for="updateSectionKey">Section Key:</label>';
                            echo '<input type="text" name="updateSectionKey" id="updateSectionKey" placeholder="Section Key" required>';
                            echo '<p class="warning">WARNING: Changing the section key can have significant implications. Proceed with caution.</p>';
                            echo '</div>';
                        } else {
                            echo '<div class="inputBox">';
                            echo '<label for="updateSectionKey" hidden="hidden">Section Key:</label>';
                            echo '<input type="text" name="updateSectionKey" id="updateSectionKey" placeholder="Section Key" required hidden="hidden">';
                            echo '</div>';
                        }
                        ?>
                        <div class="inputBox">
                            <label for="updateCourseKey">Course Key:</label>
                            <select name="updateCourseKey" id="updateCourseKey">
                                <option value="" hidden="hidden" selected>Select Course Key</option>
                                <?php
                                $stmt = $db->prepare('SELECT CourseKey FROM course');
                                $result = $stmt->execute();
                                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($row['CourseKey']) . '">' . htmlspecialchars($row['CourseKey']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="inputBox">
                            <label for="updateProfessorEmail">Professor Email:</label>
                            <select name="updateProfessorEmail" id="updateProfessorEmail">
                                <option value="" hidden="hidden" selected>Select Professor Email</option>
                                <?php
                                $stmt = $db->prepare('SELECT Email FROM user WHERE AccountType = "Professor"');
                                $result = $stmt->execute();
                                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($row['Email']) . '">' . htmlspecialchars($row['Email']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="inputBox">
                            <button type="submit">Update Section</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
    <script src="../../static/js/adminSection.js"></script>
</body>
</html>