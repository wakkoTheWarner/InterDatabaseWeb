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

    $sender = urldecode($_GET['sender']);
    $recipient = urldecode($_GET['recipient']);

    // Modify the SQL statement to select only the messages between the sender and recipient
    $stmt = $db->prepare('SELECT * FROM messages WHERE (SenderEmail = :sender AND RecipientEmail = :recipient) OR (SenderEmail = :recipient AND RecipientEmail = :sender)');
    $stmt->bindValue(':sender', $sender, SQLITE3_TEXT);
    $stmt->bindValue(':recipient', $recipient, SQLITE3_TEXT);

    // Execute the SQL statement
    $result = $stmt->execute();

    // Fetch all rows
    $rows = fetchAllRows($result);

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
    <link rel="stylesheet" type="text/css" href="../../static/css/adminMessagingConvo.css">
    <style>
        .messageBox {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            width: 100%;
            height: 100%;
            overflow-y: auto;
            padding: 10px;
        }

        .message {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            color: black;
            display: flex;
            flex-direction: column;
        }

        .messageHeader {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .messageContent {
            padding-top: 10px;
            height: 50vh;
        }

        .messageInput {
            display: flex;
            flex-direction: column;
            padding-top: 10px;
        }

        .messageInput form {
            display: flex;
            flex-direction: column;
        }

        .messageInput textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
        }

        .messageInput button {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
            cursor: pointer;
        }

        .messageInput button:hover {
            background-color: #f1f1f1;
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
            <div class="messageBox">
                <?php
                foreach ($rows as $row) {
                    if (empty($row['Message'])) {
                        ?>
                        <div class="message">
                            <div class="messageHeader">
                                <p>No messages</p>
                            </div>
                        </div>
                        <?php
                    } else {
                    ?>
                    <div class="message">
                        <div class="messageHeader">
                            <p>From: <?php echo $row['SenderEmail']; ?></p>
                            <p>To: <?php echo $row['RecipientEmail']; ?></p>
                        </div>
                        <div class="messageContent">
                            <p><?php echo $row['Message']; ?></p>
                        </div>
                        <div class="messageInput">
                            <form method="POST">
                                <textarea name="message" placeholder="Type your message here"></textarea>
                                <button type="submit">Send</button>
                            </form>
                        </div>
                    </div>
                    <?php
                    }
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
