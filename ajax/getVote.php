<?
require_once('../functions-mysql.php');

$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<getVote>";

if(isset($_POST['Election']) && $_POST['Election']!="" && 
    isset($_POST['Candidate']) && $_POST['Candidate']!="")
{
    $Election = $_POST['Election'];
    $Candidate = $_POST['Candidate'];

    //  &&
    // isset($_POST['Iteration']) && $_POST['Iteration']!="")

    // $Iteration = $_POST['Iteration'];

    $runRes = $sql_connection->query("SELECT count(*) as antal FROM running WHERE Election=$Election");
    if($runRes->num_rows == 1)
    {
        $runCount = $runRes->fetch_object()->antal;
        $q = "SELECT num as rank, VoteCount
        FROM numbers
        LEFT JOIN votecounts
        ON num = rank
            AND election = $Election
            AND candidate = $Candidate
        WHERE num<=$runCount 
        ORDER BY num ASC";

        $voteResults = $sql_connection->query($q);

        if($voteResults->num_rows >= 1)
        {
            echo "<status>OK</status>";
            echo "<resultCount>".$voteResults->num_rows."</resultCount>";
            while($voteRes = $voteResults->fetch_object())
            {
                $voteC = is_null($voteRes->VoteCount)?0:$voteRes->VoteCount;
                echo "<result>";
                echo "<rank>".$voteRes->rank."</rank>";
                echo "<voteCount>".$voteC."</voteCount>";
                echo "</result>";
            }
        }
        else
            echo "<status>FAILED_TO_GET_VOTES</status>";
    }
    else
        echo "<status>ELECTION_NOT_EXISTS</status>";
}
else
    echo "<status>FAILED_MISSING_INPUT</status>";

echo "</getVote>";
?>
