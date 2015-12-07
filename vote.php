<?

require_once('./functions-mysql.php');
require_once('./functions-other.php');

$sql_connection = connectToDatabase();
print_html_header("<link rel='stylesheet' href='css/votestyle.css'>");  
$election = isset($_GET['election'])?$_GET['election']:null;
$title = "Inget val är valt";
if(isset($election))
{
    $electionRes = $sql_connection->query("SELECT * FROM elections WHERE id=$election");
    if($electionRes->num_rows == 1)
    {
        $electionData = $electionRes->fetch_object();
        $title = $electionData->Position;

        $candidatesData = $sql_connection->query("SELECT candidates.*, running.Candidate as cID FROM candidates, running WHERE running.Election=$election and running.Candidate = candidates.id");
    }
    else
    {
        $electionFail = true;
        $title = "Kunde inte hitta valt val";        
    }

}

echo "
<div id='previousBallots'>
<h2>Avlagda röster</h2>
<ul id='listOfBallots'></ul>
<a href='#' class='delAllLink' onclick='deleteAllBallots()'><img src='./img/delete96.png'>Radera alla röster</a>
</div>

    <div class='centerBox relative'>
        <div class='myList' >
            <h2>Kandidater</h2>
            <ol id='listOfRunners'>";
$i = 0;
$charList = "123456789qwertyuiopasdfghjklzxcvbnm";
while($cData = $candidatesData->fetch_object())
{
    $lID = substr($charList, $i, 1);
    echo "
                <li data-cID='".$cData->cID."' data-listID='$lID'><div class='voteNR'>$lID</div><span>".$cData->Name."</span></li>";
    $i += 1;
}


echo "
            </ol>
        </div>
        <div id='ballotBox' class='lfloat shaded'>
            <h2>Röstsedel</h2>
            <ol class='ballotVoted'>
            </ol>
            <ul class='ballotUnvoted'>
            </ul>
            <div class='ballotNr'><a class='delvote' href='#' onclick='resetVote()'></a>RÖST #<span></span><a id='voteSubmit' class='castvote' href='#' onclick='castVote()'></a></div>
        </div>
        <div class='clear'> </div>
    </div>
    <div class='clear'> </div>
<!--
    <div id='voteControl'>
        <ul>
            <li><a id='voteButton' href='#' onClick='castVote()'><img class='key' src='./img/enter-key50.png' /><img class='icon' src='./img/political5.png' />Lägg röst</a></li>
            <li><a id='resetButton' href='#' onClick='resetVote()'><div class='key'>esc</div><img class='icon' src='./img/delete96.png' />Släng röst</a></li>
            <li><a id='resultLink' href='./calculateWinners.php?election=$election&display=true'><img class='icon' src='./img/position5.png' />Se resultat</a></li>
        </ul>
    </div>
--!>
</div>
";

?>
<script type='text/javascript' language='javascript'>

var originalList = getList();
var confirmState = false;

function getList()
{
    return $('#listOfRunners').html();
}

$('#listOfRunners').on('click', 'li', function(){
    addCandidateToBallot(this);
});



function addCandidateToBallot(liElement)
{
    var candidateID = $(liElement).attr("data-cID");
    var Name = $(liElement).children("span").text();
    if($('#ballotBox .ballotVoted li[data-cID='+candidateID+']').length)
        {
            // Perhaps some more elequate error handling?
            alert('En röst har redan lagts på den här personen.');
        }
    else
    {
        $(liElement).remove();
        $('#ballotBox .ballotVoted').append("<li data-cID='" + candidateID + "'>" + Name + "</li>");
    }
    return true;
}

$('#removeBallotsLink').click(function(){
    $('#delConfirm').show();
});
$('#delConfirmNo').click(function(){
    $('#delConfirm').hide();
});
$('#delConfirmYes').click(function(){
    $.ajax(
    {
        url: './ajax/removeBallots.php',
        data: { election: <? echo $election; ?> },
        datatype: 'xml',
        type: 'POST',
        cache: false,
        async: false,
        success: function(xml)
        {
            var status = $(xml).find("status").text();
            if(status == "OK")
            {
                location.reload();                               
            }
        }
    });
    $('#delConfirm').hide();
});

// var keyMap = [];
// keyMap['key113'] = 10; // q
// keyMap['key119'] = 11; // w
// keyMap['key101'] = 12; // e
// keyMap['key114'] = 13; // r
// keyMap['key116'] = 14; // t
// keyMap['key121'] = 15; // y
// keyMap['key117'] = 16; // u
// keyMap['key105'] = 17; // i
// keyMap['key111'] = 18; // o
// keyMap['key112'] = 19; // p
// keyMap['key97'] = 20; // a
// keyMap['key115'] = 21; // s
// keyMap['key100'] = 22; // d
// keyMap['key102'] = 23; // f
// keyMap['key103'] = 24; // g
// keyMap['key104'] = 25; // h
// keyMap['key106'] = 26; // j
// keyMap['key107'] = 27; // k
// keyMap['key108'] = 28; // l
// keyMap['key122'] = 29; // z
// keyMap['key120'] = 30; // x
// keyMap['key99'] = 31; // c
// keyMap['key118'] = 32; // v
// keyMap['key98'] = 33; // b
// keyMap['key110'] = 34; // n
// keyMap['key109'] = 35; // m

$(document).keyup(function (e) {
    e.preventDefault();
    if (e.which == 27) {
        resetVote();
    }
    if (e.which == 13) {
        castVote();
    }
    if (e.which > 48 && e.which < 58) {
        var acID = e.which-48;
        if($('#listOfRunners li[data-listID='+ acID +']').length) {
            addCandidateToBallot($('#listOfRunners li[data-listID=' + acID + ']'));    
        }
    }
});

function resetVote()
{
    emptyBallot();
    resetRunnerList();
}
function confirmVote()
{

}

function voteToDB(cIDs, ballotNr)
{
    var addDone = false;
    result = null;
    $.ajax(
    {
        url: './ajax/addBallot.php',
        data: { Election: <? echo $election; ?>, ballotNr: ballotNr  },
        datatype: 'xml',
        type: 'POST',
        cache: false,
        async: false,
        success: function(xml)
        {
            var status = $(xml).find("status").text();
            if(status == "OK")
            {
                var ballotID = $(xml).find("BallotID").text();
                var j = 0;
                if(cIDs.length == 0)
                    result = true;
                else
                {
                    for (var i = 0; i < cIDs.length; i++) {
                        $.ajax({
                            url: './ajax/castVote.php',
                            data: { Ballot: ballotID, Candidate: cIDs[i], Rank: i },
                            type: 'POST',
                            cache: false,
                            async: false,
                            success: function(xml){
                                var status = $(xml).find("status").text();
                                if(status == "OK")
                                {
                                    j++;
                                }
                            }
                        });
                    }
                    if(j == cIDs.length-1)
                        result = true;
                    else
                        result = false;                    
                }
            }
        }
    });
    return result;
}
function getBallotNr()
{
    var getElection = $.post('./ajax/getNextBallotNr.php', { Election: <? echo $election ?> });
    getElection.done(function(xml){
        var status = $(xml).find("status").text();
        if(status == "OK")
        {
            var ballotNr = $(xml).find("ballotNr").text();
            $('#ballotBox .ballotNr span').text(ballotNr);
        }
        else
            alert("Kunde inte hämta röstens nummer");
    });
}

function castVote()
{
    // $('body').append("<div id='shadow'></div>" +
    //     "<div id='confirmWrap'>" +
    //     "<div id='confirmBallot'><a id='closeDiv' onClick='cancelCastingVote()'>x</a></div></div>");
    // $('#confirmBallot').append($('#ballotBox').html());
    // $('#confirmBallot .ballotUnvoted').append(.html());
    // $('#confirmBallot div.voteNR').each(function(){
    //     $(this).remove();
    // });
    // confirmState = true;

    var cIDs = new Array();
    var ballotNr = $('#ballotBox .ballotNr span').text()
    console.log("ballotNr är " + ballotNr);
    var i = 1;
    $('#ballotBox li').each(function(){
        var cID = $(this).attr("data-cID");
        cIDs[i] = cID;
        i++;
    });
    if(voteToDB(cIDs))
    {
        // $('#confirmWrap').remove();
        // $('#shadow').remove();
        // confirmState = false;
        resetVote();
        getBallotNr();
        getVotes();
        displayBallots();
        $("#voteSubmit").blur();
    }
    else
        alert("Misslyckades med att lägga till rösten!");
}
function cancelCastingVote()
{
    $('#confirmWrap').remove();
    $('#shadow').remove();
    confirmState = false;
}

function emptyBallot()
{
    $('#ballotBox .ballotVoted').empty();
}
function resetRunnerList()
{
    $('#listOfRunners').empty();
    $('#listOfRunners').append(originalList);
    return true;
}
function resetBallot()
{
    replaceList(originalList);
}
function deleteBallot(ballotNr)
{
    var delBal = $.post('./ajax/deleteBallot.php', { Election: <? echo $election ?>, BallotNr: ballotNr});
    delBal.done(function(xml){
        if($(xml).find("status").text() == "OK")
        {
            getVotes();
            $("#previousBallots>ul>li[data-ballotID='"+ ballotNr +"']").animate({top: 0, width: 0, height: 0, left: 80}, 'fast', function(){
                $(this).remove();
                addRemoveLinkToLastPreviousBallot();

            });
        }
    })
}
function deleteAllBallots()
{
    var delAllBal = $.post('./ajax/deleteAllBallots.php', {Election: <? echo $election ?>});
    delAllBal.done(function(xml){
        if($(xml).find("status").text() == "OK")
        {
            $("#previousBallots li").animate({top: 0, width: 0, height: 0, left: 80}, 'fast', function(){
                location.reload();
            });
        }
    });
}
$("#previousBallots .deleteBallotLink").on("click", function(){
    var ballotNr = parseInt($(this).parent().children("span").text());
    console.log(ballotNr);
});

function addResultBoxes()
{
    $('#listOfRunners li').each(function(){
        var cID = $(this).attr("data-cID");
        $('#resultContainer ul').append("<li class='candidateListElement' data-cID='" + cID + "'><div><h2>" + $(this).find("span").html() + "</h2><ol></ol></div></li>");
    });
}
function resizeResultBoxes()
{
    var minHeight = 150;
    var rowHeight = 58 + 16 * ($('#resultContainer li.candidateListElement').length - 1);
    rowHeight = Math.max(rowHeight, minHeight)
    var minLiWidth = 95;
    var maxLiWidth = 180;
    var liWidth = 90; // initializing with value belove min value.
    var rows = 0;
    var ulWidth = $('#resultContainer ul').width();
    var liCount = $('#resultContainer li.candidateListElement').length;
    while(liWidth<minLiWidth)
    {
        rows++;
        
        liWidth = ulWidth/(Math.ceil(liCount/rows))-5;
    }
    liWidth = Math.min(liWidth, maxLiWidth);
    $('#resultContainer').css("height", rows*rowHeight);
    $('#container').css("margin-bottom", rows*rowHeight+20);

    $('.candidateListElement').css("height", Math.round(100/rows)+"%");
    $('#resultContainer li.candidateListElement').css("width", liWidth);
}
// function moveVoteButtons()
// {
//     var numberOfRunners = $('#listOfRunners li').length;
//     var topOffset = 131 + 26 * numberOfRunners + 15;
//     var buttonBox = $('#voteButtonBox');
//     var resultButton = $('#resultLink');
//     var buttonHeight = parseInt($(buttonBox).css('height').match(/\d+/g));
//     var resHeight = parseInt($(resultButton).css('height').match(/\d+/g));

//     if(topOffset<300)
//     {
//         console.log('Försöker flytta knapparna till '+ topOffset);
//         $(buttonBox).css('top',topOffset);
//         var resTopOffset = topOffset + (buttonHeight - resHeight);
//         $(resultButton).css('top', resTopOffset);
//     }
//     else
//     {
//         var resTopOffset = (35 + buttonHeight);
//         console.log(resTopOffset);
//         $(buttonBox).css('top',5).css('right',-270);
//         $(resultButton).css('top', resTopOffset).css('left', 590);
//     }
// }
function getVotes()
{
    $('.updated').removeClass('updated');
    $('#resultContainer li.candidateListElement').each(function(){
        var cID = $(this).attr("data-cID");
        var eID = <? echo $election; ?>;
        
        var displayLIs = $(this).find("ol li");
        var displayCount = displayLIs.length;

        var allCounts = $.post('./ajax/getVote.php', { Election: eID, Candidate: cID });
        allCounts.done(function(xml){
            var status = $(xml).find("status").text();
            if(status == "OK")
            {
                var resultCount = $(xml).find("result").length;
                if(displayCount == resultCount)
                {
                    $(xml).find("result").each(function(){
                        var rank = $(this).find("rank").text();
                        var voteC = $(this).find("voteCount").text();
                        var liNum = rank-1;
                        var liText = $('#resultContainer li[data-cID=' + cID + '] ol li:eq(' + liNum + ')').text();
                        if(liText != dashForZero(voteC))
                        {
                            $('#resultContainer li[data-cID=' + cID + '] ol li:eq(' + liNum + ')').text(dashForZero(voteC));
                            $('#resultContainer li[data-cID=' + cID + '] ol li:eq(' + liNum + ')').addClass("updated");
                        }
                    });
                }
                else
                {
                    $('#resultContainer li[data-cID=' + cID + '] ol').empty();
                    var i = 1;
                    $(xml).find("result").each(function(){
                        var rank = $(this).find("rank").text();
                        if(rank == i)
                        {
                            var voteC = $(this).find("voteCount").text();
                            $('#resultContainer li[data-cID=' + cID + '] ol').append("<li>" + dashForZero(voteC) + "</li>");
                        }
                        else
                            alert("Något har blivit allvarligt fel när röstningsresultaten hämtades!");
                        i++;
                    });
                }
            }
        });
    });
}
function displayBallots()
{
    var allBallots = $.post('./ajax/getBallots.php', { Election: <? echo $election ?> });
    allBallots.done(function(xml){
        var delayTime = 100;
        var firstLoad = true;
        var areaHeight = $("#previousBallots>ul").height()
        var lastAdded = 0;
        var addCount = 0;
        if($("#previousBallots>ul>li").length>0)
            firstLoad = false;

        var numberOfBallotsInXML = $(xml).find("ballot").length;

        $(xml).find("ballot").each(function(){
            if($("li[data-ballotID = " + $(this).attr('id') + "]").length == 0)
            {
                var ballotXML = $(this);
                var newLi = createNewBallotElement(ballotXML);

                $(newLi).appendTo("#previousBallots>ul");
                    $("#previousBallots>ul>li[data-ballotID='" + $(newLi).attr('data-ballotID') + "']").each(function(){
                        var newTopPos = areaHeight - $(this).height() - $(this).attr('data-ballotID') * 2 - 20;
                        var newLeftPos = randomIntFromInterval(0,10);
                        var newRotate = "rotate(" + randomIntFromInterval(-2,2) + "deg)";
                        var currentPosition = $(this).position();

                        var newCss = {'top':newTopPos, 'left':newLeftPos, '-webkit-transform':newRotate, '-moz-transform':newRotate, '-ms-transform':newRotate};
                        if(currentPosition.top != newTopPos)
                        {
                            if(firstLoad)
                                $(this).hide(0).delay(150).show(0).css(newCss);
                            else
                                $(this).hide(0).addClass('newColor').css(newCss).show();
                                setTimeout(function(){ 
                                    $(newLi).removeClass('newColor');
                                }, 00);
                        }

                    });
            }

        });
        if(firstLoad)
        var lastDelay = 0;
        $("#previousBallots>ul>li[data-ballotID='"+lastAdded+"']").hide(0).delay(lastDelay).show(0);
        addRemoveLinkToLastPreviousBallot();
    });
}
function addRemoveLinkToLastPreviousBallot()
{
    $("#previousBallots>ul .ballotNr a").remove();
    $("#previousBallots>ul>li:last-of-type").each(function(){
        var ballotNR = $(this).attr('data-ballotID');
        $(this).find(".ballotNr").append("<a href='#' class='delvote' onclick='deleteBallot("+ ballotNR +")'></a>");
    })
}
function createNewBallotElement(ballotXML)
{
    var thisBallot = $(ballotXML);
    // var addStr = "<li data-ballotID='" + $(thisBallot).attr('id') + "'>";
    var newLi = document.createElement("li");
    $(newLi).attr('data-ballotID', $(thisBallot).attr('id'));
    $(newLi).addClass('newColor');

    var addStr = "<div class='singleDisplayedBallot'>\n";
    if($(thisBallot).find("voted").length>0)
    {
        addStr += "<ol class='ballotVoted'>\n";

        $(thisBallot).find("voted").each(function(){
            var voteList = $(this);
            $(voteList).find("vote").each(function(){
                var cID = $(this).find("candidate").attr("id");
                var cName = $(this).find("candidate").text();
                addStr += "<li data-cID=" + cID + ">" + cName + "</li>\n";
            });
        });
        addStr += "</ol>\n";
    }
    else
        $(newLi).addClass('delColor');
    if($(thisBallot).find("unvoted").length>0)
    {
        addStr += "<ul class='ballotUnvoted'>\n";

        $(thisBallot).find("unvoted").each(function(){
            var unvoteList = $(this);
            $(unvoteList).find("vote").each(function(){
                var cID = $(this).find("candidate").attr("id");
                var cName = $(this).find("candidate").text();
                addStr += "<li data-cID=" + cID + ">" + cName + "</li>\n";
            });
        });
        addStr += "</ul>\n";
    }

    addStr += "<div class='ballotNr'>RÖST #<span>" + $(thisBallot).attr('id') + "</span>\n</div>\n</li>\n";
    $(newLi).append(addStr);
    return newLi;
}
function randomIntFromInterval(min,max)
{
    return Math.floor(Math.random()*(max-min+1)+min);
}
function dashForZero(num)
{
    if(num == 0)
        return "-";
    else
        return num;
}
$(document).ready(function(){
    getBallotNr();
    addResultBoxes();
    resizeResultBoxes();
    getVotes();
    displayBallots();
    $('#voteControl').slideDown("slow");
});
$(window).resize(function(){
    resizeResultBoxes();
});

</script>
<?

echo "</div>
<div id='resultContainer'><ul></ul></div>
</body>
</html>
";
?>