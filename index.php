<?php
include('functions.php');
include('db_connect.php');

$today = date("Y-m-d");
$notFound_cnt = 0;
$noFollow_cnt = 0;
$check_date = "";

$sql = "SELECT notFound, noFollow, searchUrl, contentUrl, check_date FROM resultsReport WHERE check_date < DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
$res = mysqli_query($db, $sql);

while ($row = $res->fetch_assoc()) {
    $resultsReport[$row['searchUrl']][] = $row;
    $notFound_cnt += $row['notFound'];
    $noFollow_cnt += $row['noFollow'];
    $check_date = date("Y-m-d", strtotime($row['check_date']));
}

$sql = "SELECT email FROM emailsList";
$res = mysqli_query($db, $sql);

$current_email = $res->fetch_assoc();



if ($_SERVER["REQUEST_METHOD"] == "POST") {


    if (isset($_POST['urlType'])) {
        if ($_POST['urlType'] == 'fromContent') {
            $data = addContentUrl($db, $_POST['urlContent']);

            if ($data['inserted'] == 1) {
                echo "\n Link added to Database successfully.";
            }
        }

        if ($_POST['urlType'] == 'toSearch') {
            $data = addUrlToSearch($db, $_POST['urlToSearch']);

            if ($data['inserted'] == 1) {
                echo "\n Link added to Database successfully.";
            }

            if ($data['updated'] == 1) {
                echo "\n Link updated in Database successfully.";
            }
        }

        if ($_POST['urlType'] == 'email') {
            $data = addEmail($db, $_POST['email']);
        }


    }

    if (isset($_POST['download'])) {
        if (isset($_POST['download_to_search_urls_list'])) {
            $sql = "SELECT searchUrl FROM urlToSearchList";
            $res = mysqli_query($db, $sql);
        }

        if (isset($_POST['download_urls_content_list']) && $_POST['download_urls_content_list'] == 'Download Content Urls list') {
            $sql = "SELECT url FROM urlContentList";
            $res = mysqli_query($db, $sql);

            while ($row = $res->fetch_assoc()) {
                $urls_list[] = $row['url'];
            }

            $urls_list_str = implode("\n", $urls_list);

            header('Content-disposition: attachment; filename=Urls_content_list.txt');
            print_r($urls_list_str);
            exit;

        }

        if (isset($_POST['download_report']) && $_POST['download_report'] == 'Download Today\'s Report') {

            header('Content-disposition: attachment; filename=Report.txt');
            printReport($resultsReport, $notFound_cnt, $noFollow_cnt, $check_date, false);
            exit;

        }

        if (isset($_POST['check_websites']) && $_POST['check_websites'] == 'Refresh backlinks Report') {

            $results = checkUrls($db);
            header("Location: index.php");

        }

        if (isset($_POST['delete_results']) && $_POST['delete_results'] == 'Delete backlinks Report') {
            $sql = "DELETE FROM resultsReport";
            mysqli_query($db, $sql);

            header("Location: index.php");
        }
    }
}
$db->close();
?>

<html>

    <body>
        <form action="index.php" method="post">
            <input type="hidden" name="urlType" value="fromContent">
            Add URL whose content should be checked:<br>
            <input type="text" name="urlContent" required><br>
            <input type="submit" value="Add">
        </form>

        <form action="index.php" method="post">
            <input type="hidden" name="urlType" value="toSearch">
            Add URL which should be founded:<br>
            <input type="text" name="urlToSearch" required><br>
            <input type="submit" value="Add">
        </form>

        <form action="index.php" method="post">
            <input type="hidden" name="urlType" value="email">
            To send Daily backlinks Report, please add Email address:
            <?php
            if (isset($current_email['email'])) {
                echo "<b>Current Email - {$current_email['email']}</b>";
            }
            ?>
            <br>
            <input type="email" name="email" required><br>
            <input type="submit" value="Add">
        </form>

        <hr>

        <?php

        if (isset($resultsReport)) {
            printReport($resultsReport, $notFound_cnt, $noFollow_cnt, $check_date, true);
        } else {
            echo "\nNo results found, please <b>Refresh backlinks Report</b>\n\n";
        }

        ?>
        <hr>

        <form action="index.php" method="post">
            <input type="hidden" name="download" value="yes">
            <input type="submit" name="download_urls_content_list" value="Download Content Urls list">
            <?php
            if (isset($resultsReport)) {
                echo '<input type = "submit" name = "download_report" value = "Download Today\'s Report" >';
            }
            ?>
            <input type="submit" name="check_websites" value="Refresh backlinks Report">
            <input type="submit" name="delete_results" value="Delete backlinks Report">
            <button onclick="location.href='deleteUrlContent.php'" type="button">Modify Content URLs</button>
        </form>
    </body>
</html>