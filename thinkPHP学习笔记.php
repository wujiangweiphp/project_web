
thinkPHP入门基础

1.控制器规则
文件名：ArtController.class.php  大驼峰
命名空间及使用：
<?php
   namespace Home/Controller;
   use Think/Controller;
类名：ArtController extend Controller
方法：index 小驼峰

2. 前置或后置方法
_before_index(){} //系统会自动调用
index()
_after_index(){} //系统自动调用

3. 参数
/m/c/method/k1/v1/k2/v2 
$_GET k1=>v1
      k2=>v2
index($k1 = 0) 按变量名

/m/c/method/v1
$_GET 0=>v1
index($k1 = 0) 按顺序

ThinkPHP/Conf/convertion.php
URL_PARAMS_BIND_TYPE 0 按变量名 1 按顺序 获取
一般在Application/Conf/config.php

3. url地址生成 
U('index/test',array('id'=>12),'html')
配置config
'URL_MODEL' => 0
0 普通模式 1 pathinfo 模式 2 rewrite模式 3 兼容模式 默认pathinfo模式

4. 页面跳转
$this->success('成功信息','/Home/index/index',5);
$this->error('出错信息');

$this->redirect('index/index',array('id'=>1),2,'提示信息');

5. ajax返回

$data['msg']='aaa';
$this->ajaxReturn($data);

xml 返回
$this->ajaxReturn($data,'xml');

6.输入参数获取
get/post/cookie/session/server
I('get.name')
I('get.id/s') s 强制转化为string  d 整型数字  f 浮点型  b 布尔  a数组
I('get.id',12) 默认值 12
I('get.name','a','htmlspecialchars') 过滤函数htmlspecialchars

7. 404空方法或者空控制器
_empty(){}  //方法找不到默认进入

EmptyController.class.php
<?
class EmptyController extends Controller
index(){}
//找不到控制器 会自动进入

有用的常量 CONTROLLER_NAME

8. 视图
public function index(){
	$this->display();
}

模块/模板/控制器/方法
‘DEFAULT_V_LAYER’ => 'template'  //模板名称 也就是url第二个参数模板
'TMPL_TEMPLATE_SUFFIX' => '.tpl'  //模板后缀名称
'DEFAULT_THEME' => 'default'  // /View/default/User/add

$this->theme('default')->display();

变量赋值
$this->assign('name',$val);
<==> $this->name = $val;
使用 {$name}
数组 $data.name/$data['name']

缓存模板
$val = $this->fetch(); //存储当前页面内容  可以使用redis 
echo $val(); // 模板内容输出

9. 模型

M('表名称')
//添加
if(IS_POST){
	$stu = M('students');
    $data['name'] = $name;
    $data['no'] = $no;
    $stu->add($data);  //添加 返回id
}
$this->display();
//删除
$stu->where(array('id'=>1))->delete();
//编辑
$stu->where(array('name'=>'w'))->save(array('age'=>5));
//查询
$stu->where(array('id'=>3))->find();
$stu->limit(0,10)->select();
//获取最后一条sql
$stu->getLastSql();
//字段筛选
$stu->field('id,name')->select();

//原生查询
$m = new \Think\Model;
$m->query($sql);

__PREFIX__ . '表名称' //表前缀常量
__表名称大写__ // 等价于上面
$m->execute($sql); //执行更新和插入删除操作

