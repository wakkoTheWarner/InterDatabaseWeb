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
} elseif (!isset($_POST['courseKey'])) {
    header('Location: profPage.php');
    exit;
} else {
    if ($config['db']['type'] === 'sqlite') {
        $db = new SQLite3($config['db']['sqlite']['path']);
    } elseif ($config['db']['type'] === 'mysql') {
        $dsn = "mysql:host={$config['db']['mysql']['host']};dbname={$config['db']['mysql']['dbname']}";
        $db = new PDO($dsn, $config['db']['mysql']['username'], $config['db']['mysql']['password']);
    }

    // Query for course table
    $queryCourse = "SELECT * FROM course";
    $stmtCourse = $db->prepare($queryCourse);
    $resultCourse = $stmtCourse->execute();
    $courses = fetchAllRows($resultCourse);

    // Query for competency table
    $queryCompetency = "SELECT * FROM competency";
    $stmtCompetency = $db->prepare($queryCompetency);
    $resultCompetency = $stmtCompetency->execute();
    $competencies = fetchAllRows($resultCompetency);

    // Fetch the competencyKey associated with the courseKey from the course table
    $stmt = $db->prepare('SELECT CompetencyKey FROM course WHERE CourseKey = :courseKey');
    $stmt->bindValue(':courseKey', $_POST['courseKey'], SQLITE3_TEXT);
    $result = $stmt->execute();
    $courseCompetencyKey = $result->fetchArray()[0];

    // Fetch the competency details from the competency table
    $stmt = $db->prepare('SELECT * FROM competency WHERE CompetencyKey = :competencyKey');
    $stmt->bindValue(':competencyKey', $courseCompetencyKey, SQLITE3_TEXT);
    $result = $stmt->execute();
    $competencyDetails = $result->fetchArray();

    // Get the form data
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['courseKey'], $_POST['competencyKey'])) {
            $courseKey = $_POST['courseKey'];
            $competencyKey = $_POST['competencyKey'];

            // Check if the POST data for each textarea field is set
            $competencyDescription = !empty($_POST['competencyDescription']) ? $_POST['competencyDescription'] : null;
            $competencyMetric = !empty($_POST['competencyMetric']) ? $_POST['competencyMetric'] : null;
            $metricResult = !empty($_POST['metricResult']) ? $_POST['metricResult'] : null;
            $studentStrengths = !empty($_POST['studentStrengths']) ? $_POST['studentStrengths'] : null;
            $studentWeaknesses = !empty($_POST['studentWeaknesses']) ? $_POST['studentWeaknesses'] : null;
            $recommendations = !empty($_POST['recommendations']) ? $_POST['recommendations'] : null;
            $evaluationInstrument = !empty($_POST['evaluationInstrument']) ? $_POST['evaluationInstrument'] : null;

            // Check if the competency already exists
            $stmt = $db->prepare('SELECT * FROM competency WHERE CompetencyKey = :competencyKey');
            $stmt->bindValue(':competencyKey', $competencyKey, SQLITE3_TEXT);
            $result = $stmt->execute();
            $competencyDetails = $result->fetchArray();

            if ($competencyDetails) {
                // If the competency exists, update it
                $stmt = $db->prepare('UPDATE competency SET CompetencyDesc = :competencyDescription, CompetencyMetric = :competencyMetric, MetricResult = :metricResult, StudentStrengths = :studentStrengths, StudentWeaknesses = :studentWeaknesses, Recommendations = :recommendations, EvaluationInstrument = :evaluationInstrument WHERE CompetencyKey = :competencyKey');
                $stmt->bindValue(':competencyDescription', $competencyDescription);
                $stmt->bindValue(':competencyMetric', $competencyMetric);
                $stmt->bindValue(':metricResult', $metricResult);
                $stmt->bindValue(':studentStrengths', $studentStrengths);
                $stmt->bindValue(':studentWeaknesses', $studentWeaknesses);
                $stmt->bindValue(':recommendations', $recommendations);
                $stmt->bindValue(':evaluationInstrument', $evaluationInstrument);
                $stmt->bindValue(':competencyKey', $competencyKey, SQLITE3_TEXT);
                $stmt->execute();

                $stmt = $db->prepare('SELECT * FROM competency WHERE CompetencyKey = :competencyKey');
                $stmt->bindValue(':competencyKey', $competencyKey, SQLITE3_TEXT);
                $result = $stmt->execute();
                $competencyDetails = $result->fetchArray();
            } else {
                // If the competency does not exist, create it
                $stmt = $db->prepare('INSERT INTO competency (CompetencyKey, CompetencyDesc, CompetencyMetric, MetricResult, StudentStrengths, StudentWeaknesses, Recommendations, EvaluationInstrument) VALUES (:competencyKey, :competencyDescription, :competencyMetric, :metricResult, :studentStrengths, :studentWeaknesses, :recommendations, :evaluationInstrument)');
                $stmt->bindValue(':competencyKey', $competencyKey, SQLITE3_TEXT);
                $stmt->bindValue(':competencyDescription', $competencyDescription);
                $stmt->bindValue(':competencyMetric', $competencyMetric);
                $stmt->bindValue(':metricResult', $metricResult);
                $stmt->bindValue(':studentStrengths', $studentStrengths);
                $stmt->bindValue(':studentWeaknesses', $studentWeaknesses);
                $stmt->bindValue(':recommendations', $recommendations);
                $stmt->bindValue(':evaluationInstrument', $evaluationInstrument);
                $stmt->execute();

                $stmt = $db->prepare('SELECT * FROM competency WHERE CompetencyKey = :competencyKey');
                $stmt->bindValue(':competencyKey', $competencyKey, SQLITE3_TEXT);
                $result = $stmt->execute();
                $competencyDetails = $result->fetchArray();

                // Check if the course already has a competency key
                $stmt = $db->prepare('SELECT CompetencyKey FROM course WHERE CourseKey = :courseKey');
                $stmt->bindValue(':courseKey', $courseKey, SQLITE3_TEXT);
                $result = $stmt->execute();
                $courseCompetencyKey = $result->fetchArray()[0];

                if (!$courseCompetencyKey) {
                    // If the course does not have a competency key, assign the new competency key to it
                    $stmt = $db->prepare('UPDATE course SET CompetencyKey = :competencyKey WHERE CourseKey = :courseKey');
                    $stmt->bindValue(':courseKey', $courseKey, SQLITE3_TEXT);
                    $stmt->bindValue(':competencyKey', $competencyKey, SQLITE3_TEXT);
                    $stmt->execute();
                }
            }
        }
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
    <title>Professor Course Competency</title>
    <link rel="stylesheet" href="../../static/css/headerFooter.css">
    <link rel="stylesheet" href="../../static/css/profCourseCompetency.css">
</head>
<body>
    <div id="headerNav">
        <div class="logo">
            <a href="profPage.php">
                <img src="../../static/img/inter-logo-full.png" alt="Inter CurricuLab">
            </a>
        </div>
        <nav>
            <a href="profPage.php">Dashboard</a>
        </nav>
        <div class="userBox">
            <button onclick="myFunction()" class="userDropdownButton">
                <img src="../../static/img/userProfile.jpg" alt="User" >
                <?php
                echo $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];
                ?>
            </button>
            <div id="userDropdown" class="dropdownContent">
                <a href="profProfile.php">Profile</a>
                <a id="logout">Log Out</a>
            </div>
        </div>
    </div>
    <div id="container">
        <div class="container-upperBox">
            <div class="formGrid">
                <h1>Course Competency for <?php echo $_POST['courseKey']; ?></h1>
                <form method="post">
                    <label for="courseKey">Course Key</label>
                    <input class="noselect" type="text" name="courseKey" id="courseKey" value="<?php echo $_POST['courseKey']; ?>" readonly>

                    <label for="competencyKey">Competency Key</label>
                    <?php
                    // Fetch the competencyKey associated with the courseKey from the course table
                    $stmt = $db->prepare('SELECT CompetencyKey FROM course WHERE CourseKey = :courseKey');
                    $stmt->bindValue(':courseKey', $_POST['courseKey'], SQLITE3_TEXT);
                    $result = $stmt->execute();
                    $courseCompetencyKey = $result->fetchArray()[0];

                    if ($courseCompetencyKey) {
                        // If competencyKey exists, display it in the input field
                        echo '<input class="noselect" type="text" name="competencyKey" id="competencyKey" value="' . $courseCompetencyKey . '" readonly>';
                    } else {
                        // If competencyKey does not exist, display an empty input field for the user to fill in
                        echo '<input type="text" name="competencyKey" id="competencyKey" required placeholder="Competency Key">';
                    }
                    ?>
                    <label for="competencyDescription">Competency Description</label>
                    <textarea name="competencyDescription" id="competencyDescription" placeholder="Competency Description"><?php echo $competencyDetails['CompetencyDesc'] ?? ''; ?></textarea>
                    <label for="competencyMetric">Competency Metric</label>
                    <textarea name="competencyMetric" id="competencyMetric" placeholder="Competency Metric"><?php echo $competencyDetails['CompetencyMetric'] ?? ''; ?></textarea>
                    <label for="metricResult">Metric Result</label>
                    <textarea name="metricResult" id="metricResult" placeholder="Metric Result"><?php echo $competencyDetails['MetricResult'] ?? ''; ?></textarea>
                    <label for="studentStrengths">Student Strengths</label>
                    <textarea name="studentStrengths" id="studentStrengths" placeholder="Student Strengths"><?php echo $competencyDetails['StudentStrengths'] ?? ''; ?></textarea>
                    <label for="studentWeaknesses">Student Weaknesses</label>
                    <textarea name="studentWeaknesses" id="studentWeaknesses" placeholder="Student Weaknesses"><?php echo $competencyDetails['StudentWeaknesses'] ?? ''; ?></textarea>
                    <label for="recommendations">Recommendations</label>
                    <textarea name="recommendations" id="recommendations" placeholder="Recommendations"><?php echo $competencyDetails['Recommendations'] ?? ''; ?></textarea>
                    <label for="evaluationInstrument">Evaluation Instrument</label>
                    <textarea name="evaluationInstrument" id="evaluationInstrument" placeholder="Evaluation Instrument"><?php echo $competencyDetails['EvaluationInstrument'] ?? ''; ?></textarea>
                    <button type="submit">Add</button>
                </form>
            </div>
            <div class="tableGrid">
                <?php
                // if the course has a competency key, display the competency details
                if ($courseCompetencyKey) {
                    ?>
                <table>
                    <tr>
                        <th>Course Key</th>
                        <th>Competency Key</th>
                        <th>Competency Description</th>
                        <th>Competency Metric</th>
                        <th>Metric Result</th>
                        <th>Student Strengths</th>
                        <th>Student Weaknesses</th>
                        <th>Recommendations</th>
                        <th>Evaluation Instrument</th>
                    </tr>
                    <tr>
                        <td><?php echo $_POST['courseKey']; ?></td>
                        <td><?php echo $competencyDetails['CompetencyKey'] ?? ''; ?></td>
                        <td><?php echo $competencyDetails['CompetencyDesc'] ?? ''; ?></td>
                        <td><?php echo $competencyDetails['CompetencyMetric'] ?? ''; ?></td>
                        <td><?php echo $competencyDetails['MetricResult'] ?? ''; ?></td>
                        <td><?php echo $competencyDetails['StudentStrengths'] ?? ''; ?></td>
                        <td><?php echo $competencyDetails['StudentWeaknesses'] ?? ''; ?></td>
                        <td><?php echo $competencyDetails['Recommendations'] ?? ''; ?></td>
                        <td><?php echo $competencyDetails['EvaluationInstrument'] ?? ''; ?></td>
                    </tr>
                </table>
                <?php
                } else {
                    echo '<h2>No Competency Key Assigned</h2>';
                }
                ?>
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
