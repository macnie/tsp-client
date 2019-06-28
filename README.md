TspSDK
------
Tsp系统的HTTP中间件层连接SDK。

`V 1.0.11`

## 使用方法

```php
use Macnie\Tsp\TspClient;

try{
     $config = [
        'gateway'=>'',
        'token'=>'',
        'tablestore'=>[
            'gateway'=>'',
            'appkey'=>'',
            'secret'=>'',
            'database'=>''
        ]
     ];
     $client = new TspClient($config);
     $res = $client->setHost($imei_sn,$host,$port);
     if($res['status'] == 200){
        return true;
     }
}catch(\Excetion $e){
     ......
}
```

## 设置亲情号码

`setFamilies`

```php
$families = [
    [
        'relation'=>'爷爷',
        'mobile'=>'13533333333'
    ],
    [
        'relation'=>'爸爸',
        'mobile'=>'13533333332'
    ]
];

$client->setFamilies($imei_sn,$families);

```
**注意：每次设置，必须把此设备所有的亲情号码传输过来**
