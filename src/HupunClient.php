<?php

namespace Xlstudio\Hupun;

class HupunClient
{
    protected $appKey;

    protected $secretKey;

    protected $gatewayUrl = 'https://erp-open.hupun.com/api';

    protected $format = 'json';

    protected $connectTimeout;

    protected $readTimeout;

    // 日志存放的工作目录
    protected $hupunSdkWorkDir = './data/';

    protected $signMethod = 'md5';

    protected $apiVersion = 'v1';

    protected $sdkVersion = 'hupun-open-api-php-sdk-20190508';

    public function __construct($appKey = '', $secretKey = '', $options = [])
    {
        $this->appKey = $appKey;
        $this->secretKey = $secretKey;

        if ($options) {
            if ($options['api_url']) {
                $this->gatewayUrl = $options['api_url'];
            }
            if ($options['api_work_dir']) {
                $this->hupunSdkWorkDir = $options['api_work_dir'];
            }
        }
    }

    public function setGatewayUrl($gatewayUrl)
    {
        $this->gatewayUrl = $gatewayUrl;
    }

    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function setConnectTimeout($connectTimeout)
    {
        $this->connectTimeout = $connectTimeout;
    }

    public function setReadTimeout($readTimeout)
    {
        $this->readTimeout = $readTimeout;
    }

    public function setHupunSdkWorkDir($hupunSdkWorkDir)
    {
        $this->hupunSdkWorkDir = $hupunSdkWorkDir;
    }

    protected function generateSign($params, $isOpen = false)
    {
        ksort($params);

        $stringToBeSigned = $this->secretKey;
        foreach ($params as $k => $v) {
            if ('@' != substr($v, 0, 1)) {
                if ($isOpen) {
                    $stringToBeSigned .= urlencode($k) . '=' . urlencode($v) . '&';
                } else {
                    $stringToBeSigned .= "$k$v";
                }
            }
            unset($k, $v);
        }
        $stringToBeSigned = rtrim($stringToBeSigned, '&');
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
        curl_setopt($ch, CURLOPT_USERAGENT, 'hupun-openapi-php-sdk');
        // HTTPS 请求
        if (strlen($url) > 5 && 'https' == strtolower(substr($url, 0, 5))) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (null != $postFields && is_array($postFields) && 0 < count($postFields)) {
            $postBodyString = '';
            $postMultipart = false;

            foreach ($postFields as $k => $v) {
                if ('@' != substr($v, 0, 1)) {// 判断是不是文件上传
                    $postBodyString .= urlencode($k) . '=' . urlencode($v) . '&';
                } else {// 文件上传用 multipart/form-data，否则用 www-form-urlencoded
                    $postMultipart = true;
                    if (class_exists('\CURLFile')) {
                        $postFields[$k] = new \CURLFile(substr($v, 1), '', '');
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

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new \Exception($response, $httpStatusCode);
            }
        }

        curl_close($ch);

        return $response;
    }

    public function curlWithMemoryFile($url, $postFields = null, $fileFields = null)
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
        curl_setopt($ch, CURLOPT_USERAGENT, 'hupun-open-api-php-sdk');
        // HTTPS 请求
        if (strlen($url) > 5 && 'https' == strtolower(substr($url, 0, 5))) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        // 生成分隔符
        $delimiter = '-------------' . uniqid();

        // 先将 POST 的普通数据生成主体字符串
        $data = '';

        if (null != $postFields) {
            foreach ($postFields as $name => $content) {
                $data .= '--' . $delimiter . "\r\n";
                $data .= 'Content-Disposition: form-data; name="' . $name . '"';
                // multipart/form-data 不需要 urlencode，参见 http://stackoverflow.com/questions/6603928/should-i-url-encode-post-data
                $data .= "\r\n\r\n" . $content . "\r\n";
            }
            unset($name, $content);
        }

        // 将上传的文件生成主体字符串
        if (null != $fileFields) {
            foreach ($fileFields as $name => $file) {
                $data .= '--' . $delimiter . "\r\n";
                $data .= 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $file['name'] . "\" \r\n";
                $data .= 'Content-Type: ' . $file['type'] . "\r\n\r\n"; // 多了个文档类型

                $data .= $file['content'] . "\r\n";
            }
            unset($name, $file);
        }

        // 主体结束的分隔符
        $data .= '--' . $delimiter . '--';

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/form-data; boundary=' . $delimiter,
            'Content-Length: ' . strlen($data),
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        unset($data);

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (in_array($httpStatusCode, [200, 403])) {
                throw new \Exception($response, $httpStatusCode);
            }
        }

        curl_close($ch);

        return $response;
    }

    protected function logCommunicationError($request, $requestUrl, $errorCode, $responseTxt)
    {
        $localIp = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'CLI';
        $logger = new HupunLogger();
        $logger->conf['log_file'] = rtrim($this->hupunSdkWorkDir, '\\/') . '/' . 'logs/hupun_comm_err_' . $this->appKey . '_' . date('Y-m-d') . '.log';
        $logger->conf['separator'] = '^_^';
        $logData = [
            date('Y-m-d H:i:s'),
            $request,
            $this->appKey,
            $localIp,
            PHP_OS,
            $this->apiVersion,
            $requestUrl,
            $errorCode,
            str_replace("\n", '', $responseTxt),
        ];
        $logger->log($logData);
    }

    public function execute($request, $params, $method = 'post', $bestUrl = null)
    {
        $isOpen = false;
        $apiParams = [];
        $apiParams = $params;

        if ('erp' == substr($request, 1, 3)) {
            $isOpen = true;
            $this->gatewayUrl = rtrim($this->gatewayUrl, '/');
            if ('open/api' == substr($this->gatewayUrl, -8)) {
                $this->gatewayUrl = str_replace('open/api', 'api', $this->gatewayUrl);
            }

            $requestMethod = ltrim($request, '/');

            // 组装系统参数
            $sysParams['_app'] = $this->appKey;
            $sysParams['_s'] = '';
            $sysParams['_t'] = $this->getMillisecond();

            // 签名
            $sysParams['_sign'] = $this->generateSign(array_merge($sysParams, $apiParams), true);
        } else {
            $requestMethod = $this->apiVersion . $request;
            // 组装系统参数
            $sysParams['app_key'] = $this->appKey;
            $sysParams['format'] = $this->format;
            $sysParams['timestamp'] = $this->getMillisecond();
            // 签名
            $sysParams['sign'] = $this->generateSign(array_merge($sysParams, $apiParams));
        }

        // 系统参数放入 GET 请求串
        if ($bestUrl) {
            $requestUrl = $bestUrl . '/' . $requestMethod . '?';
        } else {
            $requestUrl = $this->gatewayUrl . '/' . $requestMethod . '?';
        }
        $curlParams = $fileFields = [];
        if ($isOpen) {
            $mergeParams = array_merge($sysParams, $apiParams);
            foreach ($mergeParams as $key => $value) {
                if (is_array($value) && isset($value['type']) && isset($value['content'])) {
                    $value['name'] = $key;
                    $fileFields[$key] = $value;
                    unset($mergeParams[$key]);
                } elseif ('get' == $method) {
                    $requestUrl .= urlencode($key) . '=' . urlencode($value) . '&';
                }
            }
            $curlParams = $mergeParams;
        } else {
            foreach ($sysParams as $sysParamKey => $sysParamValue) {
                $requestUrl .= $sysParamKey . '=' . urlencode($sysParamValue) . '&';
            }
            foreach ($apiParams as $key => $value) {
                if (is_array($value) && isset($value['type']) && isset($value['content'])) {
                    $value['name'] = $key;
                    $fileFields[$key] = $value;
                    unset($apiParams[$key]);
                } elseif ('get' == $method) {
                    $requestUrl .= $key . '=' . urlencode($value) . '&';
                }
            }
            $curlParams = $apiParams;
        }

        $requestUrl = substr($requestUrl, 0, -1);

        // 发起 HTTP 请求
        try {
            if (count($fileFields) > 0) {
                $resp = $this->curlWithMemoryFile($requestUrl, $curlParams, $fileFields);
            } elseif ('get' == $method) {
                $resp = $this->curl($requestUrl);
            } else {
                $resp = $this->curl($requestUrl, $curlParams);
            }
        } catch (\Exception $e) {
            $this->logCommunicationError($request, $requestUrl, 'HTTP_ERROR_' . $e->getCode(), $e->getMessage());
            $result = new \stdClass();
            $result->success = false;
            $result->error_code = $e->getCode();
            $result->error_msg = $e->getMessage();

            return $result;
        }

        unset($apiParams);
        unset($fileFields);

        // 解析 HUPUN 返回结果
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

        // 返回的 HTTP 文本不是标准 JSON 或者 XML，记下错误日志
        if (false === $respWellFormed) {
            $this->logCommunicationError($request, $requestUrl, 'HTTP_RESPONSE_NOT_WELL_FORMED', $resp);
            $result = new \stdClass();
            $result->success = false;
            $result->error_code = '0';
            $result->error_msg = 'HTTP_RESPONSE_NOT_WELL_FORMED';

            return $result;
        }

        if (isset($respObject->code)) {
            $result = new \stdClass();
            if ($respObject->code) {
                $result->success = false;
                $result->error_code = $respObject->code;
                $result->error_msg = $respObject->message;
            } else {
                $result->success = true;
                $result->response = $respObject->data;
            }
            $respObject = $result;
        }

        // 如果 HUPUN 返回了错误码，记录到业务错误日志中
        if (!empty($respObject->error_code) || !empty($respObject->code)) {
            $logger = new HupunLogger();
            $logger->conf['log_file'] = rtrim($this->hupunSdkWorkDir, '\\/') . '/' . 'logs/hupun_biz_err_' . $this->appKey . '_' . date('Y-m-d') . '.log';
            $logger->log([
                date('Y-m-d H:i:s'),
                $request,
                json_encode($params),
                $requestUrl,
                $resp,
            ]);
        }

        return $respObject;
    }

    public function getMillisecond()
    {
        list($microFirst, $microSecond) = explode(' ', microtime());

        return (float) sprintf('%.0f', (floatval($microFirst) + floatval($microSecond)) * 1000);
    }
}
