# redis应用场景之----新闻推送去重布隆过滤器

布隆过滤器 参考文档：
https://redislabs.com/blog/rebloom-bloom-filter-datatype-redis/
https://mrxin.github.io/2018/11/28/redis-4-0-bloom/

### 1. 传统的解决方案

针对每个用户建立一个集合set存储已经浏览过的新闻，下次新加进来之前
查一下集合里面有没有

### 2. 布隆过滤器

可以理解为一个不怎么精确的set结构，使用它的contains方法判断某个对象是否存在可能误判
节省存储空间90%以上

误判：不存在肯定不存在
     见过 没有看过的新内容（相似性）可能存在（这里出现误判）
布隆过滤器的原理是，当一个元素被加入集合时，通过K个Hash函数将这个元素映射成一个位数组中的K个点，把它们置为1。检索时，我们只要看看这些点是不是都是1就（大约）知道集合中有没有它了：如果这些点有任何一个0，则被检元素一定不在；如果都是1，则被检元素很可能在。这就是布隆过滤器的基本思想。

### 3. 插件安装

注意：仅支持redis4.0及以上版本

```
git clone git://github.com/RedisLabsModules/rebloom
cd rebloom
make
```
启动bloom过滤器插件

方法一：添加编译后的rebloom.so 加到redis.conf
```
loadmodule /path/to/rebloom.so
```
方法二：
```
> redis-server --loadmodule /path/to/rebloom.so
```


### 4. 常用操作    

#### 4.1 单个元素添加或判断

```
bf.add key ele1
bf.exists key ele1
```
返回结果
(integer) 1

如果存在的时候再次加入 返回
(integer) 0

#### 4.2 多个元素添加或判断

```
bf.madd key ele1 ele2 ...
bf.mexists key ele1 ele2 ...
```
返回多个结果
1) (integer) 0
2) (integer) 1


#### 4.3 查看某个key的占用空间大小

```
bf.debug key
```

#### 4.4 自定义布隆过滤器

在add之前使用 bf.reserve 指令显示创建，key不能存在，否则会报错

```
bf.reserve key error_rate init_size
```
error_rate 错误率 越低存储空间越大
init_size  预计放入元素个数 当实际数量超出这个数值 误判率会上升

比如：
```
bf.reserve user1view 0.0001 200000 
```

### 5. 应用场景

爬虫系统对url过滤


