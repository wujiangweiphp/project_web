# redis应用场景之------签到记录


>用户一年的签到记录，签了是1，没签是0，如果使用普通的key/value 存储量惊人
某一条群发消息，用户id为1的看了，生成已浏览 用户id为n的用户看了也是已浏览
怎么存储 


### 1. 设置某一天的签到值

```
setbit user1-2019 189 1
setbit user1 20190611 1 
```
示例：

```
127.0.0.1:6379> setbit user1 20190611 1
(integer) 0
127.0.0.1:6379> memory usage user1
(integer) 2523884
127.0.0.1:6379> setbit user1-2019 364 1
(integer) 0
127.0.0.1:6379> memory usage user1-2019
(integer) 148
```

### 2. 查询某一天是否已经签到

```
getbit user1-2019 189
```

### 3. 统计指定范围内1的个数

因为redis没有提供什么好的方法来直观的查询某个范围内的签到情况 所以 只能使用程序来程序


```php
$distance_first = date('z',time()) + 1;
$count = 0;
//过去一个月的签到统计 和 签到记录
$logs = [];
for($i = $distance_first; $i>$distance_first-30;$i++ ) {
	$mark = $redis->getbit('user1-2019',$i);
	$count += $mark; 
	$logs[] = $mark;
}
```

<pre>
bitcount  key start end
start : start * 8
end : end * 8 - 1 
</pre>

### 4. 统计所有数量

```
bitcount key
bitcount key 0 -1
```







