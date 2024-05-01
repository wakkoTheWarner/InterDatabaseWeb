<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

    if (isset($_POST['termKey']) && isset($_POST['termName']) && isset($_POST['termStart']) && isset($_POST['termEnd'])) {
        addTerm();
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
    header('Location: adminTerms.php');
    exit;
}

function updateTerm() {
    // TODO: Implement updateTerm function
}

function deleteTerm() {
    // TODO: Implement deleteTerm function
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
            <a href="#">Programs</a>
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
                <a id="logout">Log Out</a>
            </div>
        </div>
    </div>
    <div id="container">
        <div class="container-upperBox">
            <div class="termsForm">
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
                        echo '<td><button>Update</button><button>Delete</button></td>';
                        echo '</tr>';
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('logout').addEventListener('click', function() {
            fetch('../../backend/php/logout.php')
                .then(response => response.text())
                .then(data => {
                    if(data === 'success') {
                        window.location.href = '../index.php';
                    }
                });
        });

        function myFunction() {
            document.getElementById("userDropdown").classList.toggle("show");
        }

        window.onclick = function(event) {
            if (!event.target.matches('.userDropdownButton')) {
                var dropdowns = document.getElementsByClassName("dropdownContent");
                var i;
                for (i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
    <footer>
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
</body>
</html>