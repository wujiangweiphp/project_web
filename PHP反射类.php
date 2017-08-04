参考：https://www.sitepoint.com/introspection-and-reflection-in-php/
Introspection(内省)程序在运行时检查对象的类型或属性的能力，他允许对象类由程序员操纵。

你将会发现introspection 相当有用当你不知道哪一个类后或者方法在设计时需要被执行.

Introspection 在 PHP 提供非常有用的能力去检查类(classes), 接口(interfaces), 属性(properties), 
和方法(methods). PHP 提供了大量的函数你能用他来完成这些检查任务. 
为了帮助你理解introspection, 我将简要概述一些 PHP的 类(classes),方法(methods), 和函数(functions)
他们被使用的时候，代码会高亮显示.

通过这篇文章, 你将会看到一组例子如何去使用一些非常有用的 PHP的 introspection 函数 
以及专门针对API提供类似于introspection功能的部分, 反射(Reflection) API.

PHP Introspection 函数
第一个例子, 我会展示一些 PHP的 introspection functions. 你能使用这些功能functions 
去检查基础的信息包括关于类(classes)像他们的名字(name), 父类的名字等等.

<?php 
class_exists() # 检查一个类是否被定义
get_class()    # 返回对象的类名
get_parent_class() # 返回对象的类的父类名
is_subclass_of() # 检查一个对象是否是给定父类的 子类
?>

下面是一个例子 PHP 包含定义Introspection 和子类以及输出输出通过上述功能提取的信息：


<?php
class Introspection
{
    public function description() {
        echo "I am a super class for the Child class.n";
    }
}

class Child extends Introspection
{
    public function description() {
        echo "I'm " . get_class($this) , " class.n";
        echo "I'm " . get_parent_class($this) , "'s child.n";
    }
}

if (class_exists("Introspection")) {
    $introspection = new Introspection();
    echo "The class name is: " . get_class($introspection) . "n"; 
    $introspection->description();
}

if (class_exists("Child")) {
    $child = new Child();
    $child->description();

    if (is_subclass_of($child, "Introspection")) {
        echo "Yes, " . get_class($child) . " is a subclass of Introspection.n";
    }
    else {
        echo "No, " . get_class($child) . " is not a subclass of Introspection.n";
    }
}
?>

输出如下:

The class name is: Introspection
I am a super class for the Child class.
I'm Child class.
I'm Introspection's child.
Yes, Child is a subclass of Introspection.



这是第二个例子，其中包含ICurrencyConverter接口和GBPCurrencyConverter类的定义，
并输出上面列出的函数提取的信息。 与第一个例子一样，我将首先列出这些函数，然后显示一些代码。
<?php 
get_declared_classes() # 返回一系列已经定义的类
get_class_methods()  # 返回给定【类|对象】的所有方法 private 和 protected 方法会被跳过
get_class_vars() # 返回当前【类名】的默认属性 private 和 protected 属性会被跳过
interface_exists() # 检查某个【接口名】是否 被定义
method_exists()  # 检查一个类/对象是否定义某个方法
?>

<?php
interface ICurrencyConverter
{
    public function convert($currency, $amount);
}

class GBPCurrencyConverter implements ICurrencyConverter
{
    public $name = "GBPCurrencyConverter";
    public $rates = array("USD" => 0.622846,
                          "AUD" => 0.643478);
    protected $var1;
    private $var2;

    function __construct() {}

    function convert($currency, $amount) {
        return $rates[$currency] * $amount;
    }
}

if (interface_exists("ICurrencyConverter")) {
    echo "ICurrencyConverter interface exists.n";
}

$classes = get_declared_classes();
echo "The following classes are available:n";
print_r($classes);

if (in_array("GBPCurrencyConverter", $classes)) {
    print "GBPCurrencyConverter is declared.n";
 
    $gbpConverter = new GBPCurrencyConverter();

    $methods = get_class_methods($gbpConverter);
    echo "The following methods are available:n";
    print_r($methods);

    $vars = get_class_vars("GBPCurrencyConverter");
    echo "The following properties are available:n";
    print_r($vars);

    echo "The method convert() exists for GBPCurrencyConverter: ";
    var_dump(method_exists($gbpConverter, "convert"));
}
输出如下:

ICurrencyConverter interface exists.
The following classes are available:
Array
(
    [0] => stdClass
    [1] => Exception
    [2] => ErrorException
    [3] => Closure
    [4] => DateTime
    [5] => DateTimeZone
    [6] => DateInterval
    [7] => DatePeriod
    ...
    [154] => GBPCurrencyConverter
)
GBPCurrencyConverter is declared.
The following methods are available:
Array
(
    [0] => __construct
    [1] => convert
)
The following properties are available:
Array
(
    [name] => GBPCurrencyConverter
    [rates] => Array
        (
            [USD] => 0.622846
            [AUD] => 0.643478
        )
)
?>

PHP 反射类(Reflection)API
PHP通过他的Reflection类 API支持反射. 就像你在php官方手册中看到的, 
Reflection类API比 introspection 方法更慷慨，提供了大量的类(classes)和(methods)
可以用来完成反射任务. ReflectionClass 是 API的主类 并用于对类，接口和方法应用反射，
并提取有关所有类组件的信息。Reflection在应用程序代码中很容易实现，像内省一样也很直观。

下面有个例子来解释 reflection使用相同的定义 ICurrencyConverter 接口
以及子类 GBPCurrencyConverter:

<?php
$child = new ReflectionClass("Child");
$parent = $child->getParentClass();
echo $child->getName() . " is a subclass of " . $parent->getName() . ".n";

$reflection = new ReflectionClass("GBPCurrencyConverter");
$interfaceNames = $reflection->getInterfaceNames();
if (in_array("ICurrencyConverter", $interfaceNames)) {
    echo "GBPCurrencyConverter implements ICurrencyConverter.n";
}

$methods = $reflection->getMethods();
echo "The following methods are available:n";
print_r($methods);

if ($reflection->hasMethod("convert")) {
    echo "The method convert() exists for GBPCurrencyConverter.n";
}
?>

下面是输出:

Child is a subclass of Introspection.
GBPCurrencyConverter implements ICurrencyConverter.
The following methods are available:
Array
(
    [0] => ReflectionMethod Object
        (
            [name] => __construct
            [class] => GBPCurrencyConverter
        )

    [1] => ReflectionMethod Object
        (
            [name] => convert
            [class] => GBPCurrencyConverter
        )

)
?>

getInterfaceNames() 返回当前类实例化的接口的名字. 
getParentClass() 返回一个 ReflectionClass object representation of the parent class or false if there is no parent. To list the name of the ReflectionClass object, you use the getName() method, as you have seen in the above code.

The getMethods() method retrieves an array of methods and can take as an optional argument a bitmask combination of ReflectionMethod::IS_STATIC, IS_PUBLIC, IS_PROTECTED, IS_PRIVATE, IS_ABSTRACT, and IS_FINAL to filter the list based on visibility.

The Reflection API provides a good implementation of reflection giving you the ability to create more complex applications, such as ApiGen, though further discussion is beyond this goal of this article.

Summary
In this article you’ve seen how to use PHP’s introspection functions and Refection API to obtain information about classes, interfaces, properties, and methods. The purpose of pulling this information is to gain greater insight into your code at run time and to create complex applications.
