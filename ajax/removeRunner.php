<?

require_once('../functions-mysql.php');

$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<removeRunner>";

if(isset($_POST['Election']) && isset($_POST['Candidate'])){
    $cID = $_POST['Candidate'];
    $eID = $_POST['Election'];
    
    $runnerNow = $sql_connection->query("SELECT * FROM running WHERE Election=$eID AND Candidate=$cID");
    if($runnerNow->num_rows == 1)
    {
        $sql_connection->query("DELETE FROM running WHERE Election=$eID AND Candidate=$cID");
        $RunnersRemoved = $sql_connection->affected_rows;
        if($RunnersRemoved == 1)
        {
            echo "<status>OK</status>";
            $leftRunning = $sql_connection->query("SELECT * FROM running WHERE Candidate=$cID");
            if($leftRunning->num_rows === 0)
            {
                $sql_connection->query("DELETE FROM candidates WHERE id=$cID");
                $CandidatesRemoved = $sql_connection->affected_rows;
                if($CandidatesRemoved == 1)
                {
                    echo "<candidateStatus>REMOVED</candidateStatus>";
                }
                else
                {
                    echo "<candidateStatus>FAILED_TO_REMOVE_FROM_DB</candidateStatus>";
                    echo "<cDBError>".$sql_connection->error."</cDBError>";                    
                }
            }
            else
                echo "<candidateStatus>REMAIN</candidateStatus>";
        }
        else
        {
            echo "<status>FAILED_TO_REMOVE_FROM_DB</status>";
            echo "<DBError>".$sql_connection->error."</DBError>";
        }
        echo "<RunnersRemoved>$RunnersRemoved</RunnersRemoved>";
    }
    else
        echo "<status>RUNNER_NOT_RUNNING</status>";
}
else
    echo "<status>FAILED_MISSING_CANDIDATES_OR_ELECTION</status>";

echo "</removeRunner>";

?>