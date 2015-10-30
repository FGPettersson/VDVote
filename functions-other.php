<?
function print_html_header($inside_header = null)
{

echo "
<!doctype html>
<html>
    <head>
        <meta charset='utf-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge,chrome=1'>
        <meta name='description' content=''>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <title>V-Dala personval</title>
        <link rel='stylesheet' href='css/reset.css'>
        <link rel='stylesheet' href='css/main.css'>
        <script src='./js/jquery-1.10.2.js' type='text/javascript'></script>
        <script src='./js/someJS.js' type='text/javascript'></script>
        <script src='./js/jquery.sortable.js' type='text/javascript'></script>
";
echo (isset($inside_header)?$inside_header:"");

echo "</head><body><div id='container'>




";
flush();

}
function print_html_footer()
{

// echo "
//     </div><footer class='shaded'>
//     <a href='mailto:f.g.pettersson@gmail.com'>f.g.pettersson@gmail.com</a>
//     </footer>
    echo "<div class='clear'> </div>";
echo"    </body>
";
	
}
?>