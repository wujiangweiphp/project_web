

#### 1. rsa加解密类使用

```php
$data   = array('state' => 'ok', 'data' => array('uid' => 12, 'username' => '南北朝时，陕西延安罕尚义女本分。”贺元帅听了，十分钦佩，便回朝交旨去了'));
$rsa = new RSA();
$string = $rsa->pubEncode($data);
echo $string . "\n";
$rdata = $rsa->pubDecode($string);
var_dump($rdata);

$string = $rsa->privateEncode($data);
echo $string . "\n";
$rdata = $rsa->privateDecode($string);
var_dump($rdata);
```

