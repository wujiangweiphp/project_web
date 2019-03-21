<?php

$total_arr = array(
    array('shop_name' => '店铺名称1', 'score' => 90),
    array('shop_name' => '店铺名称2', 'score' => 89),
    array('shop_name' => '店铺名称3', 'score' => 92),
    array('shop_name' => '店铺名称4', 'score' => 93),
    array('shop_name' => '店铺名称5', 'score' => 92),
    array('shop_name' => '店铺名称6', 'score' => 91),
    array('shop_name' => '店铺名称7', 'score' => 95),
    array('shop_name' => '店铺名称8', 'score' => 88),
    array('shop_name' => '店铺名称9', 'score' => 87),
    array('shop_name' => '店铺名称10', 'score' => 86),
);

//$res = rand_arr2($total_arr, 3);
//print_r($res);



/**
 * @todo: 一维 | 二维数组 随机取 多个元素
 * @author： friker
 * @date: 2019/3/21
 * @param array $arr
 * @param int $need_num
 * @return array
 */
function rand_arr2($arr = array(), $need_num = 0)
{
    if (empty($arr) || empty($need_num)) {
        return array();
    }
    $total_num  = count($arr);
    //$keys       = rand_array_custom1($total_num, $need_num, 0);
    //$keys       = rand_array_custom2($total_num, $need_num, 0);
    $keys       = rand_array_custom3($total_num, $need_num, 0);
    $return_arr = array();
    foreach ($keys as $key) {
        $return_arr[] = $arr[$key];
    }
    return $return_arr;
}

print_r(rand_array_custom2(10,5,1));

/***
 *  ---------------------- 算法一(最优算法) -------------------------
 * @todo: 获取 $total_num 个数中 $need_num 个不重复的数值
 * @author： friker
 * @date: 2019/3/21
 * @param int $total_num 总的元素个数
 * @param int $need_num 需要的元素个数
 * @param int $start_key 起始的索引值
 * @return array
 */
function rand_array_custom($total_num = 0, $need_num = 0, $start_key = 1)
{
    if (empty($total_num) || empty($need_num)) {
        return array();
    }
    /*************************** 1. 赋值 n 个键值相同的数组 *****************************/
    $total_arr = array();
    for ($i = $start_key; $i < $total_num + $start_key; $i++) {
        $total_arr[$i] = $i;
    }
    /*************************** 2. 随机打乱这个数组 *****************************/
    /*
     *  1. 指定范围内获取一个因子
     *  2. 如果当前元素未被改变 ，（注意这个条件避免重复交换后 元素又变成原来的样子）
     *     我们交换相互元素 对应的键值
     */
    for ($i = $start_key; $i < $total_num + $start_key; $i++) {
        $rand = mt_rand($i, $total_num);
        if ($total_arr[$i] == $i) {
            $total_arr[$i]    = $total_arr[$rand];
            $total_arr[$rand] = $i;
        }
    }
    /*************************** 3. 获取随机数组的前 k 个元素 *****************************/
    $return_data = array();
    for ($i = $start_key; $i < $need_num + $start_key; $i++) {
        $return_data[] = $total_arr[$i];
    }
    return $return_data;
}

/**
 *  ---------------------- 算法二 -------------------------
 * @todo: 获取 $total_num 个数中 $need_num 个不重复的数值
 * @author： friker
 * @date: 2019/3/21
 * @param int $total_num
 * @param int $need_num
 * @param int $start_key
 * @return array
 */
function rand_array_custom2($total_num = 0, $need_num = 0, $start_key = 1)
{
    $count = 0;
    $return_arr = array();
    while ($count < $need_num) {
        $rand = mt_rand($start_key,$total_num + $start_key - 1);
        if(!isset($return_arr[$rand])) {
            $return_arr[$rand] = $rand;
        }
        $count = count($return_arr);
    }
    return array_values($return_arr);
}

/**
 * ---------------------- 算法三 -------------------------
 * @todo: 获取 $total_num 个数中 $need_num 个不重复的数值
 * @author： friker
 * @date: 2019/3/21
 * @param int $total_num
 * @param int $need_num
 * @param int $start_key
 * @return mixed
 */
function rand_array_custom3($total_num = 0, $need_num = 0, $start_key = 1)
{
    $total_arr = array();
    for ($i = $start_key; $i < $total_num + $start_key; $i++) {
        $total_arr[$i] = $i;
    }
    return array_rand($total_arr,$need_num);
}
