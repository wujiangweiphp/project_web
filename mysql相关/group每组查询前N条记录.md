
# group分组查询每组返回前N条记录

* [group分组查询每组返回前N条记录](#group%E5%88%86%E7%BB%84%E6%9F%A5%E8%AF%A2%E6%AF%8F%E7%BB%84%E8%BF%94%E5%9B%9E%E5%89%8Dn%E6%9D%A1%E8%AE%B0%E5%BD%95)
    * [1\. 表准备](#1-%E8%A1%A8%E5%87%86%E5%A4%87)
    * [2\. 场景及解决方案](#2-%E5%9C%BA%E6%99%AF%E5%8F%8A%E8%A7%A3%E5%86%B3%E6%96%B9%E6%A1%88)
      * [2\.1 查询场景](#21-%E6%9F%A5%E8%AF%A2%E5%9C%BA%E6%99%AF)
      * [2\.2 常见的做法](#22-%E5%B8%B8%E8%A7%81%E7%9A%84%E5%81%9A%E6%B3%95)
      * [2\.3 一次查询union all](#23-%E4%B8%80%E6%AC%A1%E6%9F%A5%E8%AF%A2union-all)
      * [2\.4 优化union all](#24-%E4%BC%98%E5%8C%96union-all)
      * [2\.5 使用group优化](#25-%E4%BD%BF%E7%94%A8group%E4%BC%98%E5%8C%96)


### 1. 表准备

学生表

```sql
CREATE TABLE `student` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `stu_no` varchar(20) NOT NULL DEFAULT '' COMMENT '学号',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '姓名',
  `age` int(10) NOT NULL DEFAULT '0' COMMENT '年龄',
  `gender` tinyint(2) NOT NULL DEFAULT '1' COMMENT '性别 1 男 2 女',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='学生表';
```

```sql
CREATE TABLE `student_score` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `stu_no` varchar(20) NOT NULL DEFAULT '' COMMENT '学号',
  `subject` varchar(20) NOT NULL DEFAULT '' COMMENT '科目',
  `score` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '分数',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `stu_no_index` (`stu_no`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='成绩表';
```

### 2. 场景及解决方案

#### 2.1 查询场景

> 如果我们需要查询学号 `stu001 - stu010` 十位同学的最近3次的数学考试成绩 来进行教学分析

#### 2.2 常见的做法

循环查询

```php
$stu_nos = array('stu001',...,'stu010');
$return_data = array();
foreach($stu_nos as $stu_no){
	$where = array();
	$where['stu_no'] = $stu_no;
	$where['subject'] = '数学';
	$return_data[$stu_no] = M('student_score')->where($where)->order('id desc')->limit(3)->select();	
}
```
> 查询次数为10次 成绩表大概 4W条数据 ，响应时间 1.96s

#### 2.3 一次查询union all

我们知道，查询相应时间包括了： 语句发送时间 + 语句执行时间 + 数据返回时间
我们消耗了10次语句的发送往返时间，可以实现一次发送并返回么，可以

```php
$stu_nos = array('stu001',...,'stu010');
$return_data = array();
$sqls = array();
foreach($stu_nos as $stu_no){
	$where = array();
	$where['stu_no'] = $stu_no;
	$where['subject'] = '数学';
	$sqls[] = M('student_score')->where($where)->order('id desc')->limit(3)->buildSql();	
}
$query_sql = implode(' union all ',$sqls);
$data = M()->table($sql . ' as t')->select();
foreach($stu_nos as $stu_no){
	foreach($data as $val){
	    $return_data[$val['stu_no']][] = $val;
	}
}
```

> 查询次数为1次 成绩表大概 4W条数据 ，响应时间 1.46s

#### 2.4 优化union all

可以看到上面的查询结果比之前的稍微快了一点点，但仍然很慢，我们使用`explain` 执行单条语句

| id | select_type | table | type | prossible_keys | key | key_len | ref | rows | Extra |
| -- | --- | --- | --- | --- | -- | --- | --- | --- | --- |
| 1	| SIMPLE | student_score | all |  |  |  | 39245 |  Using where; Using filesort |

几乎扫描了全表，我们在字段`stu_no` 上添加索引, 然后再执行`explain`

| id | select_type | table | type | prossible_keys | key | key_len | ref | rows | Extra |
| -- | --- | --- | --- | --- | -- | --- | --- | --- | --- |
| 1	| SIMPLE | student_score | range | stu_no | stu_no | 12 | 18500 | Using index condition; Using where; Using filesort |

这回用上索引了，然后扫描行数也少了很多，然后我们再次执行

> 查询次数为1次 成绩表大概 4W条数据 ，响应时间 1.06s

依然很慢，但是比起初始的循环查询，已经快了近一倍，但是给到mysql服务器最终的查询还是做了10次
的拆分查询，我们能不能依赖于 `group`的分组以及'having' 的筛选过滤，来分别查询每组的前3条数据呢


#### 2.5 使用group优化

还真有，原文如下：
https://www.xaprb.com/blog/2006/12/07/how-to-select-the-firstleastmax-row-per-group-in-sql/

我们按照原生的查询，简单构建了一下：

```sql
set @num := 0, @group_by_field := '';

select *,
      @num := if(@group_by_field = stu_no, @num + 1, 1) as row_number,
      @group_by_field := stu_no as dummy
from student_score force index(stu_no)
where stu_no in ('stu001','stu002','stu003','stu004','stu005','stu006','stu007','stu008','stu009','stu010')
group by stu_no
having row_number <= 3;
order by stu_no,id desc
```
解释一下，这里的 `if`是作为函数出现的，传递的三个参数分别是 条件/条件满足返回/条件不满足返回

作者的意思大概是，使用 `stu_no` 作为索引，在数据扫描的时候，本来就是按照 `stu_no`进行的分组，这样一来，就可以根据变量 `@group_by_field `(学号)的改变，来进行计数字段的的改变

实际上，思想是没错的，就是实际操作上，不知道是什么原因，每组返回的结果始终是1条，我删掉
```sql
group by stu_no
having row_number <= 3;
```
这一句后，发现计数器按照正常的预想开始正常计数了，猜想可能是`group`改变的mysql的行扫描策略
所以我们更改sql为

```sql
set @num := 0, @group_by_field := '';

select *
from 
(  select *,
        @num := if(@group_by_field = stu_no, @num + 1, 1) as row_number,
        @group_by_field := stu_no as dummy
  from student_score force index(stu_no)
    where stu_no in ('stu001','stu002','stu003','stu004','stu005','stu006','stu007','stu008','stu009','stu010')
    order by stu_no,id desc
) as t
where row_number > 4 
```

代码如下：


```php

/**
 * @todo: 分组查询 每个分组返回符合条件的前 limit 条
 *        本分组只适合单表查询 不支持多表
 * @author： friker
 * @date: 2019/4/15
 * @param string $table   表名
 * @param array $query_fields   待查询的表的字段
 * @param string $group_field   分组字段
 * @param string $group_field_sort   分组字段排序
 * @param array $where    筛选条件
 * @param string $order_by  排序条件
 * @param int $limit  每个分组查询条数
 * @param string $force_index  使用索引
 * @return mixed
 */
if (!function_exists('getGroupLimitRows')) {
    function getGroupLimitRows(
        $table = '',
        $query_fields = array(),
        $group_field = '',
        $where = array(),
        $order_by = '',
        $limit = 5,
        $group_field_sort = 'asc',
        $force_index = ''
    ) {
        if(empty($table) || empty($query_fields) || empty($group_field)){
            return false;
        }
        $db  = M();
        $sql = ' set @num := 0 ,@group_by_field := \'\' ';
        $db->execute($sql,false);
        if (!stripos($order_by, $group_field)) {
            $order_by = $group_field . ' ' . $group_field_sort . ',' . $order_by;
        }
        if (!in_array($group_field, $query_fields)) {
            array_push($query_fields, $group_field);
        }
        $return_fields = implode(',', $query_fields);
        $row_fields = '@num := if(@group_by_field = ' . $group_field . ' , @num + 1 , 1 ) as row_number , @group_by_field := ' . $group_field . ' AS dummy';
        array_push($query_fields, $row_fields);
        $fields                  = implode(',', $query_fields);
        if(!empty($force_index)){
            $query_sql               = M($table . ' force index('.$force_index.')')->where($where)->field($fields)->order($order_by)->buildSql();
        }else {
            $query_sql               = M($table)->where($where)->field($fields)->order($order_by)->buildSql();
        }
        $new_where               = array();
        $new_where['row_number'] = array('lt', $limit + 1);
        $result                  = $db->table($query_sql .' as t')->where($new_where)->field($return_fields)->select();
        return $result;
    }
}

$table = 'student_score';
$query_fields = array('*');
$group_field = 'stu_no';
$order_by = 'id desc';
$limit = 3;
$group_field_sort = 'asc';
$force_index = 'stu_no';
$where = array();
$where['stu_no'] = array('stu001','stu002','stu003','stu004','stu005','stu006','stu007','stu008','stu009','stu010');
$data = getGroupLimitRows( $table,$query_fields,$group_field,$where,$order_by,$limit,$group_field_sort,$force_index);

foreach($stu_nos as $stu_no){
	foreach($data as $val){
	    $return_data[$val['stu_no']][] = $val;
	}
}
```

> 查询次数为1次 成绩表大概 4W条数据 ，响应时间 0.11s

