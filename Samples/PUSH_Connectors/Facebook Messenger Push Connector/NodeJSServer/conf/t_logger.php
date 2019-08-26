<?php

use KiamoConnectorSampleToolsFacebookPush\Logger ;

return [
  'behavior'          => [
    'globalLogLevel'     => Logger::LOG_DEBUG,
    'dateFormat'         => '[Ymd_His]',
    'smartMethodName'    => [
      'enabled'             => true,
      'strictLength'        => 50,
    ],
  ],
  'files'             => [
    'folder'             => __DIR__ . '/../logs',
    'obsolete'           => [
      'zipOlderThan'        => 5,
      //'deleteOlderThan'     => 10,
    ],
  ],
] ;
?>
