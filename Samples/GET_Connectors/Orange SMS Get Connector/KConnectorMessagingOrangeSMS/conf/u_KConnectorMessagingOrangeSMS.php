<?php
return [
  'self'                       => [
    'service'                     => 'Orange SMS+ Connector',
    'version'                     => 'sample',
    'sender'                      => 'xxxxxxxxxxx',
    'smsPrefixKeyword'            => 'xxxxx',
  ],
  'runtime'                    => [
    'pagination'                  => [
      'limitPerRequestMessages'      =>  '5',  // Min : 5, Max : 100
    ],
    'datetimes'                   => [
      'inFormat'                     => 'Y-m-d\TH:i:s.uP',
      'outFormat'                    => 'Y-m-d\TH:i:s.v\Z',
      'outTimezone'                  => 'UTC',
    ],
    'encodings'                   => [
      'outEncoding'                  => 'UCS2',  // in 'GSM7' (7bits, 153 chars max) or 'UCS2' (16 bits, 63 chars max)
    ],
    'resources'                   => [
      'customerCache'                  => [
        'enabled'                        =>   true,  // Customer cache must be enabled in this version
        'checkEveryInSecs'               =>   3600,
        'expirationInSecs'               => 259200,  // An SMS+ conversation id expires in 72h : https://contact-everyone.orange-business.com/api/docs/guides/index.html?php#10-sms
      ],
      'cursors'                      => [
        'enabled'                       => false,
      ],
      'customers'                    => [
        'enabled'                       => false,
      ],
      'conversations'                => [
        'enabled'                       => false,
      ],
    ],
  ],
  'accessData'                 => [
    'apiBaseUrl'                  => 'https://contact-everyone.orange-business.com/api',
    'apiVersion'                  => 'v1.2',
    'oauthLogin'                  => 'xxxxxxxxxxxxxxxxx',
    'oauthPassword'               => 'xxxxxxxxxxxx',
  ],
] ;
?>
