<?php
return [
  'self'                       => [
    'service'                     => 'Facebook Direct Messages Connector',
    'version'                     => 'sample',
  ],
  'runtime'                    => [
    'pagination'                  => [
      'limitPerRequestConversations' =>  '10',
      'limitPerRequestMessages'      =>  '25',
    ],
    'datetimes'                   => [
      'dateFormat'                   => 'Y-m-d\TH:i:s\+0000',
    ],
    'resources'                   => [
      'cursors'                      => [
        'enabled'                       => false,
      ],
      'customers'                    => [
        'enabled'                       => false,
        'cache'                         => [
          'enabled'                        => true,
          'checkEveryInSecs'               => 180,
          'expirationInSecs'               => 300,
        ],
      ],
      'conversations'                => [
        'enabled'                       => false,
      ],
    ],
  ],
  'accessData'                 => [
    'apiBaseUrl'                  => 'https://graph.facebook.com',
    'apiVersion'                  => 'v3.3',
    'appName'                     => 'xxxxxxxxxxx',
    'appId'                       => 'xxxxxxxxxxxxxxx',
    'appSecret'                   => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'accessToken'                 =>  'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'pageName'                    => 'xxxxxxxxxxx',
    'pageId'                      => 'xxxxxxxxxxxxxxx'
  ],
] ;
?>
