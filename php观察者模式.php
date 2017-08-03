<?php
/*
 【观察者模式|发布/订阅模式】
事件、消息队列系统 使用观察者模式
PHP 为观察者模式定义了两个接口：SplSubject 和 SplObserver。
还有一个对象存储类：SplObjectStorage
SplObjectStorage：类提供从对象到数据的映射,对象存储
SplSubject: 接口 提供attach 和 detach、notify方法
SplObserver: 接口 提供update(SplSubject $subject)
*/
/**
 * 【主体对象】
 * 观察者模式 : 被观察对象 (主体对象)
 *
 * 主体对象维护观察者列表并发送通知
 *
 */
class User implements SplSubject
{
    protected $data = array();
    protected $observers;
    
    public function __construct()
    {
        $this->observers = new SplObjectStorage();
    }

    /**
     * 添加观察者
     */
    public function attach(SplObserver $observer)
    {
        $this->observers->attach($observer);
    }

    /**
     * 取消观察者
     */
    public function detach(SplObserver $observer)
    {
        $this->observers->detach($observer);
    }

    /**
     * 通知观察者方法
     */
    public function notify()
    {
        /** @var \SplObserver $observer */
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }

    /**
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;

        // 通知观察者用户被改变
        $this->notify();
    }

    public function __get($name)
    {
        return $this->$name;
    }
}


/**
 * 【观察者】
 * UserObserver 类（观察者对象）
 */
class UserObserver implements SplObserver
{
    /**
     * 观察者要实现的唯一方法
     * @param SplSubject $subject
     */
    public function update(SplSubject $subject)
    {
        $this->sendmsg($subject->data);
    }
	
	public function sendmsg($data)
	{
		print_r($data);
	}
}



/**
 * ObserverTest 测试观察者模式
 */
$observer1 = new UserObserver();
$observer2 = new UserObserver();
$subject = new User(); //主体

$subject->attach($observer1);
$subject->attach($observer2);
$subject->property = 123;
$subject->proy = 123;
    

/**
 * 测试订阅
 */
$subject = new User();
$reflection = new ReflectionProperty($subject, 'observers');

$reflection->setAccessible(true);
/** @var SplObjectStorage $observers */
$observers = $reflection->getValue($subject);

var_dump($observers->contains($observer1));

$subject->attach($observer1);
var_dump($observers->contains($observer1));

$subject->detach($observer1);
var_dump($observers->contains($observer1));


?>
