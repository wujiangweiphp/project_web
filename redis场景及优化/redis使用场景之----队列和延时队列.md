# redis应用场景之------- 队列和延时队列

### 1. 简单的队列使用

```
lpush name zhangsan lisi wangwu zhaoliu
rpop name
```

### 2. 阻塞推送

上面的队列有个问题，就是当所有任务被pop完之后，就会出现无限的空pop
如果连接的客户端比较多，就会占用很高的cpu redis就会出现比较慢的响应 

如果说使用程序的sleep来做延迟，比如这样：

```php
while(true){
	$task = $redis->rpop($task_key);
    //do some oper here ...
    sleep(1);
}
```

这样做的缺点很明显，就是我没办法做到消息的实时更新处理，如果某个时间段来了大量的
任务，就会造成很大的延时，这时候，可以使用redis的 blpop 或者 brpop 来阻塞pop

```php
while(true){
	$task = $redis->brpop($task_key,1);
    //do some oper here ...
}
```

### 3. 注意事项

上面的进程如果长时间处于空闲状态，就可能出现空闲连接被服务器主动断开的问题

需要考虑客户端断开重连的异常问题

示例参考代码：


```php
function redisConnection() {
    try {
        $redis = new Redis()
        $redis->pconnect(localhost, 6336, 2);
        $redis->select(15);
        $redis->ping();
        return $redis;
    } catch (Exception $e) {
        throw new Exception("Can not connect: " . $e->getMessage());
    }
}

$redis = redisConnection();
while (true) {
    try {
        $redis->ping();
    } catch {
        $redis = redisConnection();
    }
    // Rest of code
}

```

参考地址：https://stackoverflow.com/questions/25236494/redis-connection-inside-infinite-loop

### 4. 延时队列

加锁未加成功，如何处理，延时队列进行重试


```
zadd key score value 
zrangebyscore key min max [withscores] [limit offset count]
zrem key value
``` 
原理：使用redis的有序集合来存储任务队列，将score作为时间戳来进行处理

```php

//加入队列
function addTaskQueue($msg) 
{
    $redis->zadd('queue',time()+5, json_encode($msg));	
}

//处理队列
function run()
{
	while(true){
	    $value = $redis->zrangebyscore('queue',0,time(),$start=0,$count=1);
	    if (empty($value)){
	        sleep(1);
	        continue;
	    }
	    $val = $value[0];
	    $success = $redis->zrem('queue',$val);
	    if($success) {
	        try {
	            $msg = json_decode($val,true)
	            //handle msg
	        }cacth(Exception $e){
	            //出现异常 重新加入集合
                addTaskQueue($msg);
	        }
	    }
	}
}

```
