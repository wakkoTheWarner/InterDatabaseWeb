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

    $stmt = $db->prepare('SELECT * FROM messages WHERE SenderEmail = :email OR RecipientEmail = :email');
    $stmt->bindValue(':email', $_SESSION['email'], SQLITE3_TEXT);
    $result = $stmt->execute();
    $users = $result->fetchArray(SQLITE3_ASSOC);
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
    <title>Admin Messages</title>
    <link rel="stylesheet" type="text/css" href="../../static/css/headerFooter.css">
    <link rel="stylesheet" type="text/css" href="../../static/css/adminMessagingDefault.css">
    <style>
        .messageBox {
            display: flex;
            flex-direction: column;
            justify-content: left;
            margin-top: 20px;
            width: 35%;
            flex-grow: 1;
        }

            .message {
                background-color: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 10px;
                text-decoration: none;
                color: black;
            }

            .message:hover {
                background-color: #f1f1f1;
            }

            .messageHeader {
                display: flex;
                flex-direction: column;
                justify-content: left;
                border-bottom: 1px solid #ddd;
                padding-bottom: 10px;
            }

            .messageContent {
                display: flex;
                justify-content: left;
                padding-top: 10px;
            }
    </style>
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
                <a href="adminLogger.php">Logger</a>
                <a href="adminUsers.php">Users</a>
                <a href="adminMessagingDefault.php">Message</a>
                <a id="logout">Log Out</a>
            </div>
        </div>
    </div>
    <div id="container">
        <div class="container-upperBox">
            <div class="header">
                <h1>Messages</h1>
            </div>
            <!-- Show clickable boxes of all active messages to current session user both as sender and receiver -->
            <div class="messageBox">
                <?php
                // Prepare the SQL statement
                $stmt = $db->prepare('SELECT * FROM messages WHERE SenderEmail = :email OR RecipientEmail = :email GROUP BY SenderEmail, RecipientEmail');
                $stmt->bindValue(':email', $_SESSION['email'], SQLITE3_TEXT);

                // Execute the SQL statement
                $result = $stmt->execute();

                // Fetch all rows
                $rows = fetchAllRows($result);

                // Loop through each row
                // Loop through each row
                foreach ($rows as $row) {
                    // Create a box with the conversation details
                    echo '<a href="adminMessagingConvo.php?sender=' . urlencode($row['SenderEmail']) . '&recipient=' . urlencode($row['RecipientEmail']) . '" class="message">';
                    echo '<div class="messageHeader">';
                    echo '<p hidden="hidden">' . $row['MessageID'] . '</p>';
                    echo '<p>From: ' . $row['SenderEmail'] . '</p>';
                    echo '<p>To: ' . $row['RecipientEmail'] . '</p>';
                    echo '<p>Date: ' . $row['Timestamp'] . '</p>';
                    echo '</div>';
                    echo '<div class="messageContent">';
                    echo '<p>' . $row['Message'] . '</p>';
                    echo '</div>';
                    echo '</a>';
                    echo '<br>';
                }
                ?>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
    <script src="../../static/js/adminMessaging.js"></script>
</body>
</html>
