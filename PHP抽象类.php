<?php


abstract class C 
{
    abstract function e();
    public function c1()
    {
        echo " this is common public fucntion \n";
    }
    final function c2()
    {
        echo " this is final fucntion \n";
    }
    static function c3()
    {
        echo " this is static function c3 \n";
    }
}

final class D extends C  //final class 不能被继续继承
{
     public function e()
     {
         parent::c1(); //注意父类方法不管是 静态非静态 都只能使用 parent::方法名 进行调用
         parent::c3(); //静态方法可以通过parent 父类获取 
         echo '11';
     }
}


$d = new D();
$d->e();
$d->c2(); //final 方法不能被子类重写 但可以继承
C::c3(); //静态方法可以通过父类获取 即使是抽象类
D::c3(); //静态方法可以通过子类获取
$d::c3(); //静态方法可以通过对象获取




