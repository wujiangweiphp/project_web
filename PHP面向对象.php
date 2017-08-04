php 面向对象

<?php
/*
 *  接口定义中：方法没有实现体
 */
interface A
{
	public function a();
}
interface B
{
	public function b();
}
/*
 * 类中的片段代码 代码复用 trait - use 
 */
trait E_1
{
   public function tools()
   {
       echo " this is tools \n";
   }    
}

/*
 * 可以同时实例化多个接口 解决单继承问题
 */

class E implements A,B 
{
    use E_1;  //使用trait 代码复用 
    const E1 = '15'; //类中常量使用
    static $e2 = 1;  //类中静态变量使用
	public $e3 =2;   //类中普通成员变量使用
    public function __construct(){
        self::$e2++;
    }
    public function a(){
        echo 'E1:'.self::E1."\n";
    }
    public function b(){
        echo 'e1:'.self::$e2."\n";
    }
}
$e1 = new E();
$e1->a();
$e1->b();
$e2 = new E();
$e1->a();
$e1->b();
$e2->a();
$e2->b();
$e2->tools();

public 类中类外都能访问
protected 父类子类 类中可访问
private 父类类中可访问








