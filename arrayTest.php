<?
$test1 = [1, 2, 3];
$test2 = [1, 2, 3];
$test3 = [1, 2, 3, 4];
$test4 = [1, 3, 2];

sort($test1);
sort($test4);

$testing1 = $test1;
$testing2 = $test4;
if(identicalArrays($testing1, $testing2))
    echo "Arrays are identical";
else
    echo "Arrays are NOT identical";



function identicalArrays($array1, $array2)
{
    return $array1 === $array2;
}

echo array_product($test1);

echo "<br />".(6%3);
?>