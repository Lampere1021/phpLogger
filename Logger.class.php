<?php


class Logger
{
    const LOG_LEVEL_NONE = 0x00;
    const LOG_LEVEL_FATAL = 0x01;
    const LOG_LEVEL_WARNING = 0x02;
    const LOG_LEVEL_NOTICE = 0x04;
    const LOG_LEVEL_TRACE = 0x08;
    const LOG_LEVEL_DEBUG = 0x10;
    const LOG_LEVEL_STATISTIC = 0x20;
    const LOG_LEVEL_ALL = 0xFF;

    private static $defaultConfig = array(
        'level' => 0XFF,
        'filePath' => './log/super.log',
        'maxFileSize' => 0, //0代表不限制
    );

    public static $levelNames = array(
        self::LOG_LEVEL_NONE => 'NONE',
        self::LOG_LEVEL_FATAL => 'FATAL',
        self::LOG_LEVEL_WARNING => 'WARNING',
        self::LOG_LEVEL_NOTICE => 'NOTICE',
        self::LOG_LEVEL_TRACE => 'TRACE',
        self::LOG_LEVEL_DEBUG => 'DEBUG',
        self::LOG_LEVEL_ALL => 'ALL',
        self::LOG_LEVEL_STATISTIC => 'STATISTIC'
    );

    private $level;
    private $filePath;
    private $logId;
    private $maxFileSize;

    private static $instance = null;

    private function __construct($config)
    {
        date_default_timezone_set("Asia/Shanghai");

        $this->level = intval($config['level']);
        $this->filePath = $config['filePath'];
        // use framework logid as default
        $this->logId = 0;
        $this->maxFileSize = $config['maxFileSize'];

        // try to create if not exists
        $this->createFile();
    }

    public static function create($level = 0XFF, $filePath = './log/super.log', $maxFileSize = 0)
    {
        if (!is_numeric($level) || intval($level) < 0 ||
            !is_string($filePath) ||
            !is_numeric($maxFileSize) || intval($maxFileSize) < 0
        ) {
            throw new \InvalidArgumentException();
        }

        $config = array(
            'level' => $level,
            'filePath' => $filePath,
            'maxFileSize' => $maxFileSize
        );
        self::$instance = new Logger($config);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Logger(self::$defaultConfig);
        }

        return self::$instance;
    }

    public function writeLog($level, $message, $errorNo = 0, $args = array(), $depth = 0)
    {
        if (!is_numeric($level) ||
            !($this->level & $level) ||
            !isset(self::$levelNames[$level]) ||
            !is_string($message) ||
            !is_numeric($errorNo) ||
            !is_array($args) ||
            !is_numeric($depth)
        ) {
            throw new \InvalidArgumentException();
        }

        $messageLevel = self::$levelNames[$level];

        $messageLogFile = $this->filePath;
        if (($level & self::LOG_LEVEL_WARNING) || ($level & self::LOG_LEVEL_FATAL)) {
            $messageLogFile .= '.wf';
        } else {
            if (($level & self::LOG_LEVEL_STATISTIC)) {
                $messageLogFile .= '.st';
            }
        }

        $trace = debug_backtrace();
        if ($depth >= count($trace)) {
            $depth = count($trace) - 1;
        }
        $file = basename($trace[$depth]['file']);
        $line = $trace[$depth]['line'];

        $arrStrArgs = array();
        if (is_array($args) && count($args) > 0) {
            foreach ($args as $key => $value) {
                $arrStrArgs[] = "{$key}=$value";
            }
        }
        $messageArgs = implode("||", $arrStrArgs);
        $messageArgs = empty($messageArgs) ? '' : $messageArgs . '||';

        $message = sprintf(
            "[%s]||logId=%d||time=%s||line=%s: +%d||errno=%d||ip=%s||uri=%s||%smsg=%s\n",
            $messageLevel,
            $this->logId,
            date('Y-m-d H:i:s', time()),
            $file,
            $line,
            $errorNo,
            self::getClientIP(),
            isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            $messageArgs,
            $message
        );

        if ($this->maxFileSize > 0) {
            clearstatcache();
            $arrFileStats = stat($messageLogFile);
            if (is_array($arrFileStats) && floatval($arrFileStats['size']) > $this->maxFileSize) {
                unlink($messageLogFile);
            }
        }

        return file_put_contents($messageLogFile, $message, FILE_APPEND);
    }

    public static function debug($message, $errorNo = 0, $args = array(), $depth = 0)
    {
        $log = Logger::getInstance();
        return $log->writeLog(self::LOG_LEVEL_DEBUG, $message, $errorNo, $args, $depth + 1);
    }

    public static function trace($message, $errorNo = 0, $args = array(), $depth = 0)
    {
        $log = Logger::getInstance();
        return $log->writeLog(self::LOG_LEVEL_TRACE, $message, $errorNo, $args, $depth + 1);
    }

    public static function notice($message, $errorNo = 0, $args = array(), $depth = 0)
    {
        $log = Logger::getInstance();
        return $log->writeLog(self::LOG_LEVEL_NOTICE, $message, $errorNo, $args, $depth + 1);
    }

    public static function warning($message, $errorNo = 0, $args = array(), $depth = 0)
    {
        $log = Logger::getInstance();
        return $log->writeLog(self::LOG_LEVEL_WARNING, $message, $errorNo, $args, $depth + 1);
    }

    public static function fatal($message, $errorNo = 0, $args = array(), $depth = 0)
    {
        $log = Logger::getInstance();
        return $log->writeLog(self::LOG_LEVEL_FATAL, $message, $errorNo, $args, $depth + 1);
    }

    public static function statistic($message, $errorNo = 0, $args = array(), $depth = 0)
    {
        $log = Logger::getInstance();
        return $log->writeLog(self::LOG_LEVEL_STATISTIC, $message, $errorNo, $args, $depth + 1);
    }

    public static function setLogId($logId)
    {
        Logger::getInstance()->logId = $logId;
    }

    public static function getClientIP()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENTIP'])) {
            $ip = $_SERVER['HTTP_CLIENTIP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENTIP')) {
            $ip = getenv('HTTP_CLIENTIP');
        } elseif (getenv('REMOTE_ADDR')) {
            $ip = getenv('REMOTE_ADDR');
        } else {
            $ip = '127.0.0.1';
        }

        $pos = strpos($ip, ',');
        if ($pos > 0) {
            $ip = substr($ip, 0, $pos);
        }

        return trim($ip);
    }

    public static function getLogId()
    {
        $requestTime = gettimeofday();
        $logId = intval($requestTime['sec'] * 100000 + $requestTime['usec'] / 10) & 0x7FFFFFFF;
        return $logId;
    }

    private function createFile()
    {
        if (!is_writable($this->filePath)) {
            $dir = dirname($this->filePath);
            if (!is_dir($dir)) {
                if (!mkdir($dir)) {
                    throw new \InvalidArgumentException();
                }
            }
            if (!touch($this->filePath)) {
                throw new \InvalidArgumentException();
            }
        }
    }
}
