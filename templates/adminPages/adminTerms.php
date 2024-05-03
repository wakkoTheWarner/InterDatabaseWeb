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

    $stmt = $db->prepare('SELECT * FROM term');
    $result = $stmt->execute();
    $terms = $result->fetchArray(SQLITE3_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['updateTermID'], $_POST['updateTermKey'], $_POST['updateTermName'], $_POST['updateTermStart'], $_POST['updateTermEnd'])) {
            updateTerm();
        } elseif (isset($_POST['delete'])) {
            deleteTerm();
        } elseif (isset($_POST['termKey'], $_POST['termName'], $_POST['termStart'], $_POST['termEnd'])) {
            addTerm();
        }
    }

    $terms = []; // Initialize the $terms variable.
    if (isset($_POST['sort'])) {
        $terms = sortTable(); // Save sorted array to the $terms variable
    } else {
        $stmt = $db->prepare('SELECT * FROM term');
        $result = $stmt->execute();
        $terms = fetchAllRows($result);
    }
}

function addTerm() {
    global $db;
    $stmt = $db->prepare('INSERT INTO term (TermKey, TermName, TermStart, TermEnd) VALUES (:termKey, :termName, :termStart, :termEnd)');
    $stmt->bindValue(':termKey', $_POST['termKey'], SQLITE3_TEXT);
    $stmt->bindValue(':termName', $_POST['termName'], SQLITE3_TEXT);
    $stmt->bindValue(':termStart', $_POST['termStart'], SQLITE3_TEXT);
    $stmt->bindValue(':termEnd', $_POST['termEnd'], SQLITE3_TEXT);
    $stmt->execute();

    // if user typed in term key that already exists, display error message
    if ($db->lastErrorCode() === 19) {
        header('Location: adminTerms.php?error=' . urlencode('Term key already exists.'));
    } else {
        header('Location: adminTerms.php');
    }
    exit;
}

function updateTerm() {
    global $db;
    $stmt = $db->prepare('UPDATE term SET TermKey = :termKey, TermName = :termName, TermStart = :termStart, TermEnd = :termEnd WHERE TermID = :termID');
    $stmt->bindValue(':termKey', $_POST['updateTermKey'], SQLITE3_TEXT);
    $stmt->bindValue(':termName', $_POST['updateTermName'], SQLITE3_TEXT);
    $stmt->bindValue(':termStart', $_POST['updateTermStart'], SQLITE3_TEXT);
    $stmt->bindValue(':termEnd', $_POST['updateTermEnd'], SQLITE3_TEXT);
    $stmt->bindValue(':termID', $_POST['updateTermID'], SQLITE3_INTEGER);
    $stmt->execute();

    // if user typed in term key that already exists, display error message
    if ($db->lastErrorCode() === 19) {
        header('Location: adminTerms.php?error=' . urlencode('Term key already exists.'));
    } else {
        header('Location: adminTerms.php');
    }
    exit;
}

function deleteTerm() {
    global $db;
    $stmt = $db->prepare('DELETE FROM term WHERE TermID = :termID');
    $stmt->bindValue(':termID', $_POST['delete'], SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: adminTerms.php');
    exit;
}

function sortTable() {
    global $db;
    $allowed_keys = ['TermKey', 'TermName', 'TermStart', 'TermEnd'];
    $sort = isset($_POST['sort']) && in_array($_POST['sort'], $allowed_keys) ? $_POST['sort'] : 'TermKey';

    $stmt = $db->prepare("SELECT * FROM term ORDER BY $sort");
    $result = $stmt->execute();
    $terms = fetchAllRows($result);
    return $terms;
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
    <title>Admin Terms Manager</title>
    <link rel="stylesheet" href="../../static/css/headerFooter.css">
    <link rel="stylesheet" href="../../static/css/adminTerms.css">
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
            <a href="#">Terms</a>
            <a href="adminPrograms.php">Programs</a>
            <a href="#">Courses</a>
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
            <div class="termsForm">
                <?php if (isset($_GET['error'])): ?>
                    <div class="error">
                        <p><?php echo htmlspecialchars($_GET['error']); ?></p>
                    </div>
                <?php endif; ?>
                <div class="header">
                    <h2>Terms Manager</h2>
                </div>
                <div class="form-container">
                    <form method="POST">
                        <div class="inputBox">
                            <label for="termKey">Term Key:</label>
                            <input type="text" name="termKey" id="termKey" placeholder="Term Key" required>
                        </div>
                        <div class="inputBox">
                            <label for="termName">Term Name:</label>
                            <input type="text" name="termName" id="termName" placeholder="Term Name" required>
                        </div>
                        <div class="inputBox">
                            <label for="termStart">Term Start:</label>
                            <input type="date" name="termStart" id="termStart" required>
                        </div>
                        <div class="inputBox">
                            <label for="termEnd">Term End:</label>
                            <input type="date" name="termEnd" id="termEnd" required>
                        </div>
                        <div class="inputBox">
                            <button type="submit">Add Term</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="termsTable">
                <table>
                    <tr>
                        <th hidden="hidden">Term ID</th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="TermKey">Term Key</button>
                            </form>
                        </th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="TermName">Term Name</button>
                            </form>
                        </th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="TermStart">Term Start</button>
                            </form>
                        </th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="TermEnd">Term End</button>
                            </form>
                        </th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    foreach ($terms as $row) {
                        echo '<tr>';
                        echo '<td hidden>' . htmlspecialchars($row['TermID'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['TermKey'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['TermName'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['TermStart'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['TermEnd'] ?? '') . '</td>';
                        echo '<td>';
                    ?>
                        <div class="actionButtons">
                            <button class="updateButton">Update</button>
                            <form method="POST">
                                <input type="hidden" name="delete" value="<?php echo htmlspecialchars($row['TermID'] ?? ''); ?>">
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
            <div class="termsFormUpdate">
                <div class="header">
                    <h2>Update Term</h2>
                </div>
                <div class="form-container">
                    <form method="POST">
                        <div class="inputBox" hidden="hidden">
                            <label for="updateTermID" hidden="hidden">Term ID:</label>
                            <input type="text" name="updateTermID" id="updateTermID" placeholder="Term ID" required hidden="hidden">
                        </div>
                        <?php
                        // If user is Root or Admin, let them update the Term Key
                        if ($_SESSION['accountType'] === 'Root' || $_SESSION['accountType'] === 'test') {
                            echo '<div class="inputBox">';
                            echo '<label for="updateTermKey">Term Key:</label>';
                            echo '<input type="text" name="updateTermKey" id="updateTermKey" placeholder="Term Key" required>';
                            echo '<p class="warning">WARNING: Changing the term key can have significant implications. Proceed with caution.</p>';
                            echo '</div>';
                        } else {
                            echo '<div class="inputBox">';
                            echo '<label for="updateTermKey">Term Key:</label>';
                            echo '<input type="text" name="updateTermKey" id="updateTermKey" placeholder="Term Key" required disabled>';
                            echo '</div>';
                        }
                        ?>
                        <div class="inputBox">
                            <label for="updateTermName">Term Name:</label>
                            <input type="text" name="updateTermName" id="updateTermName" placeholder="Term Name" required>
                        </div>
                        <div class="inputBox">
                            <label for="updateTermStart">Term Start:</label>
                            <input type="date" name="updateTermStart" id="updateTermStart" required>
                        </div>
                        <div class="inputBox">
                            <label for="updateTermEnd">Term End:</label>
                            <input type="date" name="updateTermEnd" id="updateTermEnd" required>
                        </div>
                        <div class="inputBox">
                            <button type="submit">Update Term</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
    <script src="../../static/js/adminTerms.js"></script>
</body>
</html>