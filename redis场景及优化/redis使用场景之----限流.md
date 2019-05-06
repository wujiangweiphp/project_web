# redis使用场景之----限流.md

> 我们开放给别人的接口，如果用户量比较多，而且有些情况下出现恶意请求，如何限制呢

可以使用redis的队列进行限流

### 1. 使用队列进行限流

下列操作框架基于tp5，假设我们5s内只允许请求 20 次，超过则直接返回`请求太频繁`
代码如下：

```php
public function index()
{
    $redis = new Redis();
    $ip = \request()->ip();
    $method = 'add';
    $ip .= $method;
    $limit = 20;
    if ($redis->handler()->lLen($ip) < $limit) {
        $redis->handler()->lPush($ip, time()); //左侧压入队列
        $redis->handler()->expire($ip, 10); //最后一次设置的失效时间有效
    } else {
        $lasttime = $redis->handler()->lIndex($ip, 0); //获取左侧第一个元素 （也就是最后一次压入的元素）
        if (time() - $lasttime < 5) {
            die('请求太频繁');
        }
    }
    // 成功请求时处理
    die('请求成功');
}
```

### 2. 简化处理

上面的代码有个弊端就是我们每次处理都需要 针对每个ip和每个请求方法 压入30个数据进入队列
这对内存的消耗太大，虽然我们保存的都是数字。那我们能不能只针对单个ip和单个方法保存一条记录呢。

```php
public function index()
{
    $redis = new Redis();
    $ip = \request()->ip();
    $method = 'add';
    $ip .= $method;
    $limit = 10;
    $count = 1;
    $request = $redis->get($ip);
    if (!empty($request)) {
        list($count, $lasttime) = explode('-', $request);
        if (time() - $lasttime < 10 && $count >= $limit) {
            die('请求太频繁');
        }
        $count++;
    }
    $redis->set($ip, $count . '-' . time());
    $redis->handler()->expire($ip, 10); //最后一次设置的失效时间有效
    // 成功请求时处理
    die('请求成功');
}
```

这个请求实例每个方法和ip都只生成唯一一条记录，大大节省了瞬间请求过多的内存压力。

