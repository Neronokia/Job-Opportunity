<?php

// get website content function, required website url
function response($url) {

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);
    curl_close($curl);

    return $response;

}

// check and clean post input data
function check_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// get websites links checking results, required database connecting settings
function checkUrls($db) {

    $update_date_time = date("Y-m-d H:i:s");
    $results = array();

    $sql = 'SELECT url, id FROM `urlContentList`';
    $res = mysqli_query($db, $sql);

    while ($row = $res->fetch_assoc()) {
        $url_content_list[$row['id']] = $row['url'];
    }

    $sql = 'SELECT id, searchUrl FROM `urlToSearchList';
    $res = mysqli_query($db, $sql);

    while ($row = $res->fetch_assoc()) {
        $url_to_search_list[$row['id']] = $row['searchUrl'];
    }

    foreach ($url_content_list as $content_url_id => $content_url) {

        unset($html);

        $html = response($content_url);

        // Update Url content last_check_date for future work.
        $sql = "UPDATE urlContentList SET last_check='" . $update_date_time . "' WHERE id = {$content_url_id} LIMIT 1";
        mysqli_query($db, $sql);

        foreach ($url_to_search_list as $url_to_search_id => $url_to_search) {

            if (preg_match_all('|<a[^<>]*href="([^<>"]+' . $url_to_search . '[^<>"]*)"[^<>]*>|Uis', $html, $m)) {

                foreach ($m[0] as $key => $link) {
                    if (preg_match('@rel=(?:"|\')?nofollow(?:"|\')?@Uis', $link, $n)) {
                        $links_for_print['nofollow']['content_urls'][] = $content_url;
                        $links_for_print['nofollow']['search_url'] = $url_to_search;
                    }
                }
            } else {
                $links_for_print['not_found']['content_urls'][] = $content_url;
                $links_for_print['not_found']['search_url'] = $url_to_search;
            }
        }
    }

    $url_to_search_list_str = implode(', ', $url_to_search_list);

    if (isset($links_for_print['not_found'])) {
        $links_for_print['not_found']['content_urls'] = array_unique($links_for_print['not_found']['content_urls']);
        $not_found_cnt = count($links_for_print['not_found']['content_urls']);

        $results['not_found'] = $links_for_print['not_found'];
        $results['not_found_cnt'] = $not_found_cnt;
    }

    if (isset($links_for_print['nofollow'])) {
        $links_for_print['nofollow']['content_urls'] = array_unique($links_for_print['nofollow']['content_urls']);
        $nofollow_cnt = count($links_for_print['nofollow']['content_urls']);

        $results['nofollow'] = $links_for_print['nofollow'];
        $results['nofollow_cnt'] = $nofollow_cnt;
    }

    if (!isset($results['not_found_cnt'])) {
        $results['not_found_cnt'] = 0;
    }

    if (!isset($results['nofollow_cnt'])) {
        $results['nofollow_cnt'] = 0;
    }

    $results['url_to_search_str'] = $url_to_search_list_str;

    foreach ($results['nofollow']['content_urls'] as $result) {
        $sql_insert = "INSERT INTO resultsReport (notFound, noFollow, searchUrl, contentUrl, check_date) VALUES (0, 1, '".$results['nofollow']['search_url']."', '{$result}', '{$update_date_time}')";
        mysqli_query($db, $sql_insert);
    }

    foreach ($results['not_found']['content_urls'] as $result) {
        $sql_insert = "INSERT INTO resultsReport (notFound, noFollow, searchUrl, contentUrl, check_date) VALUES (1, 0, '".$results['not_found']['search_url']."', '{$result}', '{$update_date_time}')";
        mysqli_query($db, $sql_insert);
    }

    return $results;
}

// add searchable url to database
function addUrlToSearch($db, $url) {

    $data = array();

    $sql = 'SELECT id, searchUrl FROM `urlToSearchList';
    $res = mysqli_query($db, $sql);

    $toSearch = $res->fetch_assoc();

    $link = check_input($url);

    if (!empty($toSearch)) {
        $sql = "UPDATE urlToSearchList SET searchUrl='" . $link . "' WHERE id = {$toSearch['id']} LIMIT 1";
        mysqli_query($db, $sql);
        $data['updated'] = 1;
    } else {
        $sql = "INSERT INTO urlToSearchList (searchUrl) VALUES ('{$link}')";
        mysqli_query($db, $sql);
        $data['inserted'] = 1;
    }

    return $data;
}

// add email to database
function addEmail($db, $email) {

    $data = array();

    $sql = 'SELECT id, email FROM `emailsList';
    $res = mysqli_query($db, $sql);

    $existingEmail = $res->fetch_assoc();

    $em = check_input($email);

    if (!empty($existingEmail)) {
        $sql = "UPDATE emailsList SET email='" . $em . "' WHERE id = {$existingEmail['id']} LIMIT 1";
        mysqli_query($db, $sql);
        $data['updated'] = 1;
    } else {
        $sql = "INSERT INTO emailsList (email) VALUES ('{$em}')";
        mysqli_query($db, $sql);
        $data['inserted'] = 1;
    }

    return $data;
}

// add content url to database
function addContentUrl($db, $url) {

    $data = array();

    $link = check_input($url);

    $sql = "INSERT INTO urlContentList (url) VALUES ('{$link}')";
    mysqli_query($db, $sql);
    $data['inserted'] = 1;

    return $data;

}

// printing template for results report to website or file
function printReport($results, $notFound_cnt, $noFollow_cnt, $date, $tags) {
    $print_data = "";

    foreach ($results as $search_URL => $results) {

        if ($tags == true) {
            echo "<p>{$search_URL} URL checked, {$notFound_cnt} backlink(s) not found, {$noFollow_cnt} backlink(s) with NOFOLLOW | check date {$date}:</p>";
            echo "<ul>";
            foreach ($results as $result) {

                if ($result['noFollow'] == true) {
                    echo "<li>- {$result['contentUrl']} - NOFOLLOW</li>";
                } else {
                    echo "<li>- {$result['contentUrl']}</li>";
                }
            }
            echo "</ul>";
        } else {

            $print_data = "{$search_URL} URL checked, {$notFound_cnt} backlink(s) not found, {$noFollow_cnt} backlink(s) with NOFOLLOW | check date {$date}:\n";
            echo "{$search_URL} URL checked, {$notFound_cnt} backlink(s) not found, {$noFollow_cnt} backlink(s) with NOFOLLOW | check date {$date}:\n";
            foreach ($results as $result) {

                if ($result['noFollow'] == true) {
                    $print_data .= "- {$result['contentUrl']} - NOFOLLOW\n";
                    echo "- {$result['contentUrl']} - NOFOLLOW\n";
                } else {
                    $print_data .= "- {$result['contentUrl']}\n";
                    echo "- {$result['contentUrl']}\n";
                }
            }
        }

    }

    return $print_data;

}