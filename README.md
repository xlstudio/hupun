# hupun-openapi-php-sdk
万里牛ERP开放接口的 PHP SDK
## 安装方法
```shell
composer require xlstudio/hupun dev-master
```
## 使用方法
单笔查询库存的接口
```php
use Xlstudio\Hupun\HupunClient;

$hupunclient = new HupunClient('填写你申请的appKey','填写你申请的appSecret');
$hupunclient->gatewayUrl = 'http://103.235.242.21/open/api'; // 测试环境，正式环境可以不需要
$hupunclient->hupunSdkWorkDir = './data/'; // 日志存放的工作目录

$params['shop_type'] = 100;
$params['shop_nick'] = 'dangkou';
$params['item_id'] = '1';
$params['sku_id'] = '111-1';

var_dump($hupunclient->execute('/inventories/erp/single', $params, 'get'));
```