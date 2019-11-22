<?php
namespace UserFiles\Messaging\Connector ;

define( "TWSAMPLE_CONNECTOR", "KMessagingSampleTwitter" ) ;


/**/
// Kiamo v6.x : Messaging Utilities
// -----

const TWITTER_SAMPLE_KIAMO_ROOT              = __DIR__ . "/../../../../../" ;
const TWITTER_SAMPLE_KIAMO_MESSAGING_UTILITY = TWITTER_SAMPLE_KIAMO_ROOT . "www/Symfony/src/Kiamo/Bundle/AdminBundle/Utility/Messaging/" ;

require_once TWITTER_SAMPLE_KIAMO_MESSAGING_UTILITY . "ParameterBag.php"              ;
require_once TWITTER_SAMPLE_KIAMO_MESSAGING_UTILITY . "ConnectorConfiguration.php"    ;
require_once TWITTER_SAMPLE_KIAMO_MESSAGING_UTILITY . "GenericConnectorInterface.php" ;

use Kiamo\Bundle\AdminBundle\Utility\Messaging\ParameterBag              ;
use Kiamo\Bundle\AdminBundle\Utility\Messaging\ConnectorConfiguration    ;
use Kiamo\Bundle\AdminBundle\Utility\Messaging\GenericConnectorInterface ;
/**/


/*
// Kiamo v7.x : Messaging Utilities
// -----

const TWITTER_SAMPLE_KIAMO_ROOT              = __DIR__ . "/../../../../../" ;
const TWITTER_SAMPLE_KIAMO_MESSAGING_UTILITY = TWITTER_SAMPLE_KIAMO_ROOT . "www/Symfony/src/Kiamo/Admin/Utility/Messaging/" ;

require_once TWITTER_SAMPLE_KIAMO_MESSAGING_UTILITY . "ParameterBag.php"              ;
require_once TWITTER_SAMPLE_KIAMO_MESSAGING_UTILITY . "ConnectorConfiguration.php"    ;
require_once TWITTER_SAMPLE_KIAMO_MESSAGING_UTILITY . "GenericConnectorInterface.php" ;

use Kiamo\Admin\Utility\Messaging\ParameterBag              ;
use Kiamo\Admin\Utility\Messaging\ConnectorConfiguration    ;
use Kiamo\Admin\Utility\Messaging\GenericConnectorInterface ;
*/


use \DateTime, \DateTimeZone ;


// Kiamo Messaging Connector
// ---
class KMessagingSampleTwitter implements GenericConnectorInterface
{
  const RootPath = __DIR__ ;

  public function __construct( ConnectorConfiguration $configuration )
  {
    $this->initConfig( $configuration ) ;
    $this->logger = new TwLogger( $this ) ;

    $this->log( "------------------------------------------------------------------------------", TwLogger::LOG_INFOP, __METHOD__ ) ;
    $this->_msgManager = new TwMessagingManager( $this ) ;
    $this->log( "INIT : OK", TwLogger::LOG_INFOP, __METHOD__ ) ;
  }

  /* **************************************************************************
     Connector Configuration
  */
  private function initConfig( $configuration )
  {
    $this->_parameters = $configuration ;

    // Connector's configuration
    // ---
    $this->selfConf    = [
      'service'                     => 'Twitter Connector Sample',
      'version'                     => 'sample',
    ] ;

    // Runtime configuration
    // ---
    $this->runtimeConf = [
      'logLevel'                    => TwLogger::LOG_DEBUG,
    ] ;

    // External Messaging API Access configuration
    // ---
    $this->accessConf = [
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
        'userId'                       => '844229943160725504',                                   // selfId : get it by sending a direct message to yourself and call $this->connector->_msgManager->twitterRequest( 'messageList' ) in the CommandLineTester
        'consumerKey'                  => 'Ckf6JwZYZjyKgviO4tH33GyXy',                            // apiKey
        'consumerSecret'               => 'P8mmLQWtAd66UZHxnNvQXROYhXtfNsUC18DYN6uFsIbwerECQz',   // apiSecretKey
        'oauthToken'                   => '844229943160725504-MmGcZkoQRbCMbV34Ld7DXT9dzkNRJnz',   // accessToken
        'oauthTokenSecret'             => 'XSYiH3ZoK0dV9hHTwTLRKDUiGUpmTm8vi2vm2cszuty01',        // accessSecretToken
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
    ] ;
  }

  public   function getConf( $confKey, $key = null )
  {
    $conf = null ;
    switch( $confKey )
    {
    case "self" :
      $conf = &$this->selfConf ;
      break ;
    case "runtime" :
      $conf = &$this->runtimeConf ;
      break ;
    case "access" :
      $conf = &$this->accessConf ;
      break ;
    }
    return $conf == null ? null : $this->getInDict( $conf, $key ) ;
  }


  /* **************************************************************************
     GLOBAL Connector
  */

  public function getClassName()
  {
    if( !empty( $this->_classname ) ) return $this->_classname ;
    $_array = explode( '\\', get_class( $this ) ) ;
    $this->_classname = end( $_array ) ;
    return $this->_classname ;
  }
  
  public function getName()
  {
    return $this->getConf( "self", "service" ) ;
  }

  public function getIcon()
  {
    return null ;
  }

  public function fetch( $parameterBag )
  {
    $this->log( "Fetching message(s)", TwLogger::LOG_INFO, __METHOD__ ) ;

    $params             = $parameterBag->getParameters() ;
    $lastReadMessageKey = TWSAMPLE_CONNECTOR . '.lastReadMessageId' ;
    $lastReadMessageId  = '' ;
    if( array_key_exists( $lastReadMessageKey, $params ) ) $lastReadMessageId = $params[ $lastReadMessageKey ] ;
    if( !empty( $lastReadMessageId ) ) $this->log( "==> lastMessageId=" . $lastReadMessageId, TwLogger::LOG_DEBUG, __METHOD__ ) ;

    $msgRes             = $this->_msgManager->readMessages( $lastReadMessageId ) ;  // read all unread user messages from the messaging address
    $msgArr             = $msgRes[ 'newMessages'       ] ;
    $this->log( "Fetched " . count( $msgArr ) . " message(s)", TwLogger::LOG_INFO, __METHOD__ ) ;
    if( $lastReadMessageId !== $msgRes[ 'lastReadMessageId' ] )
    {
      $this->log( "==> new lastMessageId=" . $msgRes[ 'lastReadMessageId' ], TwLogger::LOG_DEBUG, __METHOD__ ) ;
      $parameterBag->setParameter( $lastReadMessageKey, $msgRes[ 'lastReadMessageId' ] ) ;
    }

    foreach( $msgArr as $msg )
    {
      $inputMsg = [
        'id'         => $msg[ "id"      ],
        'createdAt'  => $msg[ "date"    ],
        'senderId'   => $msg[ "from"    ],
        'senderName' => $msg[ "sender"  ],
        'content'    => $msg[ "message" ],
      ] ;

      // Special case : history before connector
      if( $msg[ "from" ] === $this->getConf( 'access', 'credentials.userId' ) )
      {
        $inputMsg[ "senderId"   ] = $msg[ "to"        ] ;
        $inputMsg[ "senderName" ] = $msg[ "recipient" ] ;
        $inputMsg[ "content"    ] = $inputMsg[ "senderName" ] . ' ==> ' . $msg[ "message" ] ;
      }

      $this->log( "=> adding message : " . json_encode( $inputMsg ), TwLogger::LOG_INFO, __METHOD__ ) ;
      $parameterBag->addMessage( $inputMsg ) ;
    }
    
    return $parameterBag;
  }

  public function send( array $messageTask )
  {
    $this->log( "Sending message : " . json_encode( $messageTask ), TwLogger::LOG_INFO, __METHOD__ ) ;

    $msg = $messageTask[ "content" ] ;
    $to  = $messageTask[ "to" ][ "id" ] ;

    $this->log( "Sending message to user id '" . $to . "' : '" . $msg . "'", TwLogger::LOG_INFO, __METHOD__ ) ;
    $this->_msgManager->sendMessage( $to, $msg ) ;
  }


  /* **************************************************************************
     Inner Tools
  */

  public   function log( $str, $level = TwLogger::LOG_DEBUG, $method = '', $indentLevel = 0 )
  {
    $this->logger->log( $str, $level, $method, $indentLevel ) ;
  }

  public   function getInDict( $dict, $key = null )
  {
    if( $key === null ) return $dict ;

    $_sk = $this->_splitKey( $key ) ;

    $cur = &$dict ;
    foreach( $_sk as $_k )
    {
      if( !is_array( $cur ) || !array_key_exists( $_k, $cur ) ) return null ;
      $cur = &$cur[ $_k ] ;
    }
    return $cur ;
  }

  private function _splitKey( $key )
  {
    if( empty( $key ) ) return $key ;

    $res = null ;
    if( is_string( $key ) )
    {
      $res = explode( '.', $key ) ;
    }
    else
    {
      $res = &$key ;
    }
    return $res ;
  }
}


/* ****************************************************************************
   Messaging Management
   ---
   Purpose : externalization from the connector of the implementation of the Web Service API authentication, requests, and all the related mechanisms.
*/
class TwMessagingManager
{
  public    function __construct( $_parent )
  {
    $this->_parent = $_parent ;

    $this->log( "Service : " . $this->getConf( 'self', 'service' ), TwLogger::LOG_DEBUG, __METHOD__ ) ;
    $this->log( "Version : " . $this->getConf( 'self', 'version' ), TwLogger::LOG_DEBUG, __METHOD__ ) ;
    
    $this->initRuntimeData()   ;
  }

  public   function initRuntimeData()
  {
    $this->selfId = $this->getConf( 'access',  'credentials.userId' ) ;
  }


  /* --------------------
     Twitter call methods
  */

  public   function _buildCallContext( $verb, $urlParams = null )
  {
    $baseUrl =         $this->getConf( 'access', 'apiBaseUrl'    )
               . '/' . $this->getConf( 'access', 'apiVersion'    )
               . '/' . $this->getConf( 'access', 'verbs.' . $verb . '.route' )
               . '/' . $this->getConf( 'access', 'verbs.' . $verb . '.verb'  ) ;
    $fullUrl = $baseUrl ;
    if( !empty( $urlParams ) )
    {
      $first    = true ;
      $fullUrl .= '?' ;
      foreach( $urlParams as $k => $v )
      {
        if( $first === true ) $first = false ; else $fullUrl .= '&' ;
        $fullUrl .= $k . '=' . $v ;
      }
    }
    $res = [
      'verb'    => $verb,
      'baseUrl' => $baseUrl,
      'fullUrl' => $fullUrl,
      'method'  => $this->getConf( 'access', 'verbs.' . $verb . '.method' ),
      'type'    => $this->getConf( 'access', 'verbs.' . $verb . '.type'   ),
    ] ;
    return $res ;
  }


  public   function _buildOAuthBaseParams()
  {
    $res = [
      $this->getConf( 'access', 'oauthData.keys.consumer'        ) => $this->getConf( 'access', 'credentials.consumerKey'   ),
      $this->getConf( 'access', 'oauthData.keys.nonce'           ) => TwUuids::get( true, 32 ),
      $this->getConf( 'access', 'oauthData.keys.signatureMethod' ) => $this->getConf( 'access', 'oauthData.signatureMethod' ),
      $this->getConf( 'access', 'oauthData.keys.timestamp'       ) => TwDatetimes::nowTs(),
      $this->getConf( 'access', 'oauthData.keys.token'           ) => $this->getConf( 'access', 'credentials.oauthToken'    ),
      $this->getConf( 'access', 'oauthData.keys.version'         ) => $this->getConf( 'access', 'oauthData.version'         ),
    ] ;
    return $res ;
  }


  public   function _buildOAuthSignature( &$callContext, &$oauthBaseParams, $urlParams = null )
  {
    // Params array
    $paramArr = null ;
    if( empty( $urlParams ) )
    {
      $paramArr = $oauthBaseParams ;
    }
    else
    {
      $paramArr = array_merge( $oauthBaseParams, $urlParams ) ;
      ksort( $paramArr ) ;
    }

    // Params string
    $first    = true ;
    $paramStr = "" ;
    foreach( $paramArr as $k => $v )
    {
      if( $first === true ) $first = false ; else $paramStr .= '&' ;
      $paramStr .= rawurlencode( $k ) . '=' . rawurlencode( $v ) ;
    }

    // Signature base string
    $signBaseStr = $callContext[ 'method' ] . '&' . rawurlencode( $callContext[ 'baseUrl' ] ) . '&' . rawurlencode( $paramStr ) ;

    // Signing key
    $signKey =         rawurlencode( $this->getConf( 'access', 'credentials.consumerSecret'   ) )
               . '&' . rawurlencode( $this->getConf( 'access', 'credentials.oauthTokenSecret' ) ) ;


    // OAuth Signature
    $oauthSign = base64_encode( hash_hmac( $this->getConf( 'access', 'oauthData.hashMacMethod' ), $signBaseStr, $signKey, $raw_output = true ) ) ;

    // Add signature to oauth base params
    $oauthBaseParams[ $this->getConf( 'access', 'oauthData.keys.signature' ) ] = $oauthSign ;
    ksort( $oauthBaseParams ) ;
    
    return $oauthSign ;
  }


  public   function _buildOAuthHeaderString( &$oauthParams )
  {
    $first = true ;
    $res = "OAuth " ;
    foreach( $oauthParams as $k => $v )
    {
      if( $first === true ) $first = false ; else $res .= ', ' ;
      $res .= rawurlencode( $k ) . '="' . rawurlencode( $v ) . '"' ;
    }
    return $res ;
  }


  public   function _buildRequestHeader( &$callContext, $oauthHeaderStr, $bodyJsonStr = null )
  {
    $res = [
      "Authorization: " . $oauthHeaderStr, 
      "Content-Type: "  . $callContext[ 'type' ],
    ] ;
    if( !empty( $bodyJsonStr ) ) $res[] = 'Content-Length: ' . strlen( $bodyJsonStr ) ;
    return $res ;
  }


  public   function _requestTwitterAPI( &$callContext, $header, $bodyJsonStr = null, $verbose = false )
  {
    if( $verbose === true )
    {
      echo "---" . "\n" ;
      echo "URL = " . $callContext[ 'fullUrl' ] . "\n" ;
      echo "HED = " . json_encode( $header, JSON_PRETTY_PRINT ) . "\n" ;
    }
    $callRes = TwWebs::restRequest( $callContext[ 'fullUrl' ], $bodyJsonStr, $header, null ) ;
    if( $verbose === true )
    {
      echo "---" . "\n" ;
      echo "CALLRES = \n\n" . json_encode( $callRes, JSON_PRETTY_PRINT ) . "\n" ;
    }
    return $callRes ;
  }


  // Generic Twitter request context builder
  public   function buildRequestContext( $verb, $urlParams = null, $bodyJsonStr = null )
  {
    $res = [] ;

    $res[ 'verb'        ] = $verb        ;
    $res[ 'urlParams'   ] = $urlParams   ;
    $res[ 'bodyJson'    ] = $bodyJsonStr ;

    $res[ 'callContext' ] = $this->_buildCallContext(       $verb                                       , $urlParams         ) ;
    $res[ 'oauthParams' ] = $this->_buildOAuthBaseParams(                                                                    ) ;
    $res[ 'signature'   ] = $this->_buildOAuthSignature(    $res[ 'callContext' ], $res[ 'oauthParams' ], $urlParams         ) ;
    $res[ 'oauthHeader' ] = $this->_buildOAuthHeaderString(                        $res[ 'oauthParams' ]                     ) ;
    $res[ 'header'      ] = $this->_buildRequestHeader(     $res[ 'callContext' ], $res[ 'oauthHeader' ], $res[ 'bodyJson' ] ) ;

    return $res ;
  }


  // Generic Twitter request caller
  public   function twitterRequest( $verb, $urlParams = null, $bodyJsonStr = null )
  {
    $logstr = "Twitter request '" . $verb . "'" ;
    if( !empty( $urlParams   ) ) $logstr .= ", urlParams=" . json_encode( $urlParams ) ;
    if( !empty( $bodyJsonStr ) ) $logstr .= ", bodyJson="  . $bodyJsonStr ;
    $this->log( $logstr, TwLogger::LOG_INFOP, __METHOD__ ) ;
    $requestContext = $this->buildRequestContext( $verb, $urlParams, $bodyJsonStr ) ;
    $requestResult  = $this->_requestTwitterAPI( $requestContext[ 'callContext' ], $requestContext[ 'header' ], $requestContext[ 'bodyJson' ] ) ;
    if( $requestResult[ TwWebs::REST_REQUEST_STATUS ] !== true || $requestResult[ TwWebs::REST_REQUEST_HTTPCODE ] !== 200 )
    {
      $this->log( "==> KO request : " . json_encode( $requestResult ), TwLogger::LOG_ERROR, __METHOD__ ) ;
      return null ;
    }
    $this->log( "==> Request OK", TwLogger::LOG_INFOP, __METHOD__ ) ;
    $this->log( "==> Result : " . json_encode( $requestResult[ TwWebs::REST_REQUEST_RESULT ] ), TwLogger::LOG_DEBUG, __METHOD__ ) ;
    return $requestResult[ TwWebs::REST_REQUEST_RESULT ] ;
  }


  /* -------------------
     Entities management
  */

  private  function getUserRecord( $userId )
  {
    // Request User Data
    $userData = $this->twitterRequest( 'userShow', [ 'user_id' => $userId ] ) ;

    // Create user record
    $userRecord = [
      'id'     => $userId,
      'name'   => '',
      'screen' => '',
      'key'    => '',
    ] ;
    if( empty( $userData ) )
    {
      $this->log( "Unable to recover data for user id=" . $userId, TwLogger::LOG_WARN, __METHOD__ ) ;
    }
    $this->log( "==> User : " . json_encode( $userRecord ), TwLogger::LOG_INFO, __METHOD__ ) ;
    return $userRecord ;
  }


  /* ----------------------
     Connector entry points
  */

  public   function readMessages( $lastReadMessageId )
  {
    $res                   = [
      'lastReadMessageId'    => $lastReadMessageId,
      'newMessages'          => [],
    ] ;

    $slog = "Fetching Twitter messages" ;
    if( !empty( $lastReadMessageId ) ) $slog .= ", lastReadMessageId=" . $lastReadMessageId ;
    $slog .= "..." ;
    $this->log( $slog, TwLogger::LOG_INFO, __METHOD__ ) ;
    
    $urlParams = [] ;
    $pageCount = $this->getConf( 'access', 'verbs.messageList.paginationCount' ) ;
    if( empty( $pageCount ) )
    {
      $urlParams = null ;
    }
    else
    {
      $urlParams[ 'count' ] = $pageCount ;
    }

    $newLastReadMessageId = $lastReadMessageId ;
    $foundLastReadMessage = false ;
    $newMsgNb             = 0 ;
    while( true )
    {
      $twitterMessages = $this->twitterRequest( 'messageList', $urlParams ) ;
      if( empty( $twitterMessages ) ) break ;
      $this->log( "==> " . count( $twitterMessages[ 'events' ] ) . " message(s)", TwLogger::LOG_INFO, __METHOD__ ) ;

      // Loop on messages returned on this pagination page
      $pageMsgNb = 0 ;
      foreach( $twitterMessages[ 'events' ] as $message )
      {
        if( $message[ 'id' ] === $lastReadMessageId )
        {
          $this->log( "==> already read message, stop reading here", TwLogger::LOG_DEBUG, __METHOD__ ) ;
          $foundLastReadMessage = true ;
          break ;
        }
        if( $message[ 'type' ] !== 'message_create' ) continue ;

        // Message sent by support user : consider it as already read
        if( $message[ 'message_create' ][ 'sender_id' ] === $this->selfId )
        {
          $messageRecord = [
            'id'           => $message[ 'id'                ],
            'timestamp'    => $message[ 'created_timestamp' ],
            'from'         => $message[ 'message_create'    ][ 'sender_id'    ],
            'to'           => $message[ 'message_create'    ][ 'target'       ][ 'recipient_id' ],
            'message'      => $message[ 'message_create'    ][ 'message_data' ][ 'text'         ],
          ] ;
          $this->log( "==> skipping self message : " . json_encode( $messageRecord ), TwLogger::LOG_DEBUG, __METHOD__ ) ;
          if( $newLastReadMessageId === $lastReadMessageId ) $newLastReadMessageId = $message[ 'id' ] ;   // Pick the first one, because they are "Sorted in reverse-chronological order" (https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/list-events)
          continue ;
        }

        // Create message record
        $pageMsgNb++ ;
        $newMsgNb++  ;
        $messageRecord = [
          'id'           => $message[ 'id'                ],
          'timestamp'    => $message[ 'created_timestamp' ],
          'from'         => $message[ 'message_create'    ][ 'sender_id'    ],
          'to'           => $message[ 'message_create'    ][ 'target'       ][ 'recipient_id' ],
          'message'      => $message[ 'message_create'    ][ 'message_data' ][ 'text'         ],
        ] ;
        if( $newLastReadMessageId === $lastReadMessageId ) $newLastReadMessageId = $messageRecord[ 'id' ] ;   // Pick the first one, because they are "Sorted in reverse-chronological order" (https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/list-events)
      
        // Insert message record in messages database
        $conversationId = null ;
        if( $messageRecord[ 'from' ] === $this->selfId )
        {
          $conversationId = $messageRecord[ 'to'   ] ;
        }
        else
        {
          $conversationId = $messageRecord[ 'from' ] ;
        }

        // Get from and to user records
        $userFrom = $this->getUserRecord( $conversationId        ) ;
        $userTo   = $this->getUserRecord( $messageRecord[ 'to' ] ) ;
        $this->log( "==> new  message from '" . $userFrom[ 'key' ] . "' to '" . $userTo[ 'key' ] . "'", TwLogger::LOG_DEBUG, __METHOD__ ) ;

        // Complete the message record
        $messageRecord[ 'date'      ] = TwDatetimes::getRFC2822FromTimestamp( $messageRecord[ 'timestamp' ] ) ;
        $messageRecord[ 'sender'    ] = $userFrom[      'key'  ] ;
        $messageRecord[ 'recipient' ] = $userTo[        'key'  ] ;
        $this->log( "==> new  message : " . json_encode( $messageRecord ), TwLogger::LOG_DEBUG, __METHOD__ ) ;

        array_push( $res[ 'newMessages' ], $messageRecord ) ;
      }
      
      // Check if there is a next pagination page
      if( array_key_exists( 'next_cursor', $twitterMessages ) )
      {
        $urlParams[ 'cursor' ] = $twitterMessages[ 'next_cursor' ] ;
      }
      
      // Check if we have to break out of the loop
      if( $foundLastReadMessage === true || $pageMsgNb === 0 || !array_key_exists( 'next_cursor', $twitterMessages ) ) break ;

      $this->log( "==> Next page cursor : '" . $urlParams[ 'cursor' ] . "'", TwLogger::LOG_DEBUG, __METHOD__ ) ;
    }

    $res[ 'lastReadMessageId' ] = $newLastReadMessageId ;
    $this->log( "Read message(s) : " . count( $res[ 'newMessages' ] ) . " results", TwLogger::LOG_INFO, __METHOD__ ) ;

    return $res ;
  }
  
  public   function sendMessage( $to, $message )
  {
    $_to = $to ;
    $this->log( "Sending message to user '" . $_to . "', message='" . $message . "'...", TwLogger::LOG_INFO, __METHOD__ ) ;
    $body =   '{"event": {"type": "message_create", "message_create": {"target": {"recipient_id": "'
            . $to
            . '"}, "message_data": {"text": "' 
            . $message
            . '"}}}}' ;
    $messageData = $this->twitterRequest( 'messageNew', null, $body ) ;
    if( empty( $messageData ) )
    {
      $this->log( "! Twitter request issue while trying to send message to user id=" . $to, TwLogger::LOG_WARN, __METHOD__ ) ;
      return false ;
    }
    
    $messageRecord = [
      'id'           => $messageData[ 'event' ][ 'id'                ],
      'timestamp'    => $messageData[ 'event' ][ 'created_timestamp' ],
      'from'         => $messageData[ 'event' ][ 'message_create'    ][ 'sender_id'    ],
      'to'           => $messageData[ 'event' ][ 'message_create'    ][ 'target'       ][ 'recipient_id' ],
      'message'      => $messageData[ 'event' ][ 'message_create'    ][ 'message_data' ][ 'text'         ],
    ] ;
    $this->log( "==> message sent : " . json_encode( $messageRecord ), TwLogger::LOG_DEBUG, __METHOD__ ) ;

    return true ;
  }


  /* **************************************************************************
     Inner Tools
  */

  public function getClassName()
  {
    return $this->_parent->getClassName() ;
  }
  private  function log( $str, $level = TwLogger::LOG_DEBUG, $method = '', $indentLevel = 0 )
  {
    $this->_parent->logger->log( $str, $level, $method, $indentLevel ) ;
  }
  private  function getConf( $confKey, $key = null )
  {
    return $this->_parent->getConf( $confKey, $key ) ;
  }
}


/* ****************************************************************************
   Logger Helper
*/
class TwLogger
{
  const LOG_ALL                    =  0 ;
  const LOG_VERBOSE                =  1 ;
  const LOG_VERBOZE                =  1 ;
  const LOG_TRACE                  =  2 ;
  const LOG_DEBUG                  =  3 ;
  const LOG_INFOP                  =  4 ;
  const LOG_INFO                   =  5 ;
  const LOG_WARN                   =  6 ;
  const LOG_WARNING                =  6 ;
  const LOG_ERR                    =  7 ;
  const LOG_ERROR                  =  7 ;
  const LOG_CRITICAL               =  8 ;
  const LOG_NONE                   =  9 ;

  const LOGS_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR ;
  
  public    function __construct( $_parent )
  {
    $this->_parent = $_parent ;
    $this->initConf() ;
    $this->log( "INIT : OK", self::LOG_TRACE, __METHOD__ ) ;
  }
  
  public    function initConf()
  {
    $this->timezone        = 'Europe/Paris' ;
    $this->maxLogLevel     = $this->_parent->getConf( 'runtime', 'logLevel' ) ;
    $this->adjustMethodLen = 50 ;
    $this->parentClassName = $this->_parent->getClassName() ;
    if( !file_exists( self::LOGS_PATH ) ) mkdir( self::LOGS_PATH ) ;
    $this->logsPath        = self::LOGS_PATH . DIRECTORY_SEPARATOR . $this->parentClassName . DIRECTORY_SEPARATOR ;
    if( !file_exists( $this->logsPath ) ) mkdir( $this->logsPath ) ;
  }

  public   function log( $str, $level = self::LOG_DEBUG, $method = '', $indentLevel = 0 )
  {
    if( $level < $this->maxLogLevel ) return ;

    // Prepare the log line
    $methodStr = $this->_getMethodStr( $method ) ;
    $indentStr = '' ;
    $indentStr = str_pad( $indentStr, $indentLevel * 2 + 1 ) ;
    $now       = $this->getTimeNow() ;
    $resStr    = self::bracket( $now ) . self::getLogLevelStr( $level ) . $methodStr . $indentStr . $str . "\r\n" ;

    // Write log (with lock mechanism)
    $this->_setLogFile() ;
    $fp = fopen( $this->logfile, 'a+' ) ;
    if( flock( $fp, LOCK_EX | LOCK_NB ) )
    {
      fseek( $fp, SEEK_END ) ;
      fputs( $fp, $resStr  ) ;
      flock( $fp, LOCK_UN  ) ;
    }
    fclose( $fp ) ;
  }

  protected function getDateNow()
  {
    return ( new DateTime( 'now', new DateTimeZone( $this->timezone ) ) )->format( 'Ymd' ) ; 
  }
  protected function getTimeNow()
  {
    return ( new DateTime( 'now', new DateTimeZone( $this->timezone ) ) )->format( 'Ymd_His' ) ; 
  }

  private  function _setLogFile()
  {
    $this->logfile = $this->logsPath . $this->getDateNow() . ".log" ;
  }

  protected function _getMethodStr( $method )
  {
    $tmpSlashArr = explode( '\\', $method ) ;
    $method      = end( $tmpSlashArr ) ;
    $method      = $this->_adjustMethod( $method ) ;
    return self::bracket( $method ) ;
  }

  protected static function getLogLevelStr( $level )
  {
    switch( $level )
    {
    case self::LOG_VERBOSE :
      return "[VERBZ]" ;
    case self::LOG_TRACE :
      return "[TRACE]" ;
    case self::LOG_DEBUG :
      return "[DEBUG]" ;
    case self::LOG_INFO :
      return "[INFO ]" ;
    case self::LOG_INFOP :
      return "[INFOP]" ;
    case self::LOG_WARN :
    case self::LOG_WARNING :
      return "[WARNG]" ;
    case self::LOG_ERR :
    case self::LOG_ERROR :
      return "[ERROR]" ;
    case self::LOG_CRITICAL :
      return "[CRITK]" ;
    default :
      return "[     ]" ;
    }
  }

  protected static function bracket( $sstr )
  {
    return '[' . $sstr . ']' ;
  }

  private  function _adjustMethod( $methodName )
  {
    $_len = strlen( $methodName ) ;
    if( $_len === $this->adjustMethodLen )
    {
      return $methodName ;
    }
    else if( $_len > $this->adjustMethodLen )
    {
      return substr( $methodName, 0, $this->adjustMethodLen ) ;
    }
    else
    {
      $_delta = $this->adjustMethodLen - $_len ;
      $_post  = str_repeat( ' ', $_delta ) ;
      return $methodName . $_post ;
    }
  }
}


/* ****************************************************************************
   Web Requests Helper
*/
class TwWebs
{
  const CURL_ERROR_CODES = array(
     0 => 'CURLE_OK', 
     1 => 'CURLE_UNSUPPORTED_PROTOCOL', 
     2 => 'CURLE_FAILED_INIT', 
     3 => 'CURLE_URL_MALFORMAT', 
     4 => 'CURLE_URL_MALFORMAT_USER', 
     5 => 'CURLE_COULDNT_RESOLVE_PROXY', 
     6 => 'CURLE_COULDNT_RESOLVE_HOST', 
     7 => 'CURLE_COULDNT_CONNECT', 
     8 => 'CURLE_FTP_WEIRD_SERVER_REPLY',
     9 => 'CURLE_REMOTE_ACCESS_DENIED',
    11 => 'CURLE_FTP_WEIRD_PASS_REPLY',
    13 => 'CURLE_FTP_WEIRD_PASV_REPLY',
    14=>'CURLE_FTP_WEIRD_227_FORMAT',
    15 => 'CURLE_FTP_CANT_GET_HOST',
    17 => 'CURLE_FTP_COULDNT_SET_TYPE',
    18 => 'CURLE_PARTIAL_FILE',
    19 => 'CURLE_FTP_COULDNT_RETR_FILE',
    21 => 'CURLE_QUOTE_ERROR',
    22 => 'CURLE_HTTP_RETURNED_ERROR',
    23 => 'CURLE_WRITE_ERROR',
    25 => 'CURLE_UPLOAD_FAILED',
    26 => 'CURLE_READ_ERROR',
    27 => 'CURLE_OUT_OF_MEMORY',
    28 => 'CURLE_OPERATION_TIMEDOUT',
    30 => 'CURLE_FTP_PORT_FAILED',
    31 => 'CURLE_FTP_COULDNT_USE_REST',
    33 => 'CURLE_RANGE_ERROR',
    34 => 'CURLE_HTTP_POST_ERROR',
    35 => 'CURLE_SSL_CONNECT_ERROR',
    36 => 'CURLE_BAD_DOWNLOAD_RESUME',
    37 => 'CURLE_FILE_COULDNT_READ_FILE',
    38 => 'CURLE_LDAP_CANNOT_BIND',
    39 => 'CURLE_LDAP_SEARCH_FAILED',
    41 => 'CURLE_FUNCTION_NOT_FOUND',
    42 => 'CURLE_ABORTED_BY_CALLBACK',
    43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
    45 => 'CURLE_INTERFACE_FAILED',
    47 => 'CURLE_TOO_MANY_REDIRECTS',
    48 => 'CURLE_UNKNOWN_TELNET_OPTION',
    49 => 'CURLE_TELNET_OPTION_SYNTAX',
    51 => 'CURLE_PEER_FAILED_VERIFICATION',
    52 => 'CURLE_GOT_NOTHING',
    53 => 'CURLE_SSL_ENGINE_NOTFOUND',
    54 => 'CURLE_SSL_ENGINE_SETFAILED',
    55 => 'CURLE_SEND_ERROR',
    56 => 'CURLE_RECV_ERROR',
    58 => 'CURLE_SSL_CERTPROBLEM',
    59 => 'CURLE_SSL_CIPHER',
    60 => 'CURLE_SSL_CACERT',
    61 => 'CURLE_BAD_CONTENT_ENCODING',
    62 => 'CURLE_LDAP_INVALID_URL',
    63 => 'CURLE_FILESIZE_EXCEEDED',
    64 => 'CURLE_USE_SSL_FAILED',
    65 => 'CURLE_SEND_FAIL_REWIND',
    66 => 'CURLE_SSL_ENGINE_INITFAILED',
    67 => 'CURLE_LOGIN_DENIED',
    68 => 'CURLE_TFTP_NOTFOUND',
    69 => 'CURLE_TFTP_PERM',
    70 => 'CURLE_REMOTE_DISK_FULL',
    71 => 'CURLE_TFTP_ILLEGAL',
    72 => 'CURLE_TFTP_UNKNOWNID',
    73 => 'CURLE_REMOTE_FILE_EXISTS',
    74 => 'CURLE_TFTP_NOSUCHUSER',
    75 => 'CURLE_CONV_FAILED',
    76 => 'CURLE_CONV_REQD',
    77 => 'CURLE_SSL_CACERT_BADFILE',
    78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
    79 => 'CURLE_SSH',
    80 => 'CURLE_SSL_SHUTDOWN_FAILED',
    81 => 'CURLE_AGAIN',
    82 => 'CURLE_SSL_CRL_BADFILE',
    83 => 'CURLE_SSL_ISSUER_ERROR',
    84 => 'CURLE_FTP_PRET_FAILED',
    84 => 'CURLE_FTP_PRET_FAILED',
    85 => 'CURLE_RTSP_CSEQ_ERROR',
    86 => 'CURLE_RTSP_SESSION_ERROR',
    87 => 'CURLE_FTP_BAD_FILE_LIST',
    88 => 'CURLE_CHUNK_FAILED'
  ) ;


  const REST_REQUEST_STATUS   = 0 ;
  const REST_REQUEST_CURLCODE = 1 ;
  const REST_REQUEST_HTTPCODE = 2 ;
  const REST_REQUEST_RESULT   = 3 ;
  
  // Result : [ okFlag, curl_error, http_code, jsonResponse ]
  public static function restRequest( $url, $data = null, $header = null, $authData = null, $verbose = false )
  {
    // Init
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $url ) ;
    
    // Verbose
    if( $verbose === true )
    {
      curl_setopt( $ch, CURLOPT_VERBOSE, true ) ;
    }

    // POST Data (otherwise, GET)
    if( !empty( $data ) )
    {
      $dataStr = $data ;
      if( is_array( $data ) ) $dataStr = http_build_query( $data ) ;
      curl_setopt( $ch, CURLOPT_POST        , true        ) ;
      curl_setopt( $ch, CURLOPT_POSTFIELDS  , $dataStr ) ;
    }
    
    // Header
    if( !empty( $header ) )
    {
      $_header = [] ;
      if( is_string( $header ) )
      {
        $_header[] = $header ;
      }
      else  // It's an array
      {
        foreach( $header as $k => $v )
        {
          if( is_int( $k ) )
          {
            $_header[] = $v ;
          }
          else  // It's a string
          {
            $_header[] = $k . ': ' . $v ;
          }
        }
      }
      curl_setopt( $ch, CURLOPT_HTTPHEADER  , $_header ) ;
    }

    // Authent Data
    if( !empty( $authData ) )
    {
      if( array_key_exists( 'httpAuth', $authData ) )
      {
        curl_setopt( $ch, CURLOPT_HTTPAUTH, $authData[ 'httpAuth' ] ) ;
      }
      if(    array_key_exists( 'username', $authData )
          && array_key_exists( 'password', $authData ) )
      {
        curl_setopt( $ch, CURLOPT_USERPWD, $authData[ 'username' ] . ':' . $authData[ 'password' ] ) ;
      }
    }

    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ) ; 

    // Call
    $result = curl_exec( $ch ) ;

    // Result
    $cerr = curl_errno(   $ch ) ;
    $info = curl_getinfo( $ch ) ;

    $res = [ true, self::CURL_ERROR_CODES[ $cerr ], $info[ 'http_code' ], null ] ;
    if( !$cerr )
    {
      $res[ self::REST_REQUEST_RESULT ] = json_decode( $result, true ) ;
      if( $res[ self::REST_REQUEST_HTTPCODE ] != 200 ) $res[ self::REST_REQUEST_STATUS ] = false ;
    }
    else
    {
      //echo "ERROR : " . $res[ self::REST_REQUEST_CURLCODE ] . "\n" ;
      $res[ self::REST_REQUEST_STATUS ] = false ;
    }
    //echo "Full result : \n" . json_encode( $res, JSON_PRETTY_PRINT ) . "\n" ;
    curl_close( $ch ) ;

    return $res ;
  }
}


/* ****************************************************************************
   Datetimes Helper
*/
class TwDatetimes
{
  // Current timestamp
  public static function nowTs()
  {
    return time() ;
  }
  public static function nowMs()
  {
    return round( microtime( true ) * 1000 ) ;
  }

  public static function getRFC2822FromTimestamp( $ts )
  {
    $_ts = $ts ;
    if( is_string( $ts ) && strlen( $ts ) > 10 ) $_ts = substr( $ts, 0, 10 ) ;
    $sdate = new \DateTime() ;
    $sdate->setTimestamp( $_ts ) ;
    return $sdate->format( \DateTime::RFC2822 ) ;
  }
}


/* ****************************************************************************
   UUID Generator Helper
*/
class TwUuids
{
  const DEFAULT_SIZE = 32 ;
  const ALPHANUMS    = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789" ;

  public static function get( $strong = false, $length = self::DEFAULT_SIZE )
  {
    if( $strong !== true ) return uniqid() ;

    $res = '' ;
    $max = strlen( self::ALPHANUMS ) ;

    for( $i = 0 ; $i < $length ; $i++ )
    {
      $res .= self::ALPHANUMS[ self::realrand( 0, $max - 1 ) ] ;
    }

    return $res;
  }

  public static function realrand( $min, $max )
  {
    $range = $max - $min ;
    if( $range < 1 ) return $min ;
    $log    = ceil( log( $range, 2 ) ) ;
    $bytes  = (int)( $log / 8 ) + 1 ;
    $bits   = (int)$log + 1 ;
    $filter = (int)( 1 << $bits ) - 1 ;
    do
    {
      $rnd = hexdec( bin2hex( openssl_random_pseudo_bytes( $bytes ) ) ) ;
      $rnd = $rnd & $filter ;
    }
    while( $rnd > $range ) ;
    return $min + $rnd ;
  }
}


/* ****************************************************************************
   Command Line Tester
*/
class TwCommandLineTester
{
  const Verb = 'test' ;


  public    function __construct()
  {
    $connectorClass = "UserFiles\\Messaging\\Connector\\" . TWSAMPLE_CONNECTOR ;
    $this->connector = new $connectorClass( new ConnectorConfiguration ) ;
    $this->defineTestFunctions() ;
    if( $this->setTestId() ) $this->run() ;
  }

  private  function usage()
  {
    echo "\n" ;
    echo "Usage\n" ;
    echo "-----\n" ;
    echo '> php <ConnectorName>.php -f --test="<testId>"' . "\n" ;
    echo '  ==> execution du test <testId>.' . "\n" ;
  }

  public function getClassName()
  {
    return $this->connector->getClassName() ;
  }

  private  function setTestId()
  {
    $this->testId = -1 ;
    $args   = getopt( null, [ self::Verb . ":" ] ) ;
    if( !array_key_exists( self::Verb, $args ) )
    {
      $this->usage() ;
      return false ;
    }
    $this->testId           = $args[ self::Verb ] ;
    if( strlen( $this->testId ) == 1 ) $this->testId = '0' . $this->testId ;
    $this->testFunctionName = self::Verb . $this->testId ;
    if( !array_key_exists( $this->testFunctionName, $this->testFunctions ) )
    {
      echo "\n" ;
      echo "ERROR : no such test '" . $this->testFunctionName . "'...\n" ;
      echo "==> Exit." ;
      echo "\n" ;
      return false ;
    }
    return true ;
  }

  private  function run()
  {
    echo "\nTest #" . $this->testId . " : '" . $this->testFunctions[ $this->testFunctionName ][ 'purpose' ] . "'\n---\n" ;
    call_user_func( $this->testFunctions[ $this->testFunctionName ][ 'function' ] ) ;
  }


  // Test Functions
  // ---
  private  function defineTestFunctions()
  {
    $this->testFunctions = [] ;

    $this->testFunctions[ 'test00' ] = [
      'purpose'  => 'Void execution',
      'function' => function()
      {
        echo "Do nothing...\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test01' ] = [
      'purpose'  => 'get User Data',
      'function' => function()
      {
        $verb       = 'userShow'            ;
        //$paramName  = 'user_id'             ;
        //$paramValue = 'xxxxxxxxxxxxxxxxxx'  ;
        $paramName  = 'screen_name'         ;
        $paramValue = 'siniestaKiamo'       ;
        $params     = []                    ;
        $params[ $paramName ] = $paramValue ;
        $callRes = $this->connector->_msgManager->twitterRequest( $verb, $params, null ) ;
        $res                  = [] ;
        $res[ 'id'          ] = $callRes[ 'id'          ] ;
        $res[ 'name'        ] = $callRes[ 'name'        ] ;
        $res[ 'screen_name' ] = $callRes[ 'screen_name' ] ;
        echo "User data = " . json_encode( $res, JSON_PRETTY_PRINT ) . "\n" ; ;
      }
    ] ;


    $this->testFunctions[ 'test02' ] = [
      'purpose'  => 'Read Messages',
      'function' => function()
      {
        $lastReadMessageId = null ;
        //$lastReadMessageId = "xxxxxxxxxxxxxxxxxxxx" ;
        $res = $this->connector->_msgManager->readMessages( $lastReadMessageId ) ;
        echo "==> Nb new messages   = " . count( $res[ 'newMessages'       ]   ) . "\n" ;
        echo "==> lastReadMessageId = " .        $res[ 'lastReadMessageId' ]     . "\n" ;
        echo "==> Nb new messages   = " . json_encode( $res, JSON_PRETTY_PRINT ) . "\n" ;
      }
    ] ;


    $this->testFunctions[ 'test03' ] = [
      'purpose'  => 'Send Message',
      'function' => function()
      {
        $to  = 'xxxxxxxxxxxxxxxxxxxx' ;
        $msg = 'Hello Twitter !' ;
        $this->connector->_msgManager->sendMessage( $to, $msg ) ;
      }
    ] ;


    /*
    $this->testFunctions[ 'testXX' ] = [
      'purpose'  => 'xxxxxxxxxxxxx',
      'function' => function()
      {
        echo "Test XX" . "\n" ; ;
      } 
    ] ;
    */
  }
}


// Enable command line test if ran by a command shell
if( php_sapi_name() == 'cli' && !empty( getopt( null, [ TwCommandLineTester::Verb . ":" ] ) ) )
{
  // Usage example :
  // > php <ConnectorName>.php -f --test=00
  new TwCommandLineTester() ;
}
?>