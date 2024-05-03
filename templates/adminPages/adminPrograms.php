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

    $stmt = $db->prepare('SELECT * FROM program');
    $result = $stmt->execute();
    $programs = $result->fetchArray(SQLITE3_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['updateProgramID'], $_POST['updateProgramKey'], $_POST['updateProgramName'])) {
            updateProgram();
        } elseif (isset($_POST['delete'])) {
            deleteProgram();
        } elseif (isset($_POST['programKey'], $_POST['programName'])) {
            addProgram();
        }
    }

    $programs = []; // Initialize the $programs variable.
    if (isset($_POST['sort'])) {
        $programs = sortTable(); // Save sorted array to the $programs variable
    } else {
        $stmt = $db->prepare('SELECT * FROM program');
        $result = $stmt->execute();
        $programs = fetchAllRows($result);
    }
}

function addProgram() {
    global $db;
    $stmt = $db->prepare('INSERT INTO program (ProgramKey, ProgramName) VALUES (:programKey, :programName)');
    $stmt->bindValue(':programKey', $_POST['programKey'], SQLITE3_TEXT);
    $stmt->bindValue(':programName', $_POST['programName'], SQLITE3_TEXT);
    $stmt->execute();

    // if user typed in program key that already exists, display error message
    if ($db->lastErrorCode() === 19) {
        header('Location: adminPrograms.php?error=' . urlencode('Program key already exists.'));
    } else {
        header('Location: adminPrograms.php');
    }
    exit;
}

function updateProgram() {
    global $db;
    $stmt = $db->prepare('UPDATE program SET ProgramKey = :programKey, ProgramName = :programName WHERE ProgramID = :programID');
    $stmt->bindValue(':programKey', $_POST['updateProgramKey'], SQLITE3_TEXT);
    $stmt->bindValue(':programName', $_POST['updateProgramName'], SQLITE3_TEXT);
    $stmt->bindValue(':programID', $_POST['updateProgramID'], SQLITE3_INTEGER);
    $stmt->execute();

    // if user typed in program key that already exists, display error message
    if ($db->lastErrorCode() === 19) {
        header('Location: adminPrograms.php?error=' . urlencode('Program key already exists.'));
    } else {
        header('Location: adminPrograms.php');
    }
    exit;
}

function deleteProgram() {
    global $db;
    $stmt = $db->prepare('DELETE FROM program WHERE ProgramID = :programID');
    $stmt->bindValue(':programID', $_POST['delete'], SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: adminPrograms.php');
    exit;
}

function sortTable() {
    global $db;
    $allowed_keys = ['ProgramKey', 'ProgramName'];
    $sort = isset($_POST['sort']) && in_array($_POST['sort'], $allowed_keys) ? $_POST['sort'] : 'ProgramKey';

    $stmt = $db->prepare("SELECT * FROM program ORDER BY $sort");
    $result = $stmt->execute();
    $programs = fetchAllRows($result);
    return $programs;
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
    <title>Admin Programs Manager</title>
    <link rel="stylesheet" href="../../static/css/headerFooter.css">
    <link rel="stylesheet" href="../../static/css/adminPrograms.css">
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
            <a href="#">Courses</a>
            <a href="adminCompetency.php">Competencies</a>
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
                <a href="#">Settings</a>
                <a href="adminUsers.php">Users</a>
                <a id="logout">Log Out</a>
            </div>
        </div>
    </div>
    <div id="container">
        <div class="container-upperBox">
            <div class="programsForm">
                <?php if (isset($_GET['error'])): ?>
                    <div class="error">
                        <p><?php echo htmlspecialchars($_GET['error']); ?></p>
                    </div>
                <?php endif; ?>
                <div class="header">
                    <h2>Programs Manager</h2>
                </div>
                <div class="form-container">
                    <form method="POST">
                        <div class="inputBox">
                            <label for="programKey">Program Key:</label>
                            <input type="text" name="programKey" id="programKey" placeholder="Program Key" required>
                        </div>
                        <div class="inputBox">
                            <label for="programName">Program Name:</label>
                            <input type="text" name="programName" id="programName" placeholder="Program Name" required>
                        </div>
                        <div class="inputBox">
                            <button type="submit">Add Program</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="programsTable">
                <table>
                    <tr>
                        <th hidden="hidden">Program ID</th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="ProgramKey">Program Key</button>
                            </form>
                        </th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="ProgramName">Program Name</button>
                            </form>
                        </th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    foreach ($programs as $row) {
                        echo '<tr>';
                        echo '<td hidden>' . htmlspecialchars($row['ProgramID'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['ProgramKey'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['ProgramName'] ?? '') . '</td>';
                        echo '<td>';
                        ?>
                        <div class="actionButtons">
                            <button class="updateButton">Update</button>
                            <form method="POST">
                                <input type="hidden" name="delete" value="<?php echo htmlspecialchars($row['ProgramID'] ?? ''); ?>">
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
            <div class="programsFormUpdate">
                <div class="header">
                    <h2>Update Program</h2>
                </div>
                <div class="form-container">
                    <form method="POST">
                        <div class="inputBox" hidden="hidden">
                            <label for="updateProgramID" hidden="hidden">Program ID:</label>
                            <input type="text" name="updateProgramID" id="updateProgramID" placeholder="Program ID" required hidden="hidden">
                        </div>
                        <?php
                        // If user is Root or Admin, let them update the Program Key
                        if ($_SESSION['accountType'] === 'Root' || $_SESSION['accountType'] === 'test') {
                            echo '<div class="inputBox">';
                            echo '<label for="updateProgramKey">Program Key:</label>';
                            echo '<input type="text" name="updateProgramKey" id="updateProgramKey" placeholder="Program Key" required>';
                            echo '<p class="warning">WARNING: Changing the program key can have significant implications. Proceed with caution.</p>';
                            echo '</div>';
                        } else {
                            echo '<div class="inputBox">';
                            echo '<label for="updateProgramKey">Program Key:</label>';
                            echo '<input type="text" name="updateProgramKey" id="updateProgramKey" placeholder="Program Key" required disabled>';
                            echo '</div>';
                        }
                        ?>
                        <div class="inputBox">
                            <label for="updateProgramName">Program Name:</label>
                            <input type="text" name="updateProgramName" id="updateProgramName" placeholder="Program Name" required>
                        </div>
                        <div class="inputBox">
                            <button type="submit">Update Program</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
    <script src="../../static/js/adminPrograms.js"></script>
</body>
</html>