<?
include("./functions-mysql.php");
include("./functions-other.php");

$sql_connection = connectToDatabase();

print_html_header("<link rel='stylesheet' href='css/calculation.css'>");
$iteration = 0;

if(isset($_GET['election']) && $_GET['election']!="")
    $election = $_GET['election'];
else
    $election = FALSE;

if(isset($_GET['display']) && $_GET['display']!="")
    $display = $_GET['display'];
else
    $display = FALSE;

// Get numbers concerning the current election
$total_votes = $sql_connection->query("SELECT count(*) as total_votes FROM votes, ballots, elections WHERE votes.Ballot = ballots.id AND ballots.Election = elections.id AND ballots.Election = $election")->fetch_object()->total_votes;
$numberOfRunners = $sql_connection->query("SELECT count(*) as numR FROM running WHERE Election = $election")->fetch_object()->numR;
$numberOfBallots = $sql_connection->query("SELECT count(*) as numB FROM ballots WHERE election = $election")->fetch_object()->numB;
$elDat = $sql_connection->query("SELECT numberOfSeats, numberOfBackups, MajorityNeeded, Position FROM elections WHERE id = $election")->fetch_object();
$numberOfSeats = $elDat->numberOfSeats;
$numberOfBackups = $elDat->numberOfBackups;
$MajorityNeeded = $elDat->MajorityNeeded*1.0000000001; // Quick and dirty trick to make sure we are ABOVE the majority limit.
$VotesToWin = ceil($numberOfBallots*($MajorityNeeded/100));


if($display == "true")
{
    print_html_header();
    echo "<header class='shaded'>
    <a href='./index.php' class='backLink'><img src='./img/users2.png'>Alla val</a>
    <a href='./election.php?election=$election' class='backLink secondLink'><img src='./img/settings48.png'>Inställningar</a>
    <a href='./vote.php?election=$election' class='backLink secondLink'><img src='./img/political5.png'>Röster</a>
    <h1>".$elDat->Position."</h1>
</header>";

    print_html_footer();
    echo "<div id='infoBox'>";
    echo "Avlagda röster: ".$numberOfBallots."<br />";
    echo "För att vinna behövs $VotesToWin röster <br />";
    echo "Antal kandidater: ".$numberOfRunners."<br />";
    echo "Antal platser: ".$numberOfSeats."<br />";
    echo "<a href='./index.php'>Tillbaka till första sidan</a>";
    echo "</div>";
    flush();
}

removeAllCalculations();
$firstNode = createFirstNode();
calculate($firstNode);
$totaltMaxDepth = $sql_connection->query("SELECT Depth FROM calcNodes WHERE Election = $election ORDER BY Depth DESC LIMIT 1")->fetch_object()->Depth;
calculateProbabilies(1);
resolveLottery($firstNode);
displayAll();

function calculate($thisNode)
{
    global $election;
    global $sql_connection;

    $thisDepth = getDepth($thisNode);

    $previouslyElected = getPreviouslyElected($thisNode);
    //print_r($previouslyElected);

    $rank1sQ = $sql_connection->query("
            SELECT candidates.id as Candidate, candidates.Name, (calcRunning.Candidate IS NOT NULL) as stillRunning, count(V.id) as sumVotes 
            FROM running, candidates 
            LEFT JOIN calcRunning
            ON candidates.id = calcRunning.Candidate
            AND calcRunning.Node = $thisNode
            LEFT JOIN calcVotes as V
            ON calcRunning.Node = V.Node
            AND calcRunning.Candidate = V.Candidate
            AND V.Rank = 1
            WHERE running.Election= $election
            AND candidates.id = running.Candidate
            GROUP BY Name
            ORDER BY sumVotes DESC");
    if($rank1sQ->num_rows>0)
    {
        $position = 1;
        $bottom = array();
        while($rank1s = $rank1sQ->fetch_object())
        {
            if($rank1s->stillRunning == 1)
            {
                if($position == 1)
                {
                    $topresult = $rank1s;
                    $bottom[0] = $rank1s;
                }
                else
                {
                    if($bottom[0]->sumVotes > $rank1s->sumVotes)
                    {
                        unset($bottom);
                        $bottom[0] = $rank1s;
                    }
                    else if($bottom[0]->sumVotes === $rank1s->sumVotes)
                    {
                        $bottom[] = $rank1s;
                    }
                }
                $position += 1;

            }
        }

        global $VotesToWin;
        if($topresult->sumVotes >= $VotesToWin)
        {
            // The top candidate gets elected
            $sql_connection->query("INSERT INTO calcElected (Node, Runner) VALUES ($thisNode, ".$topresult->Candidate.")");
            global $numberOfSeats;
            global $numberOfBackups; 
            if(count($previouslyElected) + 1 < $numberOfSeats + $numberOfBackups)
            {
                // create new node here. Original runners - the ones elected in this branch.
                $thisPN = $sql_connection->query("SELECT ProbabilityN FROM calcNodes WHERE id=$thisNode")->fetch_object()->ProbabilityN;
                $sql_connection->query("INSERT INTO calcNodes (Election, ProbabilityN, Depth) VALUES ($election, $thisPN, ".($thisDepth+1).")");
                $nnID = $sql_connection->insert_id;
                // connect the nodes
                $sql_connection->query("INSERT INTO calcRelations VALUES ($thisNode, $nnID)");

                // adding all running candidates
                $getRunners = $sql_connection->query("SELECT * FROM running WHERE Election = $election");
                if($getRunners->num_rows > 0)
                {
                    while($thisRunner = $getRunners->fetch_object())
                    {
                        if(in_array($thisRunner->Candidate, $previouslyElected) OR $thisRunner->Candidate == $topresult->Candidate)
                        {}
                        else
                        $sql_connection->query("INSERT INTO calcRunning (Node, Candidate) VALUES ($nnID, ".$thisRunner->Candidate.")");
                    }
                    $allB = $sql_connection->query("SELECT id FROM ballots WHERE election = $election");
                    if($allB->num_rows > 0)
                    {
                        while ($thisB = $allB->fetch_object()) 
                        {
                            // We get all candidates 
                            $everyV = $sql_connection->query("SELECT * FROM votes WHERE Ballot=".$thisB->id." ORDER BY Rank ASC");
                            $removed = 0;
                            while($thisV = $everyV->fetch_object())
                            {
                                if(($thisV->Candidate == $topresult->Candidate) OR in_array($thisV->Candidate, $previouslyElected))
                                {
                                    $removed += 1;
                                }
                                    $newRank = $thisV->Rank - $removed;

                                    $sql_connection->query("INSERT INTO calcVotes (Ballot, Rank, Candidate, Node) VALUES (".$thisV->Ballot.", $newRank,".$thisV->Candidate.", $nnID)");
                                    if($sql_connection->affected_rows == 0)
                                        die('Could not transfer vote');
                            }
                        }
                    }
                    else
                        {
                            echo "No ballots";
                        } // No Ballots...
                }
                else
                {
                    echo "No more candidates";
                    //no more candidates in this brunch but not done. Move to finish
                }
                if(tryToMergeNext($thisNode, $nnID))
                {}
                else
                    calculate($nnID);
            }
            else
            {
                //all seats are filled in this branch.
                $gP = $sql_connection->query("SELECT ProbabilityN, ProbabilityT FROM calcNodes WHERE id = $thisNode")->fetch_object();
                $thisPT = $gP->ProbabilityT;
                $thisPN = $gP->ProbabilityN;
                
                $sql_connection->query("INSERT INTO calcNodes (Election, ProbabilityN, ProbabilityT, Depth, EndNode) VALUES($election, $thisPN, $thisPT, ".($thisDepth+1).",1)");
                $nnID = $sql_connection->insert_id;
                $sql_connection->query("INSERT INTO calcRelations VALUES ($thisNode, ".$nnID.")");
                tryToMergeNext($thisNode, $nnID);
            }
            //echo "<br /><br />".$topresult->Name." vann!<br /><br />";
        }
        else
        {
            // Remove the bottom candidate(s)
            // If more then one has the same score create branches for each of them
            $bottomCount = count($bottom);
            for($ns = 0; $ns <= $bottomCount-1; $ns++)
            {
                $removeID = $bottom[$ns]->Candidate;
                    // We don't handle probababilities here anymore.
                    // $probs = $sql_connection->query("SELECT ProbabilityN, ProbabilityT FROM calcNodes WHERE id=$thisNode")->fetch_object();
                    // $newPN = $probs->ProbabilityN * $bottomCount;
                    // $newPT = $probs->ProbabilityT;
                // create new child node
                $sql_connection->query("INSERT INTO calcNodes (Election, Depth) VALUES ($election, ".($thisDepth+1).")");
                $nnID = $sql_connection->insert_id;
                $sql_connection->query("INSERT INTO calcRelations VALUES ($thisNode, $nnID)");

                $sql_connection->query("INSERT INTO calcRemoved (Node, Runner) VALUES ($nnID, $removeID)");

                if(calculateNextNodeVotes($removeID, $nnID))
                {
                    if(tryToMergeNext($thisNode, $nnID))
                        {}
                    else
                        calculate($nnID);
                }   
                else
                {
                    die('Could not calculate next node');
                }
            }
        }
    }
    else
        die('det finns inga röster!');
}
function calculateProbabilies($thisDepth)
{
    global $totaltMaxDepth;
    global $sql_connection;
    global $election;
    if($thisDepth<=$totaltMaxDepth)
    {
        $nodesOnDepth = $sql_connection->query("SELECT id FROM calcNodes WHERE Election=$election AND Depth=$thisDepth");
        if($nodesOnDepth->num_rows>0)
        {
            while($thisNode = $nodesOnDepth->fetch_object())
            {
                $thisID = $thisNode->id;
                $parents = $sql_connection->query("SELECT parentNode FROM calcRelations WHERE childNode='$thisID'");
                $parentPTs = array();
                $parentPNs = array();
                $parentNC = array();
                while($parent = $parents->fetch_object())
                {
                    $parentID = $parent->parentNode;
                    $probs = $sql_connection->query("SELECT ProbabilityT as PT, ProbabilityN as PN FROM calcNodes WHERE id=$parentID")->fetch_object();
                    $thisParentPT = $probs->PT;
                    $thisParentPN = $probs->PN;
                    $numChild = $sql_connection->query("SELECT count(childNode) as numChild FROM calcRelations WHERE parentNode='$parentID'")->fetch_object()->numChild;
                    $parentPTs[] = $thisParentPT;
                    $parentPNs[] = $thisParentPN*$numChild;
                }
                $newN = array_product($parentPNs);
                $newT = 0;
                for($i=0;$i<count($parentPTs);$i++)
                {
                    $newT += ($newN/$parentPNs[$i])*$parentPTs[$i];
                }
                $largestCommonDen = getGCDBetween($newN, $newT);
                $newN = $newN/$largestCommonDen;
                $newT = $newT/$largestCommonDen;
                $sql_connection->query("UPDATE calcNodes SET ProbabilityT=$newT, ProbabilityN=$newN WHERE id=$thisID");
            }
        }
        calculateProbabilies($thisDepth+1);
    }
}
function displayAll()
{
    global $election;
    global $sql_connection;
    global $numberOfRunners;
    global $numberOfSeats;
    global $numberOfBackups;
    global $firstNode;

    echo "<div id='nodeSpace'>";
    // display winner.
    echo "<div id='winnerPresentation'>";
    if(singleWinner())
    {
        $winners = getPreviouslyElected($sql_connection->query("SELECT id FROM calcNodes WHERE Election=$election AND endNode=1")->fetch_object()->id);
        $winDisplayClass='show';
    }
    else
    {
        $winners = getPreviouslyElected($sql_connection->query("SELECT id FROM calcNodes WHERE Election=$election AND endNode=1 AND ResolvedWinner=1")->fetch_object()->id);
        $winDisplayClass='dontShow';
        echo "<h1>Sannolikhet för kandidaterna</h1>";
        echo "<ul id='probababilitiesForCandidate'>";
        $runners = $sql_connection->query("SELECT Candidate, Name FROM running, candidates WHERE Election = $election AND running.Candidate = candidates.id");
        while($runner = $runners->fetch_object())
        {
            echo "<li>".$runner->Name."<span class='probEcho'>".round(100*getProbabilityForCandidateAsWinner($runner->Candidate),1)."%</span></li>";
        }
        echo "</ul>";
        echo "<button onclick='showWinners()'>Genomför lottning</button>";

    }
    echo "<h1 class='$winDisplayClass'>Valda personer</h1>";
    $idStr = array();
    foreach ($winners as $winner) {
        $idStr[] = "id = $winner";
    }
    $totStr = implode(" OR ", $idStr);

    $winNames = $sql_connection->query("SELECT * FROM candidates WHERE $totStr");
    echo "<ul class='$winDisplayClass'>";
    while($winName = $winNames->fetch_object())
    {
        echo "<li>".$winName->Name."</li>";
    }
    echo "</ul>";
    echo "</div>";
    flush();

    $allNs = $sql_connection->query("SELECT * FROM calcNodes WHERE Election = $election");
    if($allNs->num_rows>0)
    {
        while($thisNode = $allNs->fetch_object())
        {
            $rank1sQ = $sql_connection->query("
            SELECT candidates.id, candidates.Name, (calcRunning.Candidate IS NOT NULL) as stillRunning, count(V.id) as sumVotes 
            FROM running, candidates 
            LEFT JOIN calcRunning
            ON candidates.id = calcRunning.Candidate
            AND calcRunning.Node = ".$thisNode->id."
            LEFT JOIN calcVotes as V
            ON calcRunning.Node = V.Node
            AND calcRunning.Candidate = V.Candidate
            AND V.Rank = 1
            WHERE running.Election= $election
            AND candidates.id = running.Candidate
            GROUP BY Name
            ORDER BY sumVotes DESC
            ");
            $parents = array();
            if($thisNode->id == $firstNode)
                $parents[] = 0;
            else
            {
                $parentNodes = $sql_connection->query("SELECT parentNode FROM calcRelations WHERE childNode='".$thisNode->id."'");
                while($parentNode = $parentNodes->fetch_object())
                {
                    $parents[] = $parentNode->parentNode;
                }
            }
            $parentString = implode("|", $parents);

            // check for lottery winnings...
            $winStr = ($thisNode->ResolvedWinner == 1)?" lotteryWinner":"";
            $endStr = ($thisNode->EndNode==1)?" endNode":"";
            echo "<div class='node$endStr$winStr' data-nodeID='".$thisNode->id."' data-parentID='$parentString' data-depth='".getDepth($thisNode->id)."' data-suggestedHeight=''><div class='probabilityDisplay'>".round(100*$thisNode->ProbabilityT/$thisNode->ProbabilityN,1)."%</div><div class='zfix'>";
            $prevEl = getPreviouslyElected($thisNode->id);
            if(count($prevEl)>0)
            {
                echo "<ul class='elected'>";
                foreach ($prevEl as $elected) {
                    $elName = $sql_connection->query("SELECT Name FROM candidates WHERE id = $elected")->fetch_object()->Name;
                    echo "<li data-rID='$elected'>$elName</li>";
                }
                echo "</ul>";
            }
            if($rank1sQ->num_rows>0)
            {
                $listOfRunners = array();
                $listOfEliminated = array();
                while($rank1s = $rank1sQ->fetch_object())
                {
                    if(!in_array($rank1s->id, $prevEl))
                    {
                        if($rank1s->stillRunning == 1)
                                $listOfRunners[] = $rank1s;
                        else
                            $listOfEliminated[] = $rank1s;                        
                    }

                }
                if(count($listOfRunners)>0)
                {
                    echo "<ol class='stillRunning'>";
                    foreach ($listOfRunners as $runner) {
                        echo "<li data-rID='".$runner->id."'><span class='name'>".$runner->Name."</span><span class='voteCount'>".$runner->sumVotes."</span></li>";
                    }
                    echo "</ol>";
                }
                if(count($listOfEliminated)>0)
                {
                    echo "<ul class='eliminated'>";
                    foreach ($listOfEliminated as $eliminated) {
                        echo "<li data-rID='".$eliminated->id."'><span class='name'>".$eliminated->Name."</span></li>";
                    }
                    echo "</ol>";
                }
            }
                echo "</div></div>";
        }
        echo "<div class='clear'></div>";
    }

    echo "</div>"

    // ------------------------------------ //
    // --------- Javascript start --------- //
    // ------------------------------------ //

?>

<script type='text/javascript' language='javascript'>
$(document).ready(function(){
    moveNodes();
});


function moveNodes(){
    var numberOfRunners = <? echo $numberOfRunners; ?>;
    var numberOfSeats= <? echo ($numberOfSeats + $numberOfBackups); ?>;
    var maxDepth = <? echo maxDepth(); ?>;
    var nodeWidth = 250;
    var usedEndHeights = 0;
    var allNodes = $(".node");
    var rootNode;

    var maxLength = 0;
    var maxWidthDepth = 0;

    for(i = 0; i<maxDepth; i++)
    {
        var thisLength = $("[data-depth="+i+"]").length;
        if(thisLength>maxLength)
        {
            maxLength = thisLength;
            maxWidthDepth = i;
        }
    }
    allNodes.each(function(){
        var depth;
        if($(this).hasClass('endNode'))
            depth = maxDepth;
        else
            depth = $(this).attr("data-depth");

        if(depth == maxWidthDepth)
        {
            $(this).css("top", usedEndHeights.toString()+"px");
            usedEndHeights = usedEndHeights + $(this).outerHeight() + 50 + 10*numberOfSeats;
        }

        if($(this).attr("data-parentID") == 0)
            rootNode = $(this);

        var left = depth*nodeWidth;
        $(this).css("left", left.toString()+"px");

    });
    changeNodeVerticals(rootNode, maxWidthDepth, maxDepth);
    // $("[data-depth="+maxWidthDepth+"]").each(function(){
    //     changeNodeVerticals($(this));
    // });
    drawLines(rootNode);
}

function changeNodeVerticals(node, maxWidthDepth, maxDepth)
{
    var nodeHeightWithMargin = $(node).height()+65;
    var cumulativPosition = 0;
    var divider = 0;

    $("[data-depth="+maxWidthDepth+"]").each(function(){
        cumulativPosition += $(this).position().top + $(this).height();
        divider++;
    });

    var totalCenter = Math.max(400,cumulativPosition/divider-100);

    for(var i=0; i<=maxDepth; i++)
    {
        var nodesInDepth = $("[data-depth="+i+"]");
        var countNodes = nodesInDepth.length;
        var j = 0;
        $(nodesInDepth).each(function(){
            var topValue = totalCenter + nodeHeightWithMargin*(j- (countNodes/2));
            $(this).css("top", topValue.toString()+"px");            
            j++;
        });
    }
    // if($(node).hasClass("endNode") || $(node).attr("data-depth") == 0)
    // {}
    // else
    // {
    //     var thisDepth = $(node).attr("data-depth");
    //     var thisCenter = $(node).position().top + $(node).height()/2;
    //     var nodeHeightWithMargin = $(node).height() + 15;
    //     var parents = $(node).attr("data-parentID").split("|");
    //     var numberOfParents = parents.length;
    //     var children = $("[data-parentID*='"+thisID+"']");
    //     var thisID = $(node).attr("data-nodeID").toString();
        
    //     if(thisDepth == maxWidthDepth)
    //     {

    //     }
    //     else if(thisDepth<maxWidthDepth)
    //     {
    //         for(var i = 0; i<numberOfParents; i++)
    //         {
    //             $pNode = $("[data-nodeid="+parents[i]+"]");
    //             var sHeights = $pNode.attr('data-suggestedHeight').split("|");


    //         }


    //     }
    //     else
    //     {

    //     }
        
        



    //     var addedVerticals = 0;
    //     children.each(function(){
    //         addedVerticals = addedVerticals + changeNodeVerticals($(this));
    //     })
    //     var newCenter = addedVerticals/children.length;
    //     var newTop = newCenter - $(node).height()/2;
    //     $(node).css("top", newTop.toString()+"px");
    //     return newCenter;
    // }
    // else
    // {

    // }
}
function drawLines(node)
{
    if($(node).hasClass("endNode"))
        return;
    else
    {
        var thisID = $(node).attr("data-nodeID").toString();
        var thisX = $(node).position().left + $(node).width();
        var thisY = $(node).position().top + $(node).height()/2;
        var children = $("[data-parentID*='"+thisID+"']");
        children.each(function(){
            var childX = $(this).position().left;
            var childY = $(this).position().top + $(this).height()/2;
            DrawLine(thisX,thisY,childX,childY);
            drawLines($(this))
        });
    }
}
function showWinners()
{
    $(".lotteryWinner").css("box-shadow","0px 0px 5px 5px green");
    $("#winnerPresentation .dontShow").css("display","block");
}
function hideWinners()
{

}

</script>
<?
    // ------------------------------------ //
    // --------- Javascript stops --------- //
    // ------------------------------------ //

}
function createFirstNode()
{
    // returns the root node ID if successful
    // returns false if unsuccessful

    // We need to check for existing nodes or calcvotes before starting
    global $election;
    global $sql_connection;


    $getVotesQ = $sql_connection->query("SELECT votes.* FROM votes, ballots WHERE votes.ballot = ballots.id AND ballots.election = $election");
    if($getVotesQ->num_rows > 0)
    {
        // Creating the node
        $sql_connection->query("INSERT INTO calcNodes (Election, Depth, ResolvedWinner) VALUES ($election, 0, 1)");
        $newNode = $sql_connection->insert_id;
        //$sql_connection->query("INSERT INTO calcRelations VALUES (0, $newNode)");

        // adding all running candidates
        $getRunners = $sql_connection->query("SELECT * FROM running WHERE Election = $election");
        if($getRunners->num_rows > 0)
        {
            while($thisRunner = $getRunners->fetch_object())
            {
                $sql_connection->query("INSERT INTO calcRunning (Node, Candidate) VALUES ($newNode, ".$thisRunner->Candidate.")");
            }
        }

        // adding all votes unaltered to the root node
        $numberOfAddedVotes = 0;
        while($itVote = $getVotesQ->fetch_object())
        {
            $sql_connection->query("INSERT INTO calcVotes (Ballot, Rank, Candidate, Node) VALUES (".$itVote->Ballot.",".$itVote->Rank.",".$itVote->Candidate.", $newNode)");
            $numberOfAddedVotes += $sql_connection->affected_rows;
        }
        return $newNode;
    }
    else
        return false;
}
function getPreviouslyElected($node){
    global $sql_connection;
    global $firstNode;
    $allElectedInBranch = array();
    
    if($node == $firstNode)
        return $allElectedInBranch;

    $currentNode = $sql_connection->query("SELECT parentNode as pID FROM calcRelations WHERE childNode=$node")->fetch_object()->pID;
        
    while($currentNode != $firstNode)
    {
        $eID = $sql_connection->query("SELECT * FROM calcElected WHERE Node=$currentNode");
        if($eID->num_rows == 1)
        {
            $allElectedInBranch[] = (int)$eID->fetch_object()->Runner;
        }
        $currentNode = $sql_connection->query("SELECT parentNode as pID FROM calcRelations WHERE childNode=$currentNode")->fetch_object()->pID;
    }
    return array_reverse($allElectedInBranch);
}
function getDepth($node)
{
    global $sql_connection;
    global $firstNode;

    return $sql_connection->query("SELECT Depth FROM calcNodes WHERE id='$node'")->fetch_object()->Depth;

    // if($node == $firstNode)
    //     return 0;
    // else
    // {
    //     $pID = $sql_connection->query("SELECT parentNode as pID FROM calcRelations WHERE childNode=$node")->fetch_object()->pID;
    //     return 1 + getDepth($pID);
    // }
}
function maxDepth()
{
    global $sql_connection;
    global $election;
    $maxDepth = 0;
    $endNodes = $sql_connection->query("SELECT id FROM calcNodes WHERE Election=$election AND EndNode=1");
    while($endNode = $endNodes->fetch_object())
    {
        $thisDepth = getDepth($endNode->id);
        $maxDepth = max($thisDepth, $maxDepth);
    }
    return $maxDepth;
}
function removeAllCalculations()
{
    global $election;
    global $sql_connection;

    // should remove all in calcElected and calcRemoved as well ...

    $sql_connection->query("DELETE FROM calcNodes WHERE Election=$election");
    
    // THIS CONTROL DOSN'T WORK. ALWAYS RETURNS TRUE. But the removal seems to always work.
    $count = $sql_connection->query("
        SELECT 
            count(n.id) as nodeCount, 
            count(v.id) as voteCount, 
            count(r.id) as runnerCount, 
            count(e.id) as electCount, 
            count(re.parentNode) as relCount, 
            count(rem.id) as remCount
        FROM 
            calcNodes as n, 
            calcVotes as v, 
            calcRunning as r, 
            calcElected as e, 
            calcRelations as re,
            calcRemoved as rem
        WHERE 
            v.Node = n.id
        AND r.node = n.id
        AND e.node = n.id
        AND (re.parentNode = n.id OR re.childNode = n.id)
        AND rem.Node = n.id
        AND n.Election = $election
        ")->fetch_object();
    if($count->nodeCount != 0 or 
        $count->voteCount != 0 or 
        $count->runnerCount != 0 or 
        $count->electCount != 0 or 
        $count->relCount != 0 or
        $count->remCount != 0)
        return FALSE;
    else
        return TRUE;
}
function calculateNextNodeVotes($removeID, $nextNode)
{
    global $election;
    global $sql_connection;
    $parentNode = $sql_connection->query("SELECT parentNode FROM calcRelations WHERE childNode=$nextNode")->fetch_object()->parentNode;

    $allB = $sql_connection->query("SELECT id FROM ballots WHERE election = $election");
    if($allB->num_rows > 0)
    {
        while ($thisB = $allB->fetch_object()) 
        {
            // We get all candidates 
            $everyV = $sql_connection->query("SELECT * FROM calcVotes WHERE node=$parentNode AND Ballot=".$thisB->id." ORDER BY Rank ASC");
            if($everyV->num_rows == 0)
            {
                //Don't need to do anything here.
            }
            else
            {
                $found = 0;
                while($thisV = $everyV->fetch_object())
                {
                    if($thisV->Candidate == $removeID)
                    {
                        $found = 1;
                    }
                    else
                    {
                        if($found === 1)
                            $newRank = $thisV->Rank-1;
                        else
                            $newRank = $thisV->Rank;

                        $sql_connection->query("INSERT INTO calcVotes (Ballot, Rank, Candidate, Node) VALUES (".$thisV->Ballot.", $newRank,".$thisV->Candidate.", $nextNode)");
                        if($sql_connection->affected_rows == 0)
                            die('Could not transfer vote');
                    }
                }
            }
        }
        $everyR = $sql_connection->query("SELECT * FROM calcRunning WHERE node=$parentNode");
        if($everyR->num_rows>1)
        {
            while($thisR = $everyR->fetch_object())
            if($thisR->Candidate == $removeID)
            {}
            else
                $sql_connection->query("INSERT INTO calcRunning (node, Candidate) VALUES ($nextNode, ".$thisR->Candidate.")");
        }
        return true;
    }
    else
    {
        echo '<br />Finns inga röster, hur fan hamnade vi här?';
        return FALSE;
    }
}
function tryToMergeNext($parentNode, $newChildNode)
{
    global $election;
    global $sql_connection;

    $newDepth = getDepth($newChildNode);
    $nodesOnDepth = $sql_connection->query("SELECT * FROM calcNodes WHERE Election=$election AND Depth > ".($newDepth-2)." AND Depth < ".($newDepth+2));
    if($nodesOnDepth->num_rows>1)
    {
        $merged = FALSE;
        while($aNode = $nodesOnDepth->fetch_object())
        {
            if($aNode->id != $newChildNode AND areNodesIdentical($aNode->id,$newChildNode))
            {
                $probs = $sql_connection->query("SELECT 
                    n1.ProbabilityN as PN1, 
                    n1.ProbabilityT as PT1, 
                    n2.ProbabilityN as PN2, 
                    n2.ProbabilityT as PT2
                FROM
                    calcNodes as n1,
                    calcNodes as n2
                WHERE n1.id='".$aNode->id."'
                AND n2.id='".$newChildNode."'")->fetch_object();

                $newT = $probs->PT1*$probs->PN2 + $probs->PT2*$probs->PN1;
                $newN = $probs->PN1*$probs->PN2;

                $GCD = getGCDBetween($newT,$newN);
                $newN = $newN/$GCD;
                $newT = $newT/$GCD;

                $sql_connection->query("DELETE FROM calcNodes WHERE id=$newChildNode");
                $sql_connection->query("UPDATE calcNodes SET ProbabilityT = $newT, ProbabilityN = $newN WHERE id=".$aNode->id);
                $sql_connection->query("INSERT INTO calcRelations VALUES ($parentNode, ".$aNode->id.")");
                if($newDepth>$aNode->Depth)
                {
                    updateDepth($aNode->id, $aNode->Depth, $newDepth);
                }
                $merged = TRUE;
            }
        }
        if($merged)
            return TRUE;
        else
            return FALSE;
    }
    else
        return FALSE;
}

function updateDepth($node, $oldDepth, $newDepth)
{
    global $sql_connection;
    $sql_connection->query("UPDATE calcNodes SET Depth=$newDepth WHERE id='$node'");
    $diff = $newDepth-$oldDepth;
    $childs = $sql_connection->query("
        SELECT childNode, Depth FROM calcRelations 
        LEFT JOIN calcNodes
        ON calcRelations.childNode = calcNodes.id
        WHERE parentNode = $node
        ");
    if($childs->num_rows>0)
    {
        while($child = $childs->fetch_object())
        {
            updateDepth($child->childNode, $child->Depth, ($child->Depth+$diff));
        }
    }
}

function areNodesIdentical($node1, $node2)
{
    // returns TRUE if nodes should merge and FALSE if they should not

    global $sql_connection;

    // Get election and information about 
    $elInfo = $sql_connection->query("
        SELECT n1.Election as election, (n1.Election <=> n2.Election) as sameElection, RankedSeats
        FROM calcNodes as n1, calcNodes as n2, elections 
        WHERE n1.id = $node1
        AND n2.id = $node2
        AND elections.id = n1.Election");
    if($elInfo->num_rows == 1)
    {
        $eI = $elInfo->fetch_object();
        if($eI->sameElection == 1)
        {
            $election = $eI->election;
            $RankedSeats = $eI->RankedSeats;

            $electedN1 = getPreviouslyElected($node1);
            $electedN2 = getPreviouslyElected($node2);

            if($RankedSeats != 1 )
            {
                sort($electedN1);
                sort($electedN2);
            }            
            if($electedN1 == $electedN2)
            {
                $runners1 = $sql_connection->query("SELECT Candidate FROM calcRunning WHERE node = $node1 ORDER BY Candidate ASC");
                $runners2 = $sql_connection->query("SELECT Candidate FROM calcRunning WHERE node = $node2 ORDER BY Candidate ASC");
                if($runners1->num_rows == $runners2->num_rows)
                {
                    $runArray1 = array();
                    $runArray2 = array();
                    while($thisRun1 = $runners1->fetch_object())
                    {
                        $runArray1[] = (int)$thisRun1->Candidate;
                    }
                    while($thisRun2 = $runners2->fetch_object())
                    {
                        $runArray2[] = (int)$thisRun2->Candidate;
                    } 
                    return ($runArray1 == $runArray2);

                }
                return FALSE;
            }
            else
                return FALSE; //Different elected candidates
        }
        else
            return FALSE; //Nodes don't belong to same election
    }
    else
        return FALSE; //Unknown database error.
}
function getGCDBetween($a, $b)
{
    while ($b != 0)
    {
        $m = $a % $b;
        $a = $b;
        $b = $m;
    }
    return $a;
}
function singleWinner()
{
    global $sql_connection;
    global $election;
    return (1 == $sql_connection->query("SELECT count(id) as numWin FROM calcNodes WHERE Election='$election' AND endNode = 1")->fetch_object()->numWin);
}
function getProbabilityForCandidateAsWinner($Candidate)
{
    global $sql_connection;
    global $election;
    $CandTs = array();
    $CandNs = array();
    $endNodes = $sql_connection->query("SELECT * FROM calcNodes WHERE Election=$election AND endNode=1");
    while($thisEndNode = $endNodes->fetch_object())
    {
        if(in_array($Candidate, getPreviouslyElected($thisEndNode->id)))
        {
            $CandTs[] = $thisEndNode->ProbabilityT;
            $CandNs[] = $thisEndNode->ProbabilityN;
        }
    }
    $thisN = array_product($CandNs);
    $thisT = 0;
    for($i=0;$i<count($CandTs);$i++)
    {
        $thisT += ($thisN/$CandNs[$i])*$CandTs[$i];
    }
    return $thisT/$thisN;
}
function resolveLottery($node)
{
    global $sql_connection;
    $childArray = array();
    $children = $sql_connection->query("SELECT childNode FROM calcRelations WHERE parentNode = $node");
    if($children->num_rows>0)
    {
        while($thisChild = $children->fetch_object()){
            $childArray[] = $thisChild->childNode;
        }
        $choosenOne = $childArray[array_rand($childArray, 1)];
        $sql_connection->query("UPDATE calcNodes SET ResolvedWinner = 1 WHERE id=$choosenOne");
        resolveLottery($choosenOne);
    }
}
function addFractions($T1, $N1, $T2, $N2)
{
    $RT = $T1*$N2 + $T2*$N2;
    $RN = $N1*$N2;
    $GCD = getGCDBetween($RT, $RN);
    return array($RT/$GCD, $RN/$GCD);
}
?>
