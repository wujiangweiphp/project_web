<?php

/**
  普通数组去重
  $arr = array(1,2,3,3,4,4,5,5,6,1,9,3,25,4); 
 */
 
 //方案一：array_unique 数组较大时会较慢
 $new_arr = array_unique($arr);
 
 //方案二：
 // 键值翻转即可实现 效率更快
 $new_arr = array_keys(array_flip($arr)));

 

