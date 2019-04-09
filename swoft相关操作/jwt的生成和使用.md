swoft精选一（swoft使用jwt实现restful接口验证）

> 本文介绍关于swoft使用jwt的介绍

* [1\.安装依赖](#1%E5%AE%89%E8%A3%85%E4%BE%9D%E8%B5%96)
* [2\.准备数据库](#2%E5%87%86%E5%A4%87%E6%95%B0%E6%8D%AE%E5%BA%93)
  * [2\.1 配置数据库](#21-%E9%85%8D%E7%BD%AE%E6%95%B0%E6%8D%AE%E5%BA%93)
  * [2\.2 认证表准备](#22-%E8%AE%A4%E8%AF%81%E8%A1%A8%E5%87%86%E5%A4%87)
  * [2\.3 生成数据库实体文件](#23-%E7%94%9F%E6%88%90%E6%95%B0%E6%8D%AE%E5%BA%93%E5%AE%9E%E4%BD%93%E6%96%87%E4%BB%B6)
* [3\. 实现登录逻辑](#3-%E5%AE%9E%E7%8E%B0%E7%99%BB%E5%BD%95%E9%80%BB%E8%BE%91)
* [4\. 实现ApiUserDao数据提取](#4-%E5%AE%9E%E7%8E%B0apiuserdao%E6%95%B0%E6%8D%AE%E6%8F%90%E5%8F%96)
* [5\. 实现认证管理](#5-%E5%AE%9E%E7%8E%B0%E8%AE%A4%E8%AF%81%E7%AE%A1%E7%90%86)
* [6\. 实现登录控制器](#6-%E5%AE%9E%E7%8E%B0%E7%99%BB%E5%BD%95%E6%8E%A7%E5%88%B6%E5%99%A8)
  * [6\.1 创建登录控制器](#61-%E5%88%9B%E5%BB%BA%E7%99%BB%E5%BD%95%E6%8E%A7%E5%88%B6%E5%99%A8)
  * [6\.2 配置引入认证服务](#62-%E9%85%8D%E7%BD%AE%E5%BC%95%E5%85%A5%E8%AE%A4%E8%AF%81%E6%9C%8D%E5%8A%A1)
  * [6\.3 访问获取token](#63-%E8%AE%BF%E9%97%AE%E8%8E%B7%E5%8F%96token)
* [7\. 用token换取结果信息](#7-%E7%94%A8token%E6%8D%A2%E5%8F%96%E7%BB%93%E6%9E%9C%E4%BF%A1%E6%81%AF)

文档地址： https://doc.swoft.org/master/zh-CN/auth/index.html

### 1.安装依赖

```
composer require swoft/auth
```

### 2.准备数据库

#### 2.1 配置数据库

找到 `config/properties/db.php` 修改相应的数据可的链接地址和主机
我这里 数据库是 `test` 
```php
 'uri'         => [
	    '127.0.0.1:3306/test?user=root&password=123456&charset=utf8',
	],
```

#### 2.2 认证表准备

```sql
CREATE TABLE `api_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户id',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(40) NOT NULL DEFAULT '' COMMENT '密码',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='api用户表';
```

#### 2.3 生成数据库实体文件

生成`Entity`实体文件 'app/Models/Entity/ApiUser'

```
php bin/swoft entity:create -d test -i api_user
```

### 3. 实现登录逻辑

创建 `app/Models/Logic/ApiAuthLogic.php`

```php
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/9
 * Time: 14:14
 */

namespace App\Models\Logic;


use App\Models\Dao\ApiUserDao;
use App\Models\Entity\ApiUser;
use Swoft\Auth\Bean\AuthResult;
use Swoft\Auth\Mapping\AccountTypeInterface;
use Swoft\Bean\Annotation\Bean;
use Swoft\Bean\Annotation\Inject;

/**
 * @Bean()
 * @package App\Models\Logic
 */
class ApiAuthLogic implements AccountTypeInterface
{
    /**
     * @Inject()
     * @var ApiUserDao
     */
    protected $dao;
    const ID = 'api_id';

    /**
     * 用户登录认证 返回 AuthResult 认证对象
     * @param array $data
     * @return AuthResult
     */
    public function login(array $data): AuthResult
    {
        $account = $data['account'];
        $password = $data['password'];
        $user = $this->dao->getByConditions(['name'=>$account]);

        $authResult = new AuthResult();
        if ($user instanceof ApiUser && $this->dao->verifyPassword($user,$password)) {
            $authResult->setIdentity($user->getId());
            $authResult->setExtendedData([self::ID=>$user->getId()]);
        }
        return $authResult;

    }

    /**
     * 认证id是否存在
     * @param string $identity
     * @return bool
     */
    public function authenticate(string $identity): bool
    {
        return $this->dao->exists($identity);
    }
}
```

`AccountTypeInterface`接口约定了:
. jwt签发token : `login` 方法，返回的对象 `AuthResult` 包含了两个字段
   `setIdentity`和 `getIdentity` 对应 `sub`，即jwt的签发对象，一般使用id即可
   `setExtendedData` 和 `getExtendedData` 对应 `payload` 即jwt的载荷，存储一些非敏感信息即可

. jwt验证token ：`authenticate` 认证token是否有效


注意：swoft实现依赖注入和对象实例化是通过注解 实现的，而注解能正常解析的前提是声明了相应的依赖，此处的 `$dao`
声明了`@Inject()`和`@var ApiUserDao`后，swoft会自动实例化 `ApiUserDao`对象并赋值给 `$dao`，所以没有显示声明实例化赋值也是正确的

### 4. 实现ApiUserDao数据提取

创建 `app/Models/Logic/ApiUserDao.php`

```php
<?php
/**
 * This file is part of Swoft.
 *
 * @link https://swoft.org
 * @document https://doc.swoft.org
 * @contact group@swoft.org
 * @license https://github.com/swoft-cloud/swoft/blob/master/LICENSE
 */

namespace App\Models\Dao;

use App\Models\Entity\ApiUser;
use Swoft\Bean\Annotation\Bean;

/**
 * @Bean()
 */
class ApiUserDao
{
    /**
     * @param array $conditions
     * @return \Swoft\Core\ResultInterface
     */
    public function getByConditions(array $conditions) : ApiUser
    {
        $result  = ApiUser::findOne($conditions);
        if (empty($result)) {
            return null;
        }
        return $result->getResult();
    }

    /**
     * @param ApiUser $user
     * @param string $password
     * @return bool
     */
    public function verifyPassword(ApiUser $user, string $password): bool
    {
        return $user->getPassword() == md5($password);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function exists(string $id): bool
    {
        $user = ApiUser::findById($id);
        return empty($user) ? false : true;
    }
}
```

### 5. 实现认证管理

创建 `app/Services/AuthManagerService.php`

```php
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/9
 * Time: 14:47
 */

namespace App\Services;


use App\Models\Logic\ApiAuthLogic;
use Swoft\Auth\AuthManager;
use Swoft\Auth\Bean\AuthSession;
use Swoft\Auth\Mapping\AuthManagerInterface;
use Swoft\Bean\Annotation\Bean;
use Swoft\Redis\Redis;

/**
 * @Bean()
 * Class AuthManagerService
 * @package App\Services
 */
class AuthManagerService extends AuthManager implements AuthManagerInterface
{
    /**
     * 缓存类
     * @var string
     */
    protected $cacheClass = Redis::class;

    /**
     * jwt 具有自包含的特性 能自己描述自身何时过期 但只能一次性签发
     * 用户主动注销后 jwt 并不能立即失效 所以我们可以设定一个 jwt 键名的 ttl
     * 这里使用是否 cacheEnable 来决定是否做二次验证
     * 当获取token并解析后，token 的算法层是正确的 但如果 redis 中的 jwt 键名已经过期
     * 则可认为用户主动注销了 jwt，则依然认为 jwt 非法
     * 所以我们需要在用户主动注销时，更新 redis 中的 jwt 键名为立即失效
     * 同时对 token 刷新进行验证 保证当前用户只有一个合法 token 刷新后前 token 立即失效
     * @var bool 开启缓存
     */
    protected $cacheEnable = true;

    // token 有效期 7 天
    protected $sessionDuration = 86400 * 7;

    /**
     * 定义登录认证方法 调用 Swoft的AuthManager@login 方法进行登录认证 签发token
     * @param string $account
     * @param string $password
     * @return AuthSession
     */
    public function auth(string $account, string $password): AuthSession
    {
        // AuthLogic 需实现 AccountTypeInterface 接口的 login/authenticate 方法
        return $this->login(ApiAuthLogic::class, [
            'account' => $account,
            'password' => $password
        ]);
    }
}
```

`config/properties/cache.php`配置对应的redis 
```php
 'uri'         => [
	    '192.168.0.97:6379',
	],
```


### 6. 实现登录控制器

#### 6.1 创建登录控制器

创建 `app/Controllers/Api/AuthController.php`

```php
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/9
 * Time: 14:55
 */

namespace App\Controllers\Api;

use App\Services\AuthManagerService;
use Swoft\Bean\Annotation\Inject;
use Swoft\Bean\Annotation\Strings;
use Swoft\Bean\Annotation\ValidatorFrom;
use Swoft\Http\Message\Server\Request;
use Swoft\Http\Server\Bean\Annotation\Controller;
use Swoft\Http\Server\Bean\Annotation\RequestMapping;
use Swoft\Http\Server\Bean\Annotation\RequestMethod;

/**
 * Class AuthController
 * @package App\Controllers\Api
 * @Controller("v1/auth")
 */
class AuthController
{
    /**
     * @Inject()
     * @var AuthManagerService
     */
    protected $authManagerService;

    /**
     * 用户登录
     * @RequestMapping(route="login", method={RequestMethod::POST})
     * @Strings(from=ValidatorFrom::POST, name="account", min=6, max=11, default="", template="帐号需{min}~{max}位,您提交的为{value}")
     * @Strings(from=ValidatorFrom::POST, name="password", min=6, max=25, default="", template="密码需{min}~{max}位,您提交的为{value}")
     * @param Request $request
     * @return array
     */
    public function login(Request $request): array
    {
        $account  = $request->input('account') ?? $request->json('account');
        $password = $request->input('password') ?? $request->json('password');
        // 调用认证服务 - 登录&签发token
        $session = $this->authManagerService->auth($account, $password);
        // 获取需要的jwt信息
        $data_token = [
            'token'      => $session->getToken(),
            'expired_at' => $session->getExpirationTime()
        ];
        return [
            "err"  => 0,
            "msg"  => 'success',
            "data" => $data_token
        ];
    }
}
```
这里注意：部分 `RequestMethod` 和 `ValidatorFrom` 在书写的时候不会自动引入，需要手动引入.
`Request` 引人的是 `Swoft\Http\Message\Server\Request` 库，不是其他库

#### 6.2 配置引入认证服务

`config/beans/base.php` 加入全局中间件认证并引入服务提供类

```php
 'serverDispatcher' => [
    'middlewares' => [
        \Swoft\Auth\Middleware\AuthMiddleware::class,
    ]
],
\Swoft\Auth\Mapping\AuthManagerInterface::class => [
    'class' => \App\Services\AuthManagerService::class
],
```

#### 6.3 访问获取token

然后我们postman访问：
```
http://127.0.0.1/v1/auth/login
```
并加上post参数：
```json
{
	"account" : "admin1",
	"password" : "123456"
}
```
获取到相应的token结果：

```json
{
    "err": 0,
    "msg": "success",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJBcHBcXE1vZGVsc1xcTG9naWNcXEFwaUF1dGhMb2dpYyIsInN1YiI6IjEiLCJpYXQiOjE1NTQ3OTczMjgsImV4cCI6MTU1NTQwMjEyOCwiZGF0YSI6eyJhcGlfaWQiOjF9fQ.yoczPRO2KIHWr4bYEpxNWYLxRroLKH9U8mvsIiyDEvg",
        "expired_at": 1555402128
    }
}
```

### 7. 用token换取结果信息

创建 `app/Controllers/Api/CompanyController.php`

```php
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/8
 * Time: 17:07
 */

namespace App\Controllers\Api;

use Swoft\Auth\Middleware\AuthMiddleware;
use Swoft\Http\Message\Bean\Annotation\Middleware;
use Swoft\Http\Message\Bean\Annotation\Middlewares;
use Swoft\Http\Server\Bean\Annotation\Controller;
use Swoft\Http\Server\Bean\Annotation\RequestMapping;
use Swoft\Http\Server\Bean\Annotation\RequestMethod;


/**
 * RESTFUL 获取参数
 * @Controller(prefix="/cpy")
 */
class CompanyController
{
    /**
     * 查询列表接口
     *
     * @RequestMapping(route="/cpy",method={RequestMethod::GET})
     * @Middlewares({
     *     @Middleware(AuthMiddleware::class)
     * })
     */
    public function list()
    {
        return ['公司甲','公司乙'];
    }

    /**
     * 获取单个信息
     *
     * @RequestMapping(route="{cid}",method={RequestMethod::GET})
     * @param int $cid
     * @return array
     */
    public function getCompany(int $cid)
    {
        return ['cid'=>$cid];
    }

}
```

使用psotman访问：

```
http://127.0.0.1/cpy
```
添加认证头部
```
Authorization : Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJBcHBcXE1vZGVsc1xcTG9naWNcXEFwaUF1dGhMb2dpYyIsInN1YiI6IjEiLCJpYXQiOjE1NTQ3OTczMjgsImV4cCI6MTU1NTQwMjEyOCwiZGF0YSI6eyJhcGlfaWQiOjF9fQ.yoczPRO2KIHWr4bYEpxNWYLxRroLKH9U8mvsIiyDEvg
```

打印结果：

```json
[
    "公司甲",
    "公司乙"
]
```

