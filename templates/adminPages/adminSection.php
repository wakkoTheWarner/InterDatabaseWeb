<?php
// Modify error reporting for production use
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(1);
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
    cleanUpSections();

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
    // Log Format: [Date-Time] [Log Level] [User Email] [Transaction ID] [Action] [Status] [Message]
    $log = fopen('../../backend/log/log.txt', 'a');
    fwrite($log, '[' . date('Y-m-d H:i:s') . '] [INFO] ' . $_SESSION['email'] . ' - ' . $action . ' - Success' . PHP_EOL);
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

function cleanUpSections() {
    global $db;
    $counter = 0;
    $alertMessage = '';

    // Retrieve all sections from the section table
    $stmt = $db->prepare('SELECT * FROM section');
    $result = $stmt->execute();

    $sections = [];
    while ($section = $result->fetchArray(SQLITE3_ASSOC)) {
        $sections[] = $section;
    }

    if (!$sections) return;

    foreach ($sections as $section) {
        // Check if the course associated with the section exists
        $stmt = $db->prepare('SELECT * FROM course WHERE CourseKey = :courseKey');
        $stmt->bindValue(':courseKey', $section['CourseKey'], SQLITE3_TEXT);
        $result = $stmt->execute();
        $course = $result->fetchArray(SQLITE3_ASSOC);

        // If the course does not exist, update the section's course key to NULL
        if (!$course) {
            $stmt = $db->prepare('UPDATE section SET CourseKey = NULL WHERE SectionID = :sectionID');
            $stmt->bindValue(':sectionID', $section['SectionID'], SQLITE3_INTEGER);
            $stmt->execute();
            $counter++;
        }
    }

    // Prepare the query to check if an alert for the current page already exists
    $stmt = $db->prepare('SELECT * FROM alerts WHERE PageName = :pageName AND AlertType = :alertType');
    $stmt->bindValue(':pageName', 'adminSection.php', SQLITE3_TEXT);
    $stmt->bindValue(':alertType', 'danger', SQLITE3_TEXT);
    $result = $stmt->execute();
    $existingAlert = $result->fetchArray(SQLITE3_ASSOC);

    if ($counter > 0) {
        $alertMessage .= 'Course Key(s) were Missing in ' . $counter . ' Section(s)! These sections have been deleted.';

        // If no identical active alert exists, insert the new alert
        if (!$existingAlert) {
            $stmt = $db->prepare('INSERT INTO alerts (PageName, Message, AlertType, IsActive, StartDate, EndDate) VALUES (:pageName, :message, :alertType, :isActive, :startDate, :endDate)');
            $stmt->bindValue(':pageName', 'adminSection.php', SQLITE3_TEXT);
            $stmt->bindValue(':message', $alertMessage, SQLITE3_TEXT);
            $stmt->bindValue(':alertType', 'danger', SQLITE3_TEXT);
            $stmt->bindValue(':isActive', 1, SQLITE3_INTEGER);
            $stmt->bindValue(':startDate', date('Y-m-d H:i:s'), SQLITE3_TEXT);
            $stmt->bindValue(':endDate', date('Y-m-d H:i:s', strtotime('+1 day')), SQLITE3_TEXT);
            $stmt->execute();
        } elseif ($existingAlert['IsActive'] == 0) {
            // If an alert exists for the current page but it's not active, set its IsActive field to 1
            $stmt = $db->prepare('UPDATE alerts SET IsActive = 1, StartDate = :startDate, EndDate = :endDate WHERE AlertID = :alertID');
            $stmt->bindValue(':startDate', date('Y-m-d H:i:s'), SQLITE3_TEXT);
            $stmt->bindValue(':endDate', date('Y-m-d H:i:s', strtotime('+1 day')), SQLITE3_TEXT);
            $stmt->bindValue(':alertID', $existingAlert['AlertID'], SQLITE3_INTEGER);
            $stmt->execute();
        }
    } else {
        // If an alert exists for the current page and it's active, set its IsActive field to 0
        if($existingAlert && $existingAlert['IsActive'] == 1) {
            $stmt = $db->prepare('UPDATE alerts SET IsActive = 0 WHERE AlertID = :alertID');
            $stmt->bindValue(':alertID', $existingAlert['AlertID'], SQLITE3_INTEGER);
            $stmt->execute();
        }
    }
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
                <div class="navDropdownContent">
                    <a href="adminCompetency.php">Competencies</a>
                    <a href="#">Section</a>
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
            <?php
            // Get the SectionID of the current section
            $stmt = $db->prepare('SELECT SectionID FROM section WHERE SectionKey = :sectionKey');
            $stmt->bindValue(':sectionKey', $_POST['sectionKey'], SQLITE3_TEXT);
            $result = $stmt->execute();
            $sectionID = $result->fetchArray(SQLITE3_ASSOC)['SectionID'];

            // Prepare the query to get the active alert for the current page and the current section
            $stmt = $db->prepare('SELECT * FROM alerts WHERE PageName = :pageName AND IsActive = 1 AND StartDate <= :now AND EndDate >= :now AND (DataID = :dataID OR DataID IS NULL)');
            $stmt->bindValue(':pageName', basename($_SERVER['PHP_SELF']), SQLITE3_TEXT);
            $stmt->bindValue(':now', date('Y-m-d H:i:s'), SQLITE3_TEXT);
            $stmt->bindValue(':dataID', $sectionID, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $alert = $result->fetchArray(SQLITE3_ASSOC);

            if ($alert) {
                ?>
                <div class="alert alert-<?php echo htmlspecialchars($alert['AlertType']); ?>" role="alert">
                    <?php echo htmlspecialchars($alert['Message']); ?>
                    <button type="button" class="close" onclick="this.parentElement.style.display='none';">&times;</button>
                </div>
                <?php
            }
            ?>
            <div class="sectionsForm">
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
                            <input type="email" name="professorEmail" id="professorEmail" placeholder="Professor Email" required>
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
                            <input type="email" name="updateProfessorEmail" id="updateProfessorEmail" placeholder="Professor Email" required>
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