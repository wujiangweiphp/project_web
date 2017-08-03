php中的设计模式

【单例模式】
适用于资源类型的操作类
如：mysql/redis/memcache/pg 等等
<?php 
class A
{
	static private $_instance = null;
	static public function getInstace($params)
	{
	    if(!self::_instance){
			self::_instance = new A($params);
		}	
		return self::_instance;
	}
}
$a = A::getInstace();

?>

【工厂模式】
适用于创建相同类型的模型类 比较健壮
比如pdo的扩展封装,举个栗子：动物Animals --> cat dog ..我们要看他们say 方法
实际一点：我们封装支付类 支付宝 和 微信支付 以及其他的银行支付
发起预支付 --- H5调起支付 --- 支付成功回调
我们不需要引入所有的类，我们只需要引入一个工厂类 即可

<?php

class Cat
{
	public function __construct($params){}
	public function say(){
		echo 'miao miao';
	}
}

class Dog
{
	public function __construct($params){}
	public function talk(){
		echo 'wang wang';
	}
}

class Animals 
{
	static public function create($class,$params)
	{
		if(!class_exists($class)){
			throw new Exception("class not existis!");
		}
		return new $class($params);
	}
}

$dog = Animals::create('dog');
$dog->say();
$cat = Animals::create('cat');
$cat->talk();

?>

【策略模式】
适用于当一个应用程序需要实现一种特定的服务或者功能，而且该程序有多种实现方式时使用
比如输出格式：json|array|object|serialize|unserialize|xml...
<?php 
interface Output
{
    public function view();	
}
class JSONOutput implements Output
{
	public function view($data)
	{
		return json_encode($data,true);
	}
}
class ArrayOutput implements Output
{
	public function view($data)
	{
		return (array)$data;
	}
}
class OBJOutput implements Output
{
	public function view($data)
	{
		return (object)$data;
	}
}
class SerializeOutput implements Output
{
	public function view($data)
	{
		return serialize($data);
	}
}

class XMLOutput implements Output
{
	private function arrayToXml($arr,$dom=0,$item=0){ 
        if (!$dom){ 
                $dom = new DOMDocument("1.0"); 
        } 
        if(!$item){ 
                $item = $dom->createElement("root"); 
                $dom->appendChild($item); 
        } 
        foreach ($arr as $key=>$val){ 
            $itemx = $dom->createElement(is_string($key)?$key:"item"); 
            $item->appendChild($itemx); 
            if (!is_array($val)){ 
                $text = $dom->createTextNode($val); 
                $itemx->appendChild($text);  
            }else { 
                $this->arrayToXml($val,$dom,$itemx); 
            } 
        } 
        return $dom->saveXML(); 
    } 
	
	public function view($data)
	{
	    return $this->arrayToXml($data);
	}
}

//coding here ... 

class ObjectOutput  
{
	public function __construct($data)
	{
		$this->data = $data;
	}
	
	
	public function setOutput(Output $output)
	{
		$this->output = $output;
	}
	
	public function view()
	{
		return $this->output->view($this-data);
	}
}

$output = new ObjectOutput($data);
$output->setOutput(new XMLOutput());
$output->view();
$output->setOutput(new JSONOutput());
$output->view();

?>
参考Laravel设计模式：http://laravelacademy.org/post/2465.html
参考老外设计模式：https://code.tutsplus.com/tutorials/design-patterns-the-strategy-pattern--cms-22796

