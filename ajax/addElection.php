<?

require_once('../functions-mysql.php');

$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<newElection>";

if(isset($_POST['Position']) && $_POST['Position']!=""){
    $Position = $_POST['Position'];
    $Details = isset($_POST['Details'])?$_POST['Details']:"";
    $NumberOfSeats = isset($_POST['NumberOfSeats'])?$_POST['NumberOfSeats']:1;
    $MajorityNeeded = isset($_POST['MajorityNeeded'])?$_POST['MajorityNeeded']:50;

    $sql_connection->query("INSERT INTO elections (Position, Details, NumberOfSeats, MajorityNeeded) VALUES ('$Position', '$Details', '$NumberOfSeats', '$MajorityNeeded')");
    $ElectionsAdded = $sql_connection->affected_rows;
    if($ElectionsAdded == 1)
    {
        echo "<status>OK</status>";
        echo "<ElectionID>".$sql_connection->insert_id."</ElectionID>";
    }
    else
    {
        echo "<status>FAIL_TO_ADD_TO_DB</status>";
        echo "<DBError>".$sql_connection->error."</DBError>";
    }
    echo "<Position>$Position</Position>";
    echo "<NumberOfSeats>$NumberOfSeats</NumberOfSeats>";
    echo "<MajorityNeeded>$MajorityNeeded</MajorityNeeded>";
    echo "<ElectionsAdded>$ElectionsAdded</ElectionsAdded>";
}
else
    echo "<status>FAILED_MISSING_POSITION_NAME</status>";

echo "</newElection>";

?>