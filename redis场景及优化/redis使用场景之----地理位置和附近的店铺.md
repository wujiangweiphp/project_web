# redis应用场景之------ 附近的店铺

### 1. 原理简介

<pre>
`GeoHash`算法

该算法将二维的经纬度数据映射到一维的整数，存储在`zset` 中
`value` ： 是元素的key
`score` ： 经纬度度使用52位整数进行编码无损存储 

通过zset的score排序就可以得到坐标附近的其他元素，再通过将 score还原成坐标值就可以
得到元素的原始坐标
</pre>

### 2. 常用指令使用

#### 2.0 数据准备

```
geoadd shop 120.130225 30.284537 zjdl
geoadd shop 120.132524 30.281793 hlwk
geoadd shop 120.131375 30.283539 hxsd
geoadd shop 120.121278 30.28329 dzsw
geoadd shop 120.121278 30.28329 hxsd
geoadd shop 120.125266 30.280795 my
```

#### 2.1 添加和查看

```
geoadd key lng lat member
geopos key member
```
示例如下：

```
127.0.0.1:6379[1]> geoadd shop 120.125266 30.280795 my
127.0.0.1:6379[1]> geopos shop my
1) 1) "120.12526541948318481"
   2) "30.28079412528742154"
```

存入的地理位置和取出的有些误差，因为二维转一维整数计算时有损 但是精度比较大的时候 这点
误差可以忽略不计

#### 2.2 计算两点之间的距离

```
geodist key mem1 mem2 m
```
这里 第四个参数是单位 m 米 km 千米 英里 和 尺

#### 2.3 获取对应经纬度的hash值

```
geohash key mem1 
```
示例如下：
```
127.0.0.1:6379[1]> geohash shop hlwk
1) "wtmkm8tu4x0"
```
地图上查看
http://geohash.org/wtmkm8tu4x0

#### 2.4 附近的位置

```
georadiusbymember shop my 20 km count 3 asc
```
20公里内最多3个元素 按距离正序排列 但不会排除自身
```
127.0.0.1:6379[1]> georadiusbymember shop my 700 m count 5 asc
1) "my"
2) "dzsw"
3) "hxsd"
4) "zjdl"
```
<pre>
有三个参数比较有用 
withcoord  返回实际经纬度
withdist   返回计算距离
withhash   返回hash整数
</pre>

示例如下：

```
127.0.0.1:6379[1]> georadiusbymember shop my 700 m  withcoord withdist withhash  count 5 asc
1) 1) "my"
   2) "0.0000"
   3) (integer) 4054133887845536
   4) 1) "120.12526541948318481"
      2) "30.28079412528742154"
2) 1) "dzsw"
   2) "472.9450"
   3) (integer) 4054133865946467
   4) 1) "120.12127965688705444"
      2) "30.28329082562937202"
3) 1) "hxsd"
   2) "472.9450"
   3) (integer) 4054133865946467
   4) 1) "120.12127965688705444"
      2) "30.28329082562937202"
4) 1) "zjdl"
   2) "632.8878"
   3) (integer) 4054133891210387
   4) 1) "120.13022750616073608"
      2) "30.28453790843976634"
```
#### 2.5 指定经纬度距离

```
>georadius shop 120.1212 30.2845 400 m withdist count 3 asc 

1) 1) "dzsw"
   2) "134.7094"
2) 1) "hxsd"
   2) "134.7094"
```






