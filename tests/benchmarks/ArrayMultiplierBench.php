<?php

    //declare(strict_types=1);

    $ARRAY_SIZE = 250;

    //Mark start
    $start = round(microtime(true) * 1000);

    $a = array();
    $b = array();

    $temp = array();
    for ($i=0; $i<$ARRAY_SIZE; $i++) {
        array_push($temp, $i);
    }

    for ($i=0; $i<$ARRAY_SIZE; $i++) {
        array_push($a, $temp);
        array_push($b, $temp);
    }



    $r=count($a);
    $c=count($b[0]);
    $p=count($b);

    if(count($a[0]) != $p){
        echo "Incompatible matrices";
        exit(0);
    }

    $result=array();
    for ($i=0; $i < $r; $i++){
        echo ".";
        if (($i%50) == 0) {
            echo "\n";
        }
        for($j=0; $j < $c; $j++){
            $result[$i][$j] = 0;
            for($k=0; $k < $p; $k++){
                $result[$i][$j] += $a[$i][$k] * $b[$k][$j];
            }
        }
    }

    echo "\n";


    //Mark end
    $end = round(microtime(true) * 1000) - $start;

    echo $end." ms"
        ." in version: ".phpversion();

?>