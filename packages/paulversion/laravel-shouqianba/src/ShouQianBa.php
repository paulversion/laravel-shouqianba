<?php

namespace Paulversion\LaravelShouQianBa;
use Illuminate\Config\Repository;
use GuzzleHttp\Client;
class ShouQianBa
{
    private string $publicKey;

    private string $privateKey;

    private string $domainUrl;

    /**
     * 构造方法
     */
    public function __construct(Repository $config)
    {
        $this->publicKey  = $config->get('shouqianba')['public_key'];
        $this->privateKey = $config->get('shouqianba')['private_key'];
        $this->domainUrl  = $config->get('shouqianba')['domain_url'];
    }

    /**
     * @param array $requestData
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function purchase()
    {
       $date    = New \DateTime(date('Y-m-d H:i:s'), new \DateTimeZone('Asia/Shanghai'));
        $saleAt = $date->format(\DateTime::ATOM);
        $requestData =  [
            "request"=>[
                "head"=>[
                    "appid"=>"28lpm0000002", //应用授权号，由商户入网后收钱吧提供
                    "sign_type"=>"SHA1",
                    "request_time"=>date('c',time()),    //请求时间
                    "reserve"=>"{}",                    //业务反射参数
                    "version"=>"1.0.0",     //被调用方的接口版本号，当前版本1.0.0
                ],
                "body"=>[
                    "request_id"=>$this->getRequestId(),    //请求编号，每次请求必须唯一
                    "brand_code"=>999888,            //品牌编号，系统对接前由“收钱吧”提供
                    "store_sn"=> "ONTEST",                 // 商户门店编号
                    "workstation_sn"=>"0",              //门店收银机编号，如果没有请传入“0”
                    "check_sn"=>'P2305231523202318',          //商户订单号，在商户系统中唯一
                    "sales_time"=>$saleAt,                    //订单创建时间
                    "amount"=>"1",                   //订单价格，精确到分
                    "currency"=>"156",                 //币种， 如：”156”for CNY
                    "subject"=>"zyk",                //订单主题
                    "operator"=>"zyk",                 //操作员，例如“张三“
                    "customer"=>"zyk",                 //客户信息，可以是客户姓名或会员号
                    "pos_info"=>"pos_info",            //本接口对接的对端的信息
                    "industry_code"=> 0,
                ]
            ],
            "signature"=>''
        ];
        $requestData['signature'] = $this->signature($requestData['request']);
        $client = new Client(['verify' => false]);
        $requestUrl = $this->domainUrl.'api/lite-pos/v1/sales/purchase';
        $res = $client->post($requestUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => $requestData,
        ]);
        $res = json_decode($res->getBody()->getContents(),true);
        $verifyResult = $this->verifySignature($res);
    }

    /**
     * format public key
     *
     * @param string $publicKey
     * @return string
     */
    private function formatPublicKey(string $publicKey):string
    {
        $fKey = "-----BEGIN PUBLIC KEY-----\n";
        $len = strlen($publicKey);
        for($i = 0; $i < $len; ) {
            $fKey = $fKey . substr($publicKey, $i, 64) . "\n";
            $i += 64;
        }
        $fKey .= "-----END PUBLIC KEY-----";
        return $fKey;
    }
    /**
     * format private key
     *
     * @param string $privateKey
     * @return string
     */
   private function formatPrivateKey(string $privateKey):string
   {
       $fKey = "-----BEGIN RSA PRIVATE KEY-----\n";
       $len = strlen($privateKey);
       for($i = 0; $i < $len; ) {
           $fKey = $fKey . substr($privateKey, $i, 64) . "\n";
           $i += 64;
       }
       $fKey .= "-----END RSA PRIVATE KEY-----";
       return $fKey;
   }

   private function signature($requestData):string
   {
       $signBody   = stripslashes(json_encode($requestData,JSON_UNESCAPED_UNICODE));
       $privateKey = $this->formatPrivateKey($this->privateKey);
       $privateKey = openssl_pkey_get_private($privateKey);
       openssl_sign($signBody, $signature, $privateKey);
       $sign       = base64_encode($signature);
       return $sign;
   }

    /**
     * check response signature
     *
     * @param array $response
     * @return bool
     */
   private function verifySignature(array $response):bool
   {
       $publicKey    = $this->formatPublicKey($this->publicKey);
       $publicKey    = openssl_pkey_get_public($publicKey);
       $responseData = stripslashes(json_encode($response['response'],JSON_UNESCAPED_UNICODE));
       $signature    = $response['signature'];
       return openssl_verify($responseData, base64_decode($signature),$publicKey,OPENSSL_ALGO_SHA256);
   }

    /**
     * get request id
     *
     * @return string
     */
   private function getRequestId():string
   {
      return uuid_create();
   }
}
