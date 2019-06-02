## function_score查询

> 函数式分数查询

[TOC]

### 1. function_score的分类

| 分类 | 说明 |
| --- | --- |
| script_score | 脚本分数 |
| weight | 权重分数 |
| random_score | 随机分数 |
| field_value_factor | 字段值因子分数 |
| 衰减函数 | gauss, linear, exp |

### 2. script_score脚本设置分数

```
GET /_search
{
    "query": {
        "function_score": {
            "query": {
                "match": { "message": "elasticsearch" }
            },
            "script_score" : {
                "script" : {
                    "params": {
                        "a": 5,
                        "b": 1.2
                    },
                    "source": "params.a / Math.pow(params.b, doc['likes'].value)"
                }
            }
        }
    }
}
```
分数的值是 script_score.script.source

### 2. weight权重分数

```
"weight" : number
```

### 3. 随机值分数

```
GET /_search
{
    "query": {
        "function_score": {
            "random_score": {
                "seed": 10,
                "field": "_seq_no"
            }
        }
    }
}
```

注意： 随机值分数 在给定种子 seed 时，必须指定字段，否则会占用大量CPU进行运算，最好字段的
值是唯一的 比如系统的序列号 _seq_no (该值可能被更新的时候改变) 或者用户自定义的id (推荐)

### 4. field_value_factor字段值因子

```
GET /_search
{
    "query": {
        "function_score": {
            "field_value_factor": {
                "field": "likes",
                "factor": 1.2,
                "modifier": "sqrt",
                "missing": 1
            }
        }
    }
}
```
分数计算规则如下：

```
sqrt(1.2 * doc[likes].value)
```
这里出现的几个字段说明如下：

| 字段名 | 说明 |
| --- | --- |
| field | 给定的字段名称 参与分数计算 |
| factor | 相乘因子  默认值 1|
| modifier | 修改器 作为 字段与因子乘积后的应用函数 默认值 none |
| missing | 字段不存在时 使用missing的值 作为默认的值参与字段的计算 |

修改器的常用函数如下：

| 修改器 | 函数名 |
| --- | --- |
| none | 不做任何修改 |
| log  | 取字段值的常用对数 |
|log1p | 字段值 + 1 后取常用对数 |
|log2p | 字段值 + 2 后取常用对数 |
| ln   | 取字段的自然对数 |
| ln1p | 字段值 + 1 后取自然对数 |
| ln2p | 字段值 + 2 后取自然对数 |
| square | 字段值平方 （自己乘自己）|
| sqrt | 字段值开平方 |
| reciprocal | 取倒数  1/字段值 |


### 5. 衰减函数 

> 衰减函数的衰减依赖于一个数字字段的值与用户给定的origin的值的差距进行衰减
他类似一个 range范围查询，但是使用了平滑的边缘代替了边界

#### 5.1 衰减函数的定义

对具有数字字段的查询使用距离评分，用户必须为每个字段定义 `origin` 和 `scale`
`origin` : 基础起点 定义距离的中心点
`scale` : 定义衰减率

一个衰减函数的定义格式如下：

```
{
	"query" : {
	   "function_score":{
	       "gauss" :{
	          "field_name" : {
	              "origin":"原始值",  //如果是日期 2013-09-12 如果是地理位置 12.01,13.45
	              "scale":"衰减规模", // 日期 3d 地理位置  2km
	              "offset":"偏移量",
	              "decay":0.5
	          }
	       }
	   }
	}
}
```
【注意】：在日期格式中 原点的日期格式取决于 mapping中定义的日期格式 如未定义原点 则使用当前时间

使用的字段说明
| 字段 | 说明 |
| origin |  用于计算距离的原点 必须是数字或日期以及地理位置字段 |
| scale | 计算得分等于 原点+scale 对于日期默认是毫秒也可以是 1h,1d 对于距离 默认是米 也可以是 1m,1km  其他任意数字类型 可以是任意数字  |
| offset | 如果定义此字段 衰减函数仅计算 大于offset的文档 默认是 0 |
| decay | 定义文档在给定距离处的评分方式 scale |

【注意】原点 ± offset 的范围类不进行衰减，分数值为1 超过此范围，开始进行衰减

支持的衰减函数

gauss 高斯函数 正常衰减  很快趋于0 
exp  指数衰减           始终大于0
linear  线性衰减        直线趋于0


#### 5.2 多个字段衰减 

多字段衰减组合计算分数的函数如下：

| 函数名称  | 说明 | 
| ---- | ---- |
| min | 取最小 |
| max | 取最大 |
| avg | 取平均 |
| sum | 取和 |

通过在衰减函数中添加：

```
"multi_value_mode":"avg"
```
来进行设置


#### 5.3 详细的实例

假设你需要找个酒店，距离和价格是你考虑的因素，然后你希望
价格越低越好 那么 price origin 为0
距离越近越好 那么 location 的orign 则是你的坐标

如果有阳台 那就更nice了，那么我们组合查询如下



```
GET /_search
{
    "query": {
        "function_score": {
          "functions": [
            {
              "gauss": {
                "price": {
                  "origin": "0",
                  "scale": "20"
                }
              }
            },
            {
              "gauss": {
                "location": {
                  "origin": "11, 12",
                  "scale": "500m"
                }
              }
            }
          ],
          "query": {
            "match": {
              "properties": "balcony"
            }
          },
          "score_mode": "multiply"
        }
    }
}
```

### 6. 复合查询概述

```
GET /_search
{
    "query": {
        "function_score": {
          "query": { "match_all": {} },
          "boost": "5", 
          "functions": [
              {
                  "filter": { "match": { "test": "bar" } },
                  "random_score": {}, 
                  "weight": 23
              },
              {
                  "filter": { "match": { "test": "cat" } },
                  "weight": 42
              }
          ],
          "max_boost": 42,
          "score_mode": "max",
          "boost_mode": "multiply",
          "min_score" : 42
        }
    }
}
```

| 参数名称 | 参数说明 |
| ---- | ---- |
| boost | 查询全部时的权重 |
| max_boost | 最大的权重得分不能超过此数 |
| score_mode | 分数计算方式 |
| boost_mode | 权重计算方式 | 
| min_score |  最小分数  |
| functions | 使用的过滤函数 |
| query | 相关性查询语句 |

分数模式score_mode（基于多个函数之间） 计算函数有以下几个：

| 函数名称 | 函数说明 |
| ------ | ----- |
| multiply | 分数相乘 |
| sum | 求和 |
| avg | 平均数 |
| first | 返回第一个匹配的函数的值 |
| max | 返回最大的分数 |
| min | 返回最小的分数 |

比如上面的例子中 两个函数分别返回的分数是 1 和 2 

score_mode是max 则返回 max(1 * 23 , 2 * 42) 作为结果分数返回
是 avg 则返回 (1 * 23 + 2 * 42)/(23+42)

提升模型 boost_mode （基于函数分数 和 查询分数）

| 函数名称 | 函数说明 |
| ------ | ----- |
| multiply | 查询分数和函数分数相乘 |
| replace | 仅仅使用函数分数 查询分数被忽略 |
| sum | 求和 |
| avg | 平均数 |
| max | 返回最大的分数 |
| min | 返回最小的分数 |


{
    "size" : 4,
	"query": {
	    "bool":{
	        "term":{"title_keywords":""}
	    }
	    "function_score":{
	        "functions":[
	            {
	               "gauss":{
	                  "end_time":{
	                    scale:-1800
	                  }
	               }
	            },{
	               "field_value_factor":{
	                  "field":"category_score",
	                  "missing":0
	               }
	            }
	        }
	    ],
	    "score_mode":"avg"
	}
}
