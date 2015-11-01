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
            $aV = $sql_connection->query("SELECT 
                    ballots.Election as electionID, 
                    ballots.id as ballotID, 
                    votes.Rank as voteRank, 
                    candidates.id AS candidateID, 
                    candidates.Name as candidateName 
                FROM 
                    ballots, votes, candidates 
                WHERE 
                    ballots.Election = $Election AND 
                    ballots.id = votes.Ballot AND 
                    votes.Candidate = candidates.id 
                ORDER BY 
                    ballotNrPerElection ASC, Rank ASC");
            $currentBallotID = NULL;
            if($aV->num_rows > 0)
            {
                while($thisVote = $aV->fetch_object())
                {
                    if($thisVote->ballotID != $currentBallotID)
                    {
                        // Hanterar första ballot. Behöver inte avsluta någon tidigare.
                        if(!is_null($currentBallotID))
                        {
                            foreach ($runnersLeft as $key => $value) {
                                echo "<vote rank=0>
                                <candidate id=".$key.">".$value."</candidate>
                                </vote>";
                            }
                            echo "</ballot>";
                        }
                            
                        echo "<ballot id=".$thisVote->ballotID.">";
                        $currentBallotID = $thisVote->ballotID;
                        $runnersLeft = $allRunners;
                    }
                    echo "<vote rank=".$thisVote->voteRank.">
                    <candidate id=".$thisVote->candidateID.">
                    ".$thisVote->candidateName."
                    </candidate>
                    </vote>";
                    unset($runnersLeft[$thisVote->candidateID]);
                }
                foreach ($runnersLeft as $key => $value) {
                    echo "<vote rank=0>
                    <candidate id=".$key.">".$value."</candidate>
                    </vote>";
                }
                echo "</ballot>";
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