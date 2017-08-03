<?php

/*
  curl_multi_init     //初始化多
  curl_multi_add_handle //添加curl句柄进来
  curl_multi_exec    //
  curl_multi_select //等待所有cURL批处理中的活动连接
  curl_multi_remove_handle //移除curl句柄
  curl_multi_close         //关闭多处理 
  curl_multi_getcontent // 如果设置了CURLOPT_RETURNTRANSFER，则返回获取的输出的文本流
    
  curl_multi_info_read
  curl_multi_setopt
  curl_multi_strerror
  
*/

//1.配置请求地址
$curl_configs = array(
    array('url'=>'http://www.baidu.com'),
    array('url'=>'http://news.163.com'),
    array('url'=>'http://php.net')
);
//2.加入子curl 
$ch_arr= array();
$mh = curl_multi_init();
foreach($curl_configs as $k=>$val){
	$ch_arr[$k] = curl_init();
	curl_setopt($ch_arr[$k], CURLOPT_URL, $val['url']);
	if(isset($ch_arr[$k]['configs'])){
		foreach($ch_arr[$k]['configs'] as $kconfig => $config){
		    curl_setopt($ch_arr[$k], $kconfig, $config);	
		}
	}
    curl_setopt($ch_arr[$k], CURLOPT_HEADER, 0);
	curl_multi_add_handle($mh,$ch_arr[$k]);
}
//3.执行curl
$active = null;
do {
    $mrc = curl_multi_exec($mh, $active);
} while ($mrc == CURLM_CALL_MULTI_PERFORM);

while ($active && $mrc == CURLM_OK) {
    if (curl_multi_select($mh) == -1) {
        usleep(100);
    }
    do {
        $mrc = curl_multi_exec($mh, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
}
//4.关闭子curl
foreach($ch_arr as $val){
    curl_multi_remove_handle($mh, $val);
}
//5.关闭父curl
curl_multi_close($mh);

//6.获取执行结果
foreach($ch_arr as $val){
	$response[] = curl_multi_getcontent($val);
}

var_dump($response);

?>
