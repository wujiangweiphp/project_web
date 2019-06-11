# redis应用场景之----分布式锁

### 1. 原始setnx

占坑
```
setnx (set if not exists)
```

场景一：
```
setnx lock:buygood:1 true
//do buy 
del lock:buygood:1
```

弊端：如果中间的 `do buy` 出现异常，导致最终没有执行到 `del` 指令 则会造成死锁

场景二：

```
setnx lock:buygood:1 true
expire lock:buygood:1 5
//do buy 
del lock:buygood:1
```

弊端：如果在`setnx` 和 `expire`之间服务器进程被kill或挂掉，依然会导致死锁
根源在于`setnx`和`expire`不是原子指令，如果他们合在一起就不会出现问题

### 2. 原子指令set(推荐)

```
set lock:buygood:1 true ex 5 nx
del lock:buygood:1 
```

nx： key不能存在才能设置成功
xx： key存在才能设置成功

未来版本可能会废弃 `setnx`






