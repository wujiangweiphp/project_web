## redis扩展应用 --- lua脚本

参考文章：https://www.compose.com/articles/a-quick-guide-to-redis-lua-scripting/
https://www.compose.com/articles/debugging-lua-in-redis/

### 1. hello world 

```
eval 'local val="hello world" return val' 0
```
上面执行的字符串 就是lua脚本

```lua
local val="hello world"
return val
```

### 2. 传递键和参数

上面示例中的 0，即传递给lua的key的数量，示例中并没有使用，所以为 0
如果不为 0，比如传递
```
eval `...` 2 name age "zhangsan" 32
```
根据名称可以看出他们分别指代的是 两个key name和age
两个参数值 "zhangsan" 和 32

如何使用这两个参数，lua中使用 
`KEYS[1]` : 接收键名 其中1是第一个键 
`ARGV[1]` : 接收参数 其中1是第一个值

```lua
127.0.0.1:6379> eval "return KEYS[1]..':'..ARGV[1]" 1 name "zhangsan"
"name:zhangsan"
``` 
注意：上面的 `..` 是lua的字符串连接符号

### 3. 在lua中调用redis指令

在lua中调用redis指令十分简单，直接使用下面的函数即可

```
redis.call(command,args...)
```

比如 ：
```lua
127.0.0.1:6379> set name lisi
OK
127.0.0.1:6379> eval "return KEYS[1]..':'..redis.call('get',KEYS[1])" 1 name
"name:lisi"
```

### 4. 使用外部lua脚本

我们编写外部lua脚本，test.lua

```lua
local name=redis.call("get",KEYS[1])
local greet=ARGV[1]
return greet.." "..name.." "..KEYS[2].." "..ARGV[2]
```
然后我们执行：
```
> /usr/local/bin/redis-cli --eval /data/redis/test.lua name age , "hello" 23
//输出
"hello lisi age 23"
```
注意外部执行时，键值分割符是 `,`，分隔符前面是key，后面是参数，多个用空格分割


### 5. 统计支付通知还有多少没被处理

比如有这样一个需求，我们要查看某个人还有多少支付没有通知到他
我们有秒杀和团购两个场景

```
lpush miaosha user1 user2 user3 
lpush tuangou user2 user4
```

编辑count.lua脚本数量

```lua
 local function incr(key)
    redis.call("incr",key)
 end

 local count=0
 local msgs=redis.call("lrange",KEYS[1],0,-1)
 for _,key in ipairs(msgs) do
    if not pcall(incr,key) then
       redis.call('set',key,1)
    end
    count=count+1
 end
 return count
```

执行lua脚本
```
/usr/local/bin/redis-cli --eval /data/redis/count.lua miaosha
/usr/local/bin/redis-cli --eval /data/redis/count.lua tuangou

mget user1 user2 user3
1
2
1
1
```

由上面的简单例子可以看出

lua学习文档参考：https://www.runoob.com/lua/lua-for-loop.html

#### 5.1 函数
lua脚本的函数声明格式
```
local function fucname(arg1,arg2...)
   //code here
end
```

#### 5.2 循环

键值对循环：
```
for k,v in ipairs(loopdata) then
  //code here
end
```
数值递增循环：
```
for i=start,end,step do
    print(i)
end
```

### 5.3 判断

```
if conditions then
  //code
else 
  //code 
end
```
与或非使用：`and`/`or`/`not`

### 5.4 函数调用的异常捕获

```
if not pcall(funcname,arg1,arg2 ...) then
  //error deal
end 
```

### 6. redis脚本缓存

```
/usr/local/bin/redis-cli SCRIPT LOAD "$(cat /data/redis/count.lua)"
36ba84996f8c17c1b2f3e24cac7075a5788aee53
```

手动执行：

```
> lpush order user3 user 4 user2 user5
> evalsha 36ba84996f8c17c1b2f3e24cac7075a5788aee53 1 order
> mget user1 user2 user3 user4 user5
1) "1"
2) "3"
3) "2"
4) "1"
5) "1"
```

脚本执行过程中，其他所有的命令都处于暂停状态，有5s的最大执行时间


### 7. 使用reids调试lua

```
redis-cli --ldb
```
执行lua脚本时，加入 `--ldb` 可选参数启动调试

12个调试选项

s/ step
n/ next
c/ continue
quit/ 退出
restart / 重新载入脚本
help / 帮助信息
l /查看脚本的特定部分
w /查看真个脚本
b 5 /在第5行设置断点
b / 列出所有的断点
print varname / 打印变量的值
redis/r redis指令参数 /执行redis相关指令

lua脚本中添加调试选项

```lua
//打印调试错误信息
redis.debug()
//添加断点
redis.breakpoint()
```

### 8. 使用场景

复杂操作，又要保证原子性，可以考虑

参考文章：https://stackoverflow.com/questions/30869919/redis-lua-when-to-really-use-it














