<?

require_once('../functions-mysql.php');

$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<allBallots>";

if(isset($_POST['Election']) && $_POST['Election']!="")
{
    $Election = $_POST['Election'];
    if($sql_connection->query("SELECT id FROM elections WHERE id=$Election")->num_rows == 1)
    {
        $gR = $sql_connection->query("SELECT 
                candidates.id, candidates.Name 
            FROM 
                running, candidates 
            WHERE 
                running.Election = $Election AND 
                running.Candidate = candidates.id");
        if($gR->num_rows > 0)
        {
            $allRunners = [];
            while($thisRunner = $gR->fetch_object())
            {
                $allRunners[$thisRunner->id] = $thisRunner->Name;
            }
            $gB = $sql_connection->query("SELECT * FROM ballots WHERE ballots.Election = $Election");
            if($gB->num_rows>0)
            {
                echo "<status>OK</status>";
                while($thisBallot = $gB->fetch_object())
                {
                    echo "<ballot id='".$thisBallot->ballotNrPerElection."'>";
                    $runnersLeft = $allRunners;
                    $gV = $sql_connection->query("SELECT 
                        votes.Rank, 
                        votes.Candidate as candidateID, 
                        candidates.Name as candidateName 
                    FROM 
                        votes, candidates 
                    WHERE 
                        Ballot=".$thisBallot->id." AND 
                        votes.Candidate = candidates.id
                    ORDER BY Rank ASC");

                    if($gV->num_rows>0)
                    {
                        echo "<voted>";
                        while($thisVote = $gV->fetch_object())
                        {
                            echo "<vote rank='".$thisVote->Rank."'><candidate id='".$thisVote->candidateID."'>".$thisVote->candidateName."</candidate></vote>";
                            unset($runnersLeft[$thisVote->candidateID]);
                        }
                        echo "</voted>";
                    }
                    if(count($runnersLeft)>0)
                    {
                        echo "<unvoted>";
                        foreach ($runnersLeft as $key => $value) {
                            echo "<vote><candidate id='".$key."'>".$value."</candidate></vote>";
                        }
                        echo "</unvoted>";
                    }
                    echo "</ballot>";
                }
            }
            else
                echo "<status>NO_BALLOTS</status>";
        }
        else
            echo "<status>NO_RUNNERS</status>";
    }
    else
        echo "<status>MISSING_ELECTION</status>";
}
else
    echo "<status>NO_ELECTION_INPUT</status>";

echo "</allBallots>";

?>