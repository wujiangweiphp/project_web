<?php
/**
 * Class RSA 公私钥加解密
 * 公私钥的加解密方法
 *  生成私钥 : openssl genrsa -out cba.key 1024
 *  生成对应的公钥 ：openssl rsa -in cba.key -pubout -out cba_pub.key
 */
namespace RSA;

class RSA
{

    const RSA_PRIVATE = '-----BEGIN RSA PRIVATE KEY-----
MIICWwIBAAKBgQCwhYqQulRYrfagC9i5xN/DKL7F1FLw68BcERPEfxmNzoI5MNtm
g3Oji37t0ivU3WPkbosm8gkM2YegLbBabP+KShYlkTyQxsEXBUc0zB86dOjGqIRh
AvNsxOYclBhYtGQlhDFqv4ffmXdjkMbzVUnE+srHWxcelAtYgyQsy8I4qwIDAQAB
AoGAazPe0EBo4yZbZh1rtH5NCp/fJBPrfycdvowcfjRWV5m32nXCeQKSIxufrVz7
g54kgWFlHvTU7DnbtTqqJHCXy+gIsClrFWeXdBX2LjU1TwuBMwnQC30DoocaY9Z1
5FXdXENSXoTPQLRDs/9dKGlpSnijNrB+ZSrw3APbBWt/mOECQQDp89JvSdB31rWc
0beUdBlOn1YtKZPnB8mEL54e4VJnEjDkMrlXfwYW65f5LQy8q0yW7KecHQpAsS1w
VcGmCY+xAkEAwSgu2v7LBQaRTfHMnjQ+xsu8Poojh243d48eHs8n08HfHKsv97oW
JcMJvNxZ58eQqJOJVENV6r30XwMtr65hGwJAEjou4QDNPyj5SViFhwlsl1WOr0IY
Bd3zc1sKZLmFZAZkzMKu8gQxg0OjuYQrA+AMvY1+mYkhrVygf6oOxlLkYQJAUu/a
DpZQvfuv8HPelB+CxQE99uyBjOk6T8/X8wqn4zjfgAPROOFiGRzB1aIXyHncF0Yi
NVgkUAL4JsPKniCV+QJAAnCbccD93DvgDarfZj9WniLLZSB0HlEvkUpcI0h4jGqR
wMsMO/fDGmDWVVUCL2ctUhC2LOJ5vjI6P+N9/Gf1NQ==
-----END RSA PRIVATE KEY-----';
    const RSA_PUBLIC = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCwhYqQulRYrfagC9i5xN/DKL7F
1FLw68BcERPEfxmNzoI5MNtmg3Oji37t0ivU3WPkbosm8gkM2YegLbBabP+KShYl
kTyQxsEXBUc0zB86dOjGqIRhAvNsxOYclBhYtGQlhDFqv4ffmXdjkMbzVUnE+srH
WxcelAtYgyQsy8I4qwIDAQAB
-----END PUBLIC KEY-----';

    /**
     * @todo: 公钥加密函数
     * @author： friker
     * @date: 2019/3/14
     * @param array $data
     * @return string
     */
    public function pubEncode($data = array())
    {
        $string     = json_encode($data);
        $key       = openssl_pkey_get_public(self::RSA_PUBLIC);
        $trunkData = str_split($string,117);
        $encodeStr = '';
        foreach($trunkData as $trunk){
            openssl_public_encrypt($trunk, $tmpStr , $key);
            $encodeStr .= $tmpStr;
        }
        return base64_encode($encodeStr);
    }

    /**
     * @todo: 对应的公钥加密的解密函数（私钥解密）
     * @author： friker
     * @date: 2019/3/14
     * @param string $str
     * @return mixed|string
     */
    public function pubDecode($str = '')
    {
        $encodeStr     = base64_decode($str);
        $key       = openssl_pkey_get_private(self::RSA_PRIVATE);
        $trunkData = str_split($encodeStr,128);
        $decodeStr = '';
        foreach($trunkData as $trunk){
            openssl_private_decrypt($trunk, $tmpStr, $key);
            $decodeStr .= $tmpStr;
        }
        return json_decode($decodeStr,true);
    }

    /**
     * @todo: 私钥加密函数
     * @author： friker
     * @date: 2019/3/14
     * @param array $data
     * @return string
     */
    public function privateEncode($data = array())
    {
        $string     = json_encode($data);
        $key       = openssl_pkey_get_private(self::RSA_PRIVATE);
        $trunkData = str_split($string,117);
        $encodeStr = '';
        foreach($trunkData as $trunk){
            openssl_private_encrypt($trunk, $tmpStr , $key);
            $encodeStr .= $tmpStr;
        }
        return base64_encode($encodeStr);
    }

    /**
     * @todo: 私钥解密函数（公钥解密）
     * @author： friker
     * @date: 2019/3/14
     * @param string $str
     * @return mixed
     */
    public function privateDecode($str = '')
    {
        $encodeStr     = base64_decode($str);
        $key       = openssl_pkey_get_public(self::RSA_PUBLIC);
        $trunkData = str_split($encodeStr,128);
        $decodeStr = '';
        foreach($trunkData as $trunk){
            openssl_public_decrypt($trunk, $tmpStr, $key);
            $decodeStr .= $tmpStr;
        }
        return json_decode($decodeStr,true);
    }
}
