<?

require_once('../functions-mysql.php');

$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<deleteAllBallot>";

if(isset($_POST['Election']) && 
    $_POST['Election']!="")
{
    $Election = $_POST['Election'];

    $sql_connection->query("DELETE FROM ballots WHERE Election=$Election");
    if($sql_connection->affected_rows > 0)
    {
        echo "<status>OK</status>";
    }
    else
        echo "<status>FAILED_TO_REMOVE_BALLOTS</status>";
}
else
    echo "<status>FAILED_MISSING_ELECTION</status>";

echo "</deleteAllBallot>";

?>