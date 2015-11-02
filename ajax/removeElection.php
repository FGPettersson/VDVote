<?

require_once('../functions-mysql.php');


$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<removeElection>";

if(isset($_POST['election']) && $_POST['election']!=""){
    $election = $_POST['election'];
    

    $sql_connection->query("UPDATE elections SET Deleted=1 WHERE id=$election");
    if($sql_connection->affected_rows>0)
        echo "<status>OK</status>";
    else
        echo "<status>ELECTION_NOT_DELETED:DB_ERROR</status>";
}
else
    echo "<status>FAILED_MISSING_ELECTION_ID</status>";

echo "</removeElection>";

?>