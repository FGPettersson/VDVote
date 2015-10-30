<?

require_once('../functions-mysql.php');

$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<newCandidate>";

if(isset($_POST['Name']) && $_POST['Name']!=""){
    $Name = $_POST['Name'];
    
    $sql_connection->query("INSERT INTO candidates (Name) VALUES ('$Name')");
    $CandidatesAdded = $sql_connection->affected_rows;
    if($CandidatesAdded == 1)
    {
        echo "<status>OK</status>";
        echo "<CandidateID>".$sql_connection->insert_id."</CandidateID>";
    }
    else
    {
        echo "<status>FAIL_TO_ADD_TO_DB</status>";
        echo "<DBError>".$sql_connection->error."</DBError>";
    }
    echo "<Name>$Name</Name>";
    echo "<CandidatesAdded>$CandidatesAdded</CandidatesAdded>";
}
else
    echo "<status>FAILED_MISSING_NAME</status>";

echo "</newCandidate>";

?>