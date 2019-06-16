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

### 4. 实现原理

0000 0000 | 0000 0000 
存储是以单个2位16进制无符号整型存储 每超过最大数值范围就增加 1个整数

1000 0000 | 第 0 位 类似于第 0 天的签到 对应的十进制为 128
0100 0000 | 第 1 位 类似于第 1 天的签到 对应的十进制为 64
0010 0000 | 第 2 位 类似于第 2 天的签到 对应的十进制为 32

所以 bitcount 所实现的统计是以8位的倍数 所做的统计

start : start * 8
end : end * 8 - 1

bitcount  key start end



### 5. 统计实现

```go
res, err := conn.Do("get", "user1")
result := res.([]uint8)
fmt.Println(res)

//result := []uint8{0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 24}
count := countRange(result, 187, 190)
fmt.Println(count)

/**
  result := []uint8{0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 24}
*/
func countRange(result []uint8, start, end int) int {
	str := ""
	for _, v := range result {
		tmp := fmt.Sprintf("%b", v)
		l := len(tmp)
		if 8 > l {
			str += strings.Repeat("0", 8-l)
		}
		str += tmp
	}
	fmt.Println(str)
	byteStr := []rune(str)
	count := 0
	for i := start; i <= end; i++ {
		if byteStr[i] == '1' {
			count++
		}
	}
	return count
}
```
lua实现

```lua
local function countRange(key,start,end)
    count = 0
	for i=start,end,1 do
       count = count + redis.call("getbit",i)
	end
	return count
end
```







