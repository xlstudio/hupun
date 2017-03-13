<?php
namespace Xlstudio\Hunpun;

class HupunClient
{
    public $appkey;

    public $secretKey;

    public $gatewayUrl = 'http://open.hupun.com/api';

    public $format = 'json';

    public $connectTimeout;

    public $readTimeout;

    /** 日志存放的工作目录**/
    public $hupunSdkWorkDir = './data/';

    protected $signMethod = 'md5';

    protected $apiVersion = 'v1';

    protected $sdkVersion = 'hupun-openapi-php-sdk-20170313';

    public function __construct($appkey = '', $secretKey = '')
    {
        $this->appkey = $appkey;
        $this->secretKey = $secretKey ;
    }

    protected function generateSign($params)
    {
        ksort($params);

        $stringToBeSigned = $this->secretKey;
        foreach ($params as $k => $v) {
            if ('@' != substr($v, 0, 1)) {
                $stringToBeSigned .= "$k$v";
            }
        }
        unset($k, $v);
        $stringToBeSigned .= $this->secretKey;

        return strtoupper(md5($stringToBeSigned));
    }

    public function curl($url, $postFields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->readTimeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
        }
        if ($this->connectTimeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }
        curl_setopt ( $ch, CURLOPT_USERAGENT, 'hupun-openapi-php-sdk');
        //https 请求
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (null != $postFields && is_array($postFields) && 0 < count($postFields)) {
            $postBodyString = '';
            $postMultipart = false;

            foreach ($postFields as $k => $v) {
                if (!is_string($v)) {
                    continue;
                }
                if ('@' != substr($v, 0, 1)) {//判断是不是文件上传
                    $postBodyString .= "$k=" . urlencode($v) . '&';
                } else {//文件上传用multipart/form-data，否则用www-form-urlencoded
                    $postMultipart = true;
                    if (class_exists('\CURLFile')) {
                        $postFields[$k] = new \CURLFile(substr($v, 1));
                    }
                }
            }
            unset($k, $v);

            curl_setopt($ch, CURLOPT_POST, true);

            if ($postMultipart) {
                if (class_exists('\CURLFile')) {
                    curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
                } else {
                    if (defined('CURLOPT_SAFE_UPLOAD')) {
                        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
                    }
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            } else {
                $header = ['content-type: application/x-www-form-urlencoded; charset=UTF-8'];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
        }

        $reponse = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new Exception($reponse, $httpStatusCode);
            }
        }

        curl_close($ch);
        return $reponse;
    }
    public function curl_with_memory_file($url, $postFields = null, $fileFields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->readTimeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
        }
        if ($this->connectTimeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, 'hupun-openapi-php-sdk');
        //https 请求
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        //生成分隔符
        $delimiter = '-------------' . uniqid();

        //先将post的普通数据生成主体字符串
        $data = '';

        if (null != $postFields) {
            foreach ($postFields as $name => $content) {
                $data .= '--' . $delimiter . "\r\n";
                $data .= 'Content-Disposition: form-data; name="' . $name . '"';
                //multipart/form-data 不需要urlencode，参见 http:stackoverflow.com/questions/6603928/should-i-url-encode-post-data
                $data .= "\r\n\r\n" . $content . "\r\n";
            }
            unset($name, $content);
        }

        //将上传的文件生成主体字符串
        if (null != $fileFields) {
            foreach ($fileFields as $name => $file) {
                $data .= '--' . $delimiter . "\r\n";
                $data .= 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $file['name'] . "\" \r\n";
                $data .= 'Content-Type: ' . $file['type'] . "\r\n\r\n";//多了个文档类型

                $data .= $file['content'] . "\r\n";
            }
            unset($name, $file);
        }

        //主体结束的分隔符
        $data .= '--' . $delimiter . '--';

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: multipart/form-data; boundary=' . $delimiter,
            'Content-Length: ' . strlen($data))
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $reponse = curl_exec($ch);
        unset($data);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new Exception($reponse, $httpStatusCode);
            }
        }

        curl_close($ch);
        return $reponse;
    }

    protected function logCommunicationError($request, $requestUrl, $errorCode, $responseTxt)
    {
        $localIp = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'CLI';
        $logger = new HupunLogger;
        $logger->conf['log_file'] = rtrim($this->hupunSdkWorkDir, '\\/') . '/' . 'logs/hupun_comm_err_' . $this->appkey . '_' . date('Y-m-d') . '.log';
        $logger->conf['separator'] = '^_^';
        $logData = [
            date('Y-m-d H:i:s'),
            $request,
            $this->appkey,
            $localIp,
            PHP_OS,
            $this->apiVersion,
            $requestUrl,
            $errorCode,
            str_replace("\n", '', $responseTxt)
        ];
        $logger->log($logData);
    }
    public function execute($request, $params, $method = 'post', $bestUrl = null)
    {
        //组装系统参数
        $sysParams['app_key'] = $this->appkey;
        $sysParams['format'] = $this->format;
        $sysParams['timestamp'] = $this->getMillisecond();

        $apiParams = [];
        $apiParams = $params;

        //系统参数放入GET请求串
        if ($bestUrl) {
            $requestUrl = $bestUrl.'/'.$this->apiVersion.$request.'?';
        } else {
            $requestUrl = $this->gatewayUrl.'/'.$this->apiVersion.$request.'?';
        }
        //签名
        $sysParams['sign'] = $this->generateSign(array_merge($apiParams, $sysParams));

        foreach ($sysParams as $sysParamKey => $sysParamValue) {
            // if(strcmp($sysParamKey,'timestamp') != 0)
            $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . '&';
        }

        $fileFields = [];
        foreach ($apiParams as $key => $value) {
            if (is_array($value) && isset($value['type']) && isset($value['content'])) {
                $value['name'] = $key;
                $fileFields[$key] = $value;
                unset($apiParams[$key]);
            } elseif ('get' == $method) {
                $requestUrl .= "$key=" . urlencode($value) . '&';
            }
        }

        // $requestUrl .= 'timestamp=' . urlencode($sysParams['timestamp']) . '&';
        $requestUrl = substr($requestUrl, 0, -1);

        //发起HTTP请求
        try {
            if (count($fileFields) > 0) {
                $resp = $this->curl_with_memory_file($requestUrl, $apiParams, $fileFields);
            } elseif ('get' == $method) {
                $resp = $this->curl($requestUrl);
            } else {
                $resp = $this->curl($requestUrl, $apiParams);
            }
        } catch (Exception $e) {
            $this->logCommunicationError($request, $requestUrl, 'HTTP_ERROR_' . $e->getCode(), $e->getMessage());
            $result->success = false;
            $result->error_code = $e->getCode();
            $result->error_msg = $e->getMessage();
            return $result;
        }

        unset($apiParams);
        unset($fileFields);

        //解析HUPUN返回结果
        $respWellFormed = false;
        if ('json' == $this->format) {
            $respObject = json_decode($resp);
            if (null !== $respObject) {
                $respWellFormed = true;
            }
        } elseif ('xml' == $this->format) {
            $respObject = @simplexml_load_string($resp);
            if (false !== $respObject) {
                $respWellFormed = true;
            }
        }

        //返回的HTTP文本不是标准JSON或者XML，记下错误日志
        if (false === $respWellFormed) {
            $this->logCommunicationError($request, $requestUrl, 'HTTP_RESPONSE_NOT_WELL_FORMED', $resp);
            $result->success = false;
            $result->error_code = 0;
            $result->error_msg = 'HTTP_RESPONSE_NOT_WELL_FORMED';
            return $result;
        }

        //如果HUPUN返回了错误码，记录到业务错误日志中
        if ($respObject->error_code) {
            $logger = new HunpunLogger;
            $logger->conf['log_file'] = rtrim($this->hupunSdkWorkDir, '\\/') . '/' . 'logs/hupun_biz_err_' . $this->appkey . '_' . date('Y-m-d') . '.log';
            $logger->log([
                date('Y-m-d H:i:s'),
                $resp
            ]);
        }
        return $respObject;
    }

    private function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (string)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }
}
