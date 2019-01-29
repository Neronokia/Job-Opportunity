<?php

include('functions.php');
include('db_connect.php');

$notFound_cnt = 0;
$noFollow_cnt = 0;
$check_date = "";

$results = checkUrls($db);

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

if (isset($current_email['email'])) {

    $to = $current_email['email'];
    $subject = 'backlinks report';
    $message = printReport($resultsReport, $notFound_cnt, $noFollow_cnt, $check_date, false);
    $headers = 'FROM: crawler-developer@000webhostapp.com' . "\r\n" .
        'Reply-To: crawler-developer@000webhostapp.com' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    mail($to, $subject, $message, $headers);
}

exit;


