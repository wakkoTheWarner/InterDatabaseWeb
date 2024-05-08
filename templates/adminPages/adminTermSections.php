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
    <title>Admin Term/Courses</title>
    <link rel="stylesheet" href="../../static/css/headerFooter.css">
    <link rel="stylesheet" href="../../static/css/adminTermSections.css">
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
            <a href="#">Term/Courses</a>
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
                    <h2>Term Sections Management</h2>
                    <form id="termForm">
                        <label for="term">Term:</label>
                        <select id="term" name="term">
                            <option value="0">Select a term</option>
                            <?php
                            $result = $db->query('SELECT * FROM term');
                            $rows = fetchAllRows($result);
                            foreach ($rows as $row) {
                                echo '<option value="' . $row['TermKey'] . '">' . $row['TermName'] . '</option>';
                            }
                            ?>
                        </select>
                        <button id="termSelectorButton">Select</button>
                        <button id="resetButton">Reset</button>
                    </form>
                </div>
                <div class="sectionsTable">
                    <h2>Available Sections</h2>
                    <table>
                        <thead>
                            <tr>
                                <th hidden="hidden">Section ID</th>
                                <th>Section Key</th>
                                <th>Course Key</th>
                                <th>Professor Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Sections will be displayed here -->
                        </tbody>
                    </table>
                </div>
                <div class="addedSections">
                    <h2>Added Sections</h2>
                    <table>
                        <thead>
                            <tr>
                                <th hidden="hidden">Section ID</th>
                                <th>Section Key</th>
                                <th>Course Key</th>
                                <th>Professor Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Added sections will be displayed here -->
                        </tbody>
                    </table>
                </div>
                <div class="termSectionsTable" hidden="hidden">
                    <h2>Term Sections</h2>
                    <table>
                        <thead>
                            <tr>
                                <th hidden="hidden">Term Section ID</th>
                                <th>Term Key</th>
                                <th>Section Key</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Term sections will be displayed here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
    <script src="../../static/js/adminTermSections.js"></script>
</body>
</html>
