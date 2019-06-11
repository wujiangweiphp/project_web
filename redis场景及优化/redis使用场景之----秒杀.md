# redis使用场景之----秒杀.md

> 我们知道秒杀处理有很多种方式，其中最常见的方式就是异步变同步

使用reids队列来处理，但是并发量较大的情况下，队列可能会瞬间撑爆内存，我们使用更为简单
的方式来提前限流。


### 1. 数据预热

处理框架基于tp5 ,假设有时十件商品
我们设置每件的库存为10，每件的初始售卖量为 0 

```php
public function preloadData()
{
    $redis = new Redis();
    $redis->handler()->pipeline();
    for ($i = 1; $i <= 10; $i++) {
        $redis->set('good-id:' . $i, 10);
        $redis->set('sales-good-id:' . $i, 0);
    }
    $redis->handler()->exec();
}
````

### 2. 购买模拟

每次我们购买前，我们判断一下售卖量是否已经达到限制，如果达到则直接返回退出
如果未达到，我们增加售卖量，并根据增加的成功与否 来进行数据持久化处理

```php
public function buy()
{
    $redis = new Redis();
    $id    = input('post.id');
    $limit = $redis->get('good-id:' . $id);
    $sales = $redis->get('sales-good-id:' . $id);
    if ($sales >= $limit) {
        die('已卖完');
    } else {
        //该结果返回自增后的数
        $result = $redis->inc('sales-good-id:' . $id, 1);
        if ($result > 0 && $result != $sales && $limit>= $result) {
            // mysql handle here ...
            die('购买成功');
        } else {
            die('挤爆了，请稍后再试');
        }
    }
}
```

这种方式的好处在于，我们将大部分的访问阻挡在业务处理之外，而只有有效的访问进入业务处理
极大的保证了并发对数据库的影响，并能做到快速响应处理，而不需要等待队列进行排队消耗。

