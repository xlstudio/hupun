<?php

namespace Xlstudio\Hupun;

class HupunLogger
{
    public $conf = [
        'separator' => " ",
        'log_file' => '',
    ];

    private $fileHandle;

    protected function getFileHandle()
    {
        if (empty($this->conf['log_file'])) {
            throw new \Exception('没有指定日志文件存放路径');
        }
        $logDir = dirname($this->conf['log_file']);
        if (!is_dir($logDir)) {
            $isCreated = @mkdir($logDir, 0777, true);
            if (false === $isCreated) {
                throw new \Exception('日志文件存放路径无法创建');
            }
        }
        $this->fileHandle = @fopen($this->conf['log_file'], 'a');
        if (false === $this->fileHandle) {
            throw new \Exception('指定的日志文件无法打开');
        }

        return $this->fileHandle;
    }

    public function log($logData)
    {
        if (empty($logData)) {
            return false;
        }
        if (is_array($logData)) {
            $logData = implode($this->conf['separator'], $logData);
        }
        $logData = $logData . "\n";
        if (@is_resource($this->getFileHandle())) {
            fwrite($this->fileHandle, $logData);
            fclose($this->fileHandle);
        } else {
            throw new \Exception('指定的日志文件不正确');
        }
    }
}
