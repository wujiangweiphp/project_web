# redis应用场景之-----uv统计 HyperLog

### 1. 添加uv

```
pfadd key user1 user2 ...
```

### 2. 统计uv

```
pfcount key
```

### 3. 多个key的uv去重统计

```
pfcount key1 key2 ...
```

### 4. 合并多个key到新的key 自动去重
```
pfmerge destkey srckey1 srckey2 srckey3 ... 
```

### 5. 原理

内存占用 12k
复杂公式推导 count-distinct problem
估计值：误差大概 0.81%左右

### 6. 操作
```
pfadd 201906100806 user3 user4 user5 user7
(integer) 1
127.0.0.1:6379> pfcount 201906100806
(integer) 7
127.0.0.1:6379> pfcount 201906100806
(integer) 7
127.0.0.1:6379> pfadd 201906100806 user3 user4 user5 user7 user8
(integer) 1
127.0.0.1:6379> pfcount 201906100806
(integer) 8
127.0.0.1:6379> pfcount 201906100807
(integer) 0
127.0.0.1:6379> pfcount 201906100809 user3 user4 user5 user7 user8
(integer) 0
127.0.0.1:6379> pfadd 201906100809 user3 user4 user5 user7 user8
(integer) 1
127.0.0.1:6379> pfcount 201906100806 201906100807 201906100809
(integer) 8
127.0.0.1:6379> pfadd 201906100809 user10
(integer) 1
127.0.0.1:6379> pfadd 201906100809 user9
(integer) 1
127.0.0.1:6379> pfadd 201906100809 user11
(integer) 1
127.0.0.1:6379> pfcount 201906100806 201906100807 201906100809
(integer) 11
127.0.0.1:6379> pfmerge 2019061008 201906100806 201906100807
OK
127.0.0.1:6379> pfcount 2019061008
(integer) 8
127.0.0.1:6379> pfmerge 2019061008 201906100806 201906100809
OK
127.0.0.1:6379> pfcount 2019061008
(integer) 11
```

### 7. 代码

```go
package main

import (
	"fmt"
	"github.com/gomodule/redigo/redis"
	"math/rand"
	"time"
)

type RedisConfig struct {
	Ip       string
	Port     string
	Password string
	Db       int
}

/**
 *  获取连接信息
 */
func GetConn(conf RedisConfig) (conn redis.Conn, err error) {
	conn, err = redis.Dial("tcp", conf.Ip+":"+conf.Port)
	if err != nil {
		return nil, err
	}
	// 1. 认证
	if conf.Password != "" {
		if _, err := conn.Do("AUTH", conf.Password); err != nil {
			conn.Close()
			return nil, err
		}
	}

	// 2. 选择库
	if _, err := conn.Do("SELECT", conf.Db); err != nil {
		conn.Close()
		return nil, err
	}
	return conn, err
}

func init() {
	rand.Seed(time.Now().Unix())
}

func main() {

	conn, err := GetConn(RedisConfig{
		Ip:       "127.0.0.1",
		Port:     "6379",
		Password: "",
		Db:       1,
	})
	if err != nil {
		panic(err)
	}

	defer conn.Close()
	/*
		t := time.Now()
		for i := 1440; i > 0; i-- {
			tmp := time.Duration(-1 * i)
			n := rand.Intn(1000)
			key := t.Add(time.Minute * tmp).Format("200601021504")
			for j := n; j > 0; j-- {
				conn.Do("pfadd", key, "user"+strconv.Itoa(j))
			}
		}
	*/
	ts := []interface{}{}

	t := time.Now()
	for i := 2141; i > 1000; i-- {
		tmp := time.Duration(-1 * i)
		key := t.Add(time.Minute * tmp).Format("200601021504")
		ts = append(ts, key)
	}
	res, err := conn.Do("pfcount", ts...)
	fmt.Printf("%v", res)

}

```



