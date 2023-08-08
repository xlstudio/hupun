# HUPUN-API-PHP-SDK-20230808
用于对接万里牛 ERP 开放接口的 PHP SDK (B2C 接口 和 OPEN 接口通用)
## 安装方法
```shell
composer require xlstudio/hupun
```
## 使用方法

B2C 商品推送的接口 ( items/open )

```php
use Xlstudio\Hupun\HupunClient;

$hupunClient = new HupunClient('填写你申请的 B2C 的 appKey','填写你申请的 B2C 的 appSecret');
$hupunClient->setGatewayUrl('填写万里牛的 B2C 的 API'); // 万里牛正式环境或测试环境的 B2C API 地址
$hupunClient->setHupunSdkWorkDir('./data/'); // 日志存放的工作目录

$item['shopNick'] = '你的店铺昵称'; // 万里牛 ERP 中 B2C 平台的店铺昵称( 掌柜旺旺/账号 ID )
$item['itemID'] = '商品ID';
$item['title'] = '商品标题';
$item['itemCode'] = '商品编码';
$item['price'] = 100.00; // 单价
$item['itemURL'] = '商品地址';
$item['imageURL'] = '图片地址';
$item['status'] = 1; // 0：已删除，1：在售
$item['createTime'] = time() * 1000; // 创建时间，毫秒级时间戳 (13 位毫秒级)
$item['modifyTime'] = time() * 1000; // 最新修改时间，毫秒级时间戳 (13 位毫秒级)

$item['skus'] = []; // 规格集，如果是单规格需传入 []

$items[] = $item; // 商品集

$params['items'] = json_encode($items); // 商品集 json 串

$result = $hupunClient->execute('items/open', $params, 'post');

var_dump($result);

```

OPEN 商品推送的接口 ( erp/goods/add/item ) [ 注意：OPEN 和 B2C 的接口及密钥需要各自申请，不能混用 ]

```php
use Xlstudio\Hupun\HupunClient;

$hupunClient = new HupunClient('填写你申请的 OPEN 的 appKey','填写你申请的 OPEN 的 appSecret');
$hupunClient->setGatewayUrl('填写万里牛的 OPEN 的 API'); // 万里牛正式环境或测试环境的 OPEN API 地址
$hupunClient->setHupunSdkWorkDir('./data/'); // 日志存放的工作目录

$item['article_number'] = '货号';
$item['item_name'] = '商品名称';
$item['item_code'] = '商品编码';
$item['remark'] = '商品备注';
$item['prime_price'] = 50.00; // 参考进价——如果有规格，会忽略，即使规格集中的没有传
$item['sale_price'] = 100.00; // 标准售价——如果有规格，会忽略，即使规格集中的没有传
$item['item_pic'] = '图片地址';

$params['item'] = json_encode($item); // 商品信息 json 串

$result = $hupunClient->execute('erp/goods/add/item', $params, 'post');

var_dump($result);

```

B2C 单笔查询库存的接口 ( inventories/erp/single )

```php
use Xlstudio\Hupun\HupunClient;

$hupunClient = new HupunClient('填写你申请的 B2C 的 appKey','填写你申请的 B2C 的 appSecret');
$hupunClient->setGatewayUrl('填写万里牛的 B2C 的 API'); // 万里牛正式环境或测试环境的 B2C API 地址
$hupunClient->setHupunSdkWorkDir('./data/'); // 日志存放的工作目录

$params['shop_type'] = 100; // 店铺类型，B2C 平台：100
$params['shop_nick'] = '你的店铺昵称';  // 万里牛 ERP 中 B2C 平台的店铺昵称( 掌柜旺旺/账号 ID )
$params['item_id'] = '商品ID'; // 商品编号，对应商品推送中的 itemID
$params['sku_id'] = '规格ID'; // 如果商品含规格，则必填，对应商品推送的中 skuID

$result = $hupunClient->execute('inventories/erp/single', $params, 'get');

var_dump($result);

```

如果你是使用 Laravel 框架，可以参考使用：
> https://github.com/xlstudio/laravel-hupun

使用本 SDK 过程中如有问题，请联系作者协助解决：[ QQ: 2019809069, WECHAT: 2019809069 ]

请他喝杯咖啡

![喝杯咖啡](buymeacoffee.png)

###### _( 如遇公司没有相关技术人员，可以联系作者沟通技术外包 )_