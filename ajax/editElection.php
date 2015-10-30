<?

require_once('../functions-mysql.php');

$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<editElection>";

if(isset($_POST['ElectionId']) && $_POST['ElectionId']!=""){
    
    $colValStr = "id = ".$_POST['ElectionId'];
    $colValStr .= isset($_POST['Position'])?", Position = '".$_POST['Position']."'":"";
    $colValStr .= isset($_POST['Details'])?", Details = '".$_POST['Details']."'":"";
    $colValStr .= isset($_POST['NumberOfSeats'])?", NumberOfSeats = ".$_POST['NumberOfSeats']:"";
    $colValStr .= isset($_POST['NumberOfBackups'])?", NumberOfBackups = ".$_POST['NumberOfBackups']:"";
    $colValStr .= isset($_POST['RankedSeats'])?", RankedSeats = ".$_POST['RankedSeats']:"";
    $colValStr .= isset($_POST['MajorityNeeded'])?", MajorityNeeded = ".$_POST['MajorityNeeded']:"";



    $queryString = "UPDATE elections SET ".$colValStr." WHERE id = ".$_POST['ElectionId'];
    $sql_connection->query($queryString);
    if($sql_connection->errno === 0)
    {
        if($sql_connection->affected_rows == 1)
            echo "<status>OK</status>";
        else
            echo "<status>NO_CHANGE_NO_UPDATE</status>";
    }
    else
    {
        echo "<status>FAILED_TO_UPDATE_DB</status>";
        echo "<errorMessage>".$sql_connection->error."</errorMessage>";
    }
}
else
    echo "<status>FAILED_MISSING_ELECTION_ID</status>";

echo "</editElection>";

?>