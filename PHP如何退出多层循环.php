/*
 * 如何退出多层循环 ： break n; continue n; 
 * 没有n 默认退出当前循环
 * 适用于 for while switch
 */

for($i=1;$i<3;$i++){
    for($j=1;$j<3;$j++){
        echo $j."\n";
        for($k=1;$k<3;$k++){
            echo $k."\n";
            continue 2;
            //break 3;
        }   
    } 
}
