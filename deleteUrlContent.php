<?php
include('db_connect.php');


$sql = 'SELECT * FROM `urlContentList`';
$res = mysqli_query($db, $sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['id'])) {
        $ids_str = implode(',', $_POST['id']);

        $sql = "DELETE FROM urlContentList WHERE id IN ($ids_str)";
        mysqli_query($db, $sql);

        header("Location: deleteUrlContent.php");
    }
}

?>
<html>
<meta http-equiv=\"Content-Type\" content=\"text/html;charset=UTF-8\">
<hr>
    <body>
        <form action="deleteUrlContent.php" method="post">
            <table style="border: groove">
                <tr>
                    <th></th>
                    <th>URL</th>
                    <th>Last check date</th>
                </tr>
                <?php
                while($row = $res->fetch_assoc()) {

                    if (preg_match('|0000-00-00|Uis', $row['last_check'])) {
                        $row['last_check'] = '-';
                    }
                    echo "<tr><td><input type='checkbox' name=\"id[]\" value=\"{$row['id']}\"></td><td>{$row['url']}</td><td>{$row['last_check']}</td></tr>";
                }
                ?>

            </table>
            <br>
            <input type="submit" value="Delete selected">
        </form>

        <button onclick="location.href='index.php'" type="button">Back To Main</button>
    </body>
</html>
<?php
$db->close();
?>