# hupun-open-api-php-sdk
对接万里牛 ERP 开放接口的 PHP SDK
## 安装方法
```shell
composer require xlstudio/hupun dev-master
```
## 使用方法
单笔查询库存的接口
```php
use Xlstudio\Hupun\HupunClient;

$hupunClient = new HupunClient('填写你申请的appKey','填写你申请的appSecret');
$hupunClient->setGatewayUrl('http://114.67.231.99/open/api'); // 万里牛正式环境或测试环境的API地址
$hupunClient->setHupunSdkWorkDir('./data/'); // 日志存放的工作目录

$params['shop_type'] = 100;
$params['shop_nick'] = '你的店铺昵称';
$params['item_id'] = '1';
$params['sku_id'] = '111-1';

var_dump($hupunClient->execute('/inventories/erp/single', $params, 'get'));
```
如果你是使用 Laravel 框架，可以参考使用：
> https://github.com/xlstudio/laravel-hupun

如有不明白的地方，请联系[QQ：2019809069]