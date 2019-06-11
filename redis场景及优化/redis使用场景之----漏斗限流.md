# redis应用场景之------限流

参考文章： https://redislabs.com/blog/redis-cell-rate-limiting-redis-module/

限定某个行为在指定的时间里只能允许发生N次


### 1. 安装

加载安装模块

https://github.com/brandur/redis-cell/releases

下载对应的编译好的包，直接在加载的时候引入

```
redis-server  --loadmodule /data/redis/libredis_cell.so
```

### 2. 命令说明

<pre>
cl.throttle hello:reply 15 30 60 1
                 |       |  |  | |__ 默认值 1
                 |       |  |__|___ 60s操作速率30次 也就是 2s一次
                 |       |_ 漏斗容量
                 |__ key  


127.0.0.1:6379> cl.throttle user1:token:getInfo 15 30 60 1
1) (integer) 0   0 表示允许 1 表示拒绝
2) (integer) 16  容量
3) (integer) 15  剩余容量
4) (integer) -1  如果拒绝对应的重试时间
5) (integer) 2   拒绝后 多长时间后重试
</pre>

60s操作速率30次  也就是漏斗满容量后，每2s流出一个，然后流进来一个
这里讲的是一个流速，不是真的操作30次 改成 1 2 结果也是相同的

### 3. 尝试代码

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

	i := 0

	t := time.After(time.Second * 20)

	for {
		res, err := conn.Do("cl.throttle", "user1:token:getInfo", "15", "1", "3", "1")
		if err != nil {
			fmt.Printf("%v", err)
			break
		}
		elems := res.([]interface{})
		if elems[0].(int64) == 0 {
			i++
			fmt.Printf("%d ：进入\n", i)
		}
		select {
		case <-t:
			goto HII
		default:
			continue
		}
	}
HII:
	fmt.Println("over")

}

```

