<?

require_once('../functions-mysql.php');


$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<removedBallots>";

if(isset($_POST['election']) && $_POST['election']!=""){
    $election = $_POST['election'];
    
    $sql_connection->query("DELETE FROM ballots WHERE Election='$election'");
    if($sql_connection->affected_rows>0)
        echo "<status>OK</status>";
    else
        echo "<status>NO_BALLOTS_REMOVED</status>";
}
else
    echo "<status>FAILED_MISSING_NAME</status>";

echo "</removedBallots>";

?>