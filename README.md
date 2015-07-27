# phpLogger
强大的php logger类

# 使用方法：
```php
$logConfig = array(  
  'intLevel' => 0xff,  
  >'strLogFile' => '/data/log/super'.date('Ymd').'.log',  
  >'intMaxFileSize' => 0, //0为不限制  
);  
Logger::create(  
  >$logConfig['intLevel'],  
  >$logConfig['strLogFile'],  
  >$logConfig['intMaxFileSize']  
);  
Logger::setLogId(Logger::getLogId());  
$params = array('get' => $_GET, 'post' => $_POST);  
register_shutdown_function('logFinish', $params);  


function logFinish($in){  
  //这里由于没有做ob_start,如果输出的数据太大超出缓冲区被自动flush出去的话, ob_get_contents拿到的数据就是空的  
  //日志里的out就是空  
  Logger::notice(  
    '',  
    0,  
    array('in' => json_encode($in), 'out' => ob_get_contents())  
  );  
}  
```
