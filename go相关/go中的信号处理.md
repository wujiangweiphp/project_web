
golang中的信号处理

> golang中常常会遇到系统级别的进程操作，比如 `kill -signal pid`
那如何动态接收这个进程信号呢，可以使用对应的系统库 `syscall`包处理

### 0. 什么是信号

中断可以视为CPU和OS内核之间通信的一种方式。可以将信号视为OS内核和OS进程之间的通信方式。

中断可以由CPU启动（例外 - 例如：除以零，页面错误），设备（硬件中断 - 例如：输入可用），或通过CPU指令（陷阱 - 例如：系统调用，断点）。它们最终由CPU管理，它“中断”当前任务，并调用OS内核提供的ISR /中断处理程序。

信号可以由OS内核（例如：SIGFPE，SIGSEGV，SIGIO）或进程（kill（））发起。它们最终由操作系统内核管理，操作系统内核将它们传递给目标线程/进程，调用通用操作（忽略，终止，终止和转储核心）或进程提供的信号处理程序

参考文章地址：https://stackoverflow.com/questions/13341870/signals-and-interrupts-a-comparison

### 1. 接收处理信号

```
func main() {
	sig := make(chan os.Signal,1) // 创建终端信号接收channel

	signal.Notify(sig,syscall.SIGINT,syscall.SIGHUP) //接收信号通知

    //信号处理
    go handleSignal(sig)

    //主要业务
    // code here ....
}
```

### 2. 处理信号

```
func handleSignal(sig os.Signal){
    s := <-sig
	switch s {
	    case syscall.SIGINT :
          fmt.Println("sigint")
	    case syscall.SIGHUP :
	      fmt.Println("sigint")
	    case syscall.SIGKILL :
          fmt.Println("sigkill")
	}
}

```

### 3. 系统调用

| 系统调用  | 说明 |
| -------- | --- |
| `os.Exit(0)` | 系统中止退出 使用此函数 defer将不会执行 |
| `os.Getpid()` | 获取当前go程序的进程id 一般接收信号使用此pid |
| `os.Getppid()` | 获取当前进程的父进程id |
| `os.Setenv("pid","2222")` | 设置环境变量 |
| `os.Getenv("pid")` | 获取系统环境变量 | 
| `os.LookupEnv("HOME")` | 查看环境变量是否设置 并返回结果 |
| `os.Environ()` | 获取所有的环境变量 |
| `os.Unsetenv(key)` | 移除单个环境变量 |
| `os.Chdir(dir)` | 更改操作目录 |
| `os.Getwd()` | 获取当前目录 |
| `os.Chmod(name,mode)` | 增加文件权限 |
| `os.Chown(name,uid,gid)` | 文件赋予用户和组 |
| 




### 4. 命令执行

#### 4.1 syscall.Exec

```
if err := syscall.Exec("/bin/ls", []string{"ls", "-al"}, os.Environ()); err != nil {
	log.Fatal(err)
}
```
我们跟踪到函数底层
```
/usr/local/go/src/syscall/exec_unix.go
```
第254行有一句注释：
```
// Exec invokes the execve(2) system call.
```
该方法调用的底层是liunx的系统函数 `execve(2)`
而该方法对应的Linux文档说明如下：
文档地址： http://man7.org/linux/man-pages/man2/execve.2.html
```
执行pathname引用的程序。这导致当前正由调用进程运行的程序用新的初始化堆栈和堆 替换为新程序（初始化和未初始化）数据段。
```
没有新的进程产生，源于的pid也保持不变 任何未完成的IO操作都将被取消


#### 4.2 exec.Command

设置控制台输出

```
cmd := exec.Command("ls","-al")
cmd.Stdout = os.Stdout
cmd.Stderr = os.Stderr
err := cmd.Run()
if err != nil {
	log.Fatalf("%v",err)
}
```

接收命令执行结果

```
cmd := exec.Command("ls", "-lah")
if runtime.GOOS == "windows" {
	cmd = exec.Command("tasklist")
}

out, err := cmd.CombinedOutput()
if err != nil {
	log.Fatalf("cmd.Run() failed with %s\n", err)
}
fmt.Printf("combined out:\n%s\n", string(out))
return
```

这里的`CombinedOutput` 实际上做了两件事
1. 命令输出和命令错误都赋值给实现了 io.Writer的 bytes.Buffer 类型的变量 
2. 调用 Run 方法 返回结果

参考文章：https://blog.kowalczyk.info/article/wOYk/advanced-command-execution-in-go-with-osexec.html

https://www.darrencoxall.com/golang/executing-commands-in-go/



### 信号附录 

IPC -- inter-process communication 进程间通信

POSIX -- portable operating system interface for uninx  可移植的操作系统接口

信号附录 `signal`

| 信号  | 对应数字 | 是否为同步信号  | 说明 |
| ---- | --- | --- | ----- |
| SIGHUP    |  1   | 异步 | 挂起 程序丢失控制终端 |
| SIGINT    |  2   | 异步 | 终端中断信号 ctrl+c |
| SIGQUIT   |  3   | 异步 | 终端退出信号 ctrl+/ |
| SIGILL    |  4   | 异步 | 非法指令 |
| SIGTRAP   |  5   | 异步 | 跟踪/ BPT陷阱 |
| SIGABRT/SIGIOT   | 异步 |  6   | 中止陷阱 |
| SIGEMT    |  7   | 异步 | EMT陷阱 |
| SIGFPE    |  8   | 同步 | 浮点异常 错误的算术运算 如除0 |
| SIGKILL   |  9   | 异步 | 杀 （不能被捕获或忽略） |
| SIGBUS    |  10  | 同步 | 总线错误 访问内存对象的未定义部分 | 
| SIGSEGV   |  11  | 同步 | 分段错误  无效的内存引用|
| SIGSYS    |  12  | 异步 | 糟糕的系统调用 |
| SIGPIPE   |  13  | 异步 | 写在没有人阅读的管道上  |
| SIGALRM   |  14  | 异步 | 闹钟 |
| SIGTERM   |  15  | 异步 | 终止信号 |
| SIGURG    |  16  | 异步 | 紧急 I/O|
| SIGSTOP   |  17  | 异步 | 暂停信号 信号可能无法被程序捕获 |
| SIGTSTP   |  18  | 异步 | 暂停 |
| SIGCONT   |  19  | 异步 | 继续 如果停止，继续执行 |
| SIGCHLD   |  20  | 异步 | 子进程已终止，停止或继续 |
| SIGTTIN   |  21  | 异步 | 停止 tty输入 后台进程尝试读取 | 
| SIGTTOU   |  22  | 异步 | 停止 tty输出 尝试写入的后台进程 |
| SIGIO     |  23  | 异步 | 可能I/O |
| SIGXCPU   |  24  | 异步 | 超出cpu时间限制 |
| SIGXFSZ   |  25  | 异步 | 超出文件大小限制 |
| SIGVTALRM |  26  | 异步 | 虚拟计时器已过期 |
| SIGPROF   |  27  | 异步 | 分析计时器已过期 |
| SIGWINCH  |  28  | 异步 | 终端窗口大小变化 |
| SIGINFO   |  29  | 异步 | 信息请求 |
| SIGUSR1   |  30  | 异步 | 用户定义信号 1 |
| SIGUSR2   |  31  | 异步 | 用户定义信号 2 |

同步信号会直接转换成运行时panic恐慌，异步信号不是程序错误触发的，而是从内核或
其他程序发送的，

系统信号附录 `os`

| 系统信号 | 对应的上述信号 | 
| ----- | ---- |
| Interrupt | SIGINT |
| Kill |  SIGKILL |

信号的入门教程（基于C）

https://www.usna.edu/Users/cs/aviv/classes/ic221/s16/lec/19/lec.html

