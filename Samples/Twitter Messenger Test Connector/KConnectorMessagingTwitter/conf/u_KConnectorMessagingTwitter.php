<?php
return [
  'self'                       => [
    'service'                     => 'Twitter Direct Messages Connector',
    'version'                     => 'sample',
  ],
  'runtime'                    => [
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
    'apiBaseUrl'                  => 'https://api.twitter.com',
    'apiVersion'                  => '1.1',
    'verbs'                       => [
      'messageList'                  => [  // Get all messages
        'method'                       => 'GET',
        'route'                        => 'direct_messages/events',
        'verb'                         => 'list.json',
        'type'                         => 'application/x-www-form-urlencoded',
        'paginationCount'              => '50',  // default = 20, max = 50 : https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/list-events
      ],
      'messageShow'                  => [  // Get uniq message
        'method'                       => 'GET',
        'route'                        => 'direct_messages/events',
        'verb'                         => 'show.json',
        'type'                         => 'application/x-www-form-urlencoded',
      ],
      'messageNew'                   => [  // Send message
        'method'                       => 'POST',
        'route'                        => 'direct_messages/events',
        'verb'                         => 'new.json',
        'type'                         => 'application/json',
      ],
      'userShow'                     => [  // Show user data
        'method'                       => 'GET',
        'route'                        => 'users',
        'verb'                         => 'show.json',
        'type'                         => 'application/json',
      ],
    ],
    'credentials'                 => [
      'userId'                       => 'xxxxxxxxxxxxxxxxxx',                                   // selfId : get it by sending a direct message to yourself and call $this->connector->_msgManager->twitterRequest( 'messageList' ) in the CommandLineTester
      'consumerKey'                  => 'xxxxxxxxxxxxxxxxxxxxxxxxx',                            // apiKey
      'consumerSecret'               => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',   // apiSecretKey
      'oauthToken'                   => 'xxxxxxxxxxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',   // accessToken
      'oauthTokenSecret'             => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',        // accessSecretToken
    ],
    'oauthData'                   => [
      'version'                      => '1.0',
      'hashMacMethod'                => 'sha1',
      'signatureMethod'              => 'HMAC-SHA1',
      'keys'                         => [
        'consumer'                      => 'oauth_consumer_key',
        'nonce'                         => 'oauth_nonce',
        'signature'                     => 'oauth_signature',
        'signatureMethod'               => 'oauth_signature_method',
        'timestamp'                     => 'oauth_timestamp',
        'token'                         => 'oauth_token',
        'version'                       => 'oauth_version',
      ],
    ],
  ],
] ;
?>
