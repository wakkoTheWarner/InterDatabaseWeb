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

    $stmt = $db->prepare('SELECT * FROM competency');
    $result = $stmt->execute();
    $competencies = $result->fetchArray(SQLITE3_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['updateCompetencyID'], $_POST['updateCompetencyKey'], $_POST['updateCompetencyDesc'])) {
            updateCompetency();
        } elseif (isset($_POST['delete'])) {
            deleteCompetency();
        } elseif (isset($_POST['competencyKey'], $_POST['competencyDesc'])) {
            addCompetency();
        }
    }

    $competencies = []; // Initialize the $competencies variable.
    if (isset($_POST['sort'])) {
        $competencies = sortTable(); // Save sorted array to the $competencies variable
    } else {
        $stmt = $db->prepare('SELECT * FROM competency');
        $result = $stmt->execute();
        $competencies = fetchAllRows($result);
    }
}

function addCompetency() {
    global $db;
    $stmt = $db->prepare('INSERT INTO competency (CompetencyKey, CompetencyDesc) VALUES (:competencyKey, :competencyDesc)');
    $stmt->bindValue(':competencyKey', $_POST['competencyKey'], SQLITE3_TEXT);
    $stmt->bindValue(':competencyDesc', $_POST['competencyDesc'], SQLITE3_TEXT);
    $stmt->execute();

    // if user typed in competency key that already exists, display error message
    if ($db->lastErrorCode() === 19) {
        header('Location: adminCompetency.php?error=' . urlencode('Competency key already exists.'));
    } else {
        header('Location: adminCompetency.php');
    }
    exit;
}

function updateCompetency() {
    global $db;
    $stmt = $db->prepare('UPDATE competency SET CompetencyKey = :competencyKey, CompetencyDesc = :competencyDesc WHERE CompetencyID = :competencyID');
    $stmt->bindValue(':competencyKey', $_POST['updateCompetencyKey'], SQLITE3_TEXT);
    $stmt->bindValue(':competencyDesc', $_POST['updateCompetencyDesc'], SQLITE3_TEXT);
    $stmt->bindValue(':competencyID', $_POST['updateCompetencyID'], SQLITE3_INTEGER);
    $stmt->execute();

    // if user typed in competency key that already exists, display error message
    if ($db->lastErrorCode() === 19) {
        header('Location: adminCompetency.php?error=' . urlencode('Competency key already exists.'));
    } else {
        header('Location: adminCompetency.php');
    }
    exit;
}

function deleteCompetency() {
    global $db;
    $stmt = $db->prepare('DELETE FROM competency WHERE CompetencyID = :competencyID');
    $stmt->bindValue(':competencyID', $_POST['delete'], SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: adminCompetency.php');
    exit;
}

function sortTable() {
    global $db;
    $allowed_keys = ['CompetencyKey', 'CompetencyDesc'];
    $sort = isset($_POST['sort']) && in_array($_POST['sort'], $allowed_keys) ? $_POST['sort'] : 'CompetencyKey';

    $stmt = $db->prepare("SELECT * FROM competency ORDER BY $sort");
    $result = $stmt->execute();
    $competencies = fetchAllRows($result);
    return $competencies;
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
    <title>Admin Competencies Manager</title>
    <link rel="stylesheet" href="../../static/css/headerFooter.css">
    <link rel="stylesheet" href="../../static/css/adminCompetency.css">
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
            <a href="#">Courses</a>
            <a href="#">Competencies</a>
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
            <div class="competenciesForm">
                <?php if (isset($_GET['error'])): ?>
                    <div class="error">
                        <p><?php echo htmlspecialchars($_GET['error']); ?></p>
                    </div>
                <?php endif; ?>
                <div class="header">
                    <h2>Competencies Manager</h2>
                </div>
                <div class="form-container">
                    <form method="POST">
                        <div class="inputBox">
                            <label for="competencyKey">Competency Key:</label>
                            <input type="text" name="competencyKey" id="competencyKey" placeholder="Competency Key" required>
                        </div>
                        <div class="inputBox">
                            <label for="competencyDesc">Competency Description:</label>
                            <!-- TextArea for Competency Description -->
                            <textarea name="competencyDesc" id="competencyDesc" placeholder="Competency Description" required></textarea>
                        </div>
                        <div class="inputBox">
                            <button type="submit">Add Competency</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="competenciesTable">
                <table>
                    <tr>
                        <th hidden="hidden">Competency ID</th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="CompetencyKey">Competency Key</button>
                            </form>
                        </th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="CompetencyDesc">Competency Description</button>
                            </form>
                        </th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    foreach ($competencies as $row) {
                        echo '<tr>';
                        echo '<td hidden>' . htmlspecialchars($row['CompetencyID'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['CompetencyKey'] ?? '') . '</td>';
                        echo '<td style="text-align: left;">' . htmlspecialchars($row['CompetencyDesc'] ?? '') . '</td>';
                        echo '<td>';
                        ?>
                        <div class="actionButtons">
                            <button class="updateButton">Update</button>
                            <form method="POST">
                                <input type="hidden" name="delete" value="<?php echo htmlspecialchars($row['CompetencyID'] ?? ''); ?>">
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
            <div class="competenciesFormUpdate">
                <div class="header">
                    <h2>Update Competency</h2>
                </div>
                <div class="form-container">
                    <form method="POST">
                        <div class="inputBox" hidden="hidden">
                            <label for="updateCompetencyID" hidden="hidden">Competency ID:</label>
                            <input type="text" name="updateCompetencyID" id="updateCompetencyID" placeholder="Competency ID" required hidden="hidden">
                        </div>
                        <?php
                        // If user is Root or Admin, let them update the Competency Key
                        if ($_SESSION['accountType'] === 'Root' || $_SESSION['accountType'] === 'Admin') {
                            echo '<div class="inputBox">';
                            echo '<label for="updateCompetencyKey">Competency Key:</label>';
                            echo '<input type="text" name="updateCompetencyKey" id="updateCompetencyKey" placeholder="Competency Key" required>';
                            echo '<p class="warning">WARNING: Changing the competency key can have significant implications. Proceed with caution.</p>';
                            echo '</div>';
                        } else {
                            echo '<div class="inputBox">';
                            echo '<label for="updateCompetencyKey">Competency Key:</label>';
                            echo '<input type="text" name="updateCompetencyKey" id="updateCompetencyKey" placeholder="Competency Key" required disabled>';
                            echo '</div>';
                        }
                        ?>
                        <div class="inputBox">
                            <label for="updateCompetencyDesc">Competency Name:</label>
                            <textarea name="updateCompetencyDesc" id="updateCompetencyDesc" placeholder="Competency Description" required></textarea>
                        </div>
                        <div class="inputBox">
                            <button type="submit">Update Competency</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
    <script src="../../static/js/adminCompetency.js"></script>
</body>
</html>