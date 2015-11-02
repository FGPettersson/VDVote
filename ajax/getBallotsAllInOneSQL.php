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
                    votes.Rank as voteRank, 
                    candidates.id AS candidateID, 
                    candidates.Name as candidateName, 
                    ballots.ballotNrPerElection as ballotNr
                FROM 
                    ballots, votes, candidates 
                WHERE 
                    ballots.Election = $Election AND 
                    ballots.id = votes.Ballot AND 
                    votes.Candidate = candidates.id 
                ORDER BY 
                    ballotNrPerElection ASC, Rank ASC");
            $currentBallotNr = NULL;
            if($aV->num_rows > 0)
            {
                echo "<status>OK</status>";
                while($thisVote = $aV->fetch_object())
                {
                    if($thisVote->ballotNr != $currentBallotNr)
                    {
                        // is_null hanterar första ballot. Behöver inte avsluta någon tidigare.
                        if(!is_null($currentBallotNr))
                        {
                            if(count($runnersLeft)>0)
                            {
                                echo "</voted>";
                                echo "<unvoted>";
                                foreach ($runnersLeft as $key => $value) {
                                    echo "<vote>
                                    <rank>0</rank>
                                    <candidate id='".$key."'>".$value."</candidate>
                                    </vote>";
                                }
                                echo "</unvoted>";
                            }
                            else
                                echo "</voted>";


                            echo "</ballot>";
                        }
                            
                        echo "<ballot id='".$thisVote->ballotNr."'>";
                        echo "<voted>";
                        $currentBallotNr = $thisVote->ballotNr;
                        $runnersLeft = $allRunners;
                    }
                    echo "<vote><rank>".$thisVote->voteRank."</rank><candidate id='".$thisVote->candidateID."'>".$thisVote->candidateName."</candidate>
                    </vote>";
                    unset($runnersLeft[$thisVote->candidateID]);
                }
                
                if(count($runnersLeft)>0)
                {
                    echo "</voted>";
                    echo "<unvoted>";
                    foreach ($runnersLeft as $key => $value) {
                        echo "<vote><rank>0</rank>
                        <candidate id='".$key."'>".$value."</candidate>
                        </vote>";
                    }
                    echo "</unvoted>";
                }
                else
                    echo "</voted>";
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