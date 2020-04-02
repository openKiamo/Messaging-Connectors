<?php
namespace UserFiles\Messaging\Connector ;

define( "ORSAMPLE_CONNECTOR", "KMessagingSampleOrangeSMS" ) ;


const ORANGESMS_SAMPLE_KIAMO_ROOT              = __DIR__ . "/../../../../../" ;
const ORANGESMS_SAMPLE_KIAMO_MESSAGING_UTILITY = ORANGESMS_SAMPLE_KIAMO_ROOT . "www/Symfony/src/Kiamo/Admin/Utility/Messaging/" ;

require_once ORANGESMS_SAMPLE_KIAMO_MESSAGING_UTILITY . "ParameterBag.php"              ;
require_once ORANGESMS_SAMPLE_KIAMO_MESSAGING_UTILITY . "ConnectorConfiguration.php"    ;
require_once ORANGESMS_SAMPLE_KIAMO_MESSAGING_UTILITY . "GenericConnectorInterface.php" ;

use Kiamo\Admin\Utility\Messaging\ParameterBag              ;
use Kiamo\Admin\Utility\Messaging\ConnectorConfiguration    ;
use Kiamo\Admin\Utility\Messaging\GenericConnectorInterface ;


use \DateTime, \DateTimeZone ;


// Kiamo Messaging Connector
// ---
class KMessagingSampleOrangeSMS implements GenericConnectorInterface
{
  const RootPath = __DIR__ ;

  public function __construct( ConnectorConfiguration $configuration )
  {
    $this->initConfig( $configuration ) ;
    $this->logger = new OrLogger( $this ) ;

    $this->log( "------------------------------------------------------------------------------", OrLogger::LOG_INFOP, __METHOD__ ) ;
    $this->_msgManager = new OrMessagingManager( $this ) ;
    $this->log( "INIT : OK", OrLogger::LOG_INFOP, __METHOD__ ) ;
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
      'service'                     => 'Orange SMS+ Connector',
      'version'                     => 'sample',
      'sender'                      => '33xxxxxxxxxx',
      'smsPrefixKeyword'            => 'xxxxxx',
    ] ;

    // Runtime configuration
    // ---
    $this->runtimeConf = [
      'logLevel'                    => OrLogger::LOG_DEBUG,
      'pagination'                  => [
        'limitPerRequestMessages'      =>  '5',        // Min : 5, Max : 100
      ],
      'datetimes'                   => [
        'inFormat'                     => 'Y-m-d\TH:i:s.uP',
        'outFormat'                    => 'Y-m-d\TH:i:s.v\Z',
        'outTimezone'                  => 'UTC',
      ],
      'encodings'                   => [
        'outEncoding'                  => 'UCS2',      // in 'GSM7' (7bits, 153 chars max) or 'UCS2' (16 bits, 63 chars max)
      ],
      'resources'                   => [
        'customerCache'                  => [          // Customer cache must be enabled ; there is no other way to link the user id to the conversation id while sending / responding to a user
          'checkEveryInSecs'               =>   3600,
          'expirationInSecs'               => 259200,  // An SMS+ conversation id expires in 72h : https://contact-everyone.orange-business.com/api/docs/guides/index.html?php#10-sms
        ],
      ],
    ] ;

    // External Messaging API Access configuration
    // ---
    $this->accessConf = [
      'apiBaseUrl'                  => 'https://contact-everyone.orange-business.com/api',
      'apiVersion'                  => 'v1.2',
      'oauthLogin'                  => 'xxxxxxxx@xxxxxxx.xxx',
      'oauthPassword'               => 'xxxxxxxxxxxxxxx',
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
  
  public function getName() : string
  {
    return $this->getConf( "self", "service" ) ;
  }

  public static function getIcon() : ?string
  {
    return null ;
  }

  public function fetch( $parameterBag ) : ParameterBag
  {
    $this->log( "Fetching message(s)", OrLogger::LOG_INFO, __METHOD__ ) ;

    $params              = $parameterBag->getParameters() ;
    $lastReadMessageKey  = ORSAMPLE_CONNECTOR . '.lastReadMessageDate' ;
    $lastReadMessageDate = '' ;
    if( array_key_exists( $lastReadMessageKey, $params ) ) $lastReadMessageDate = $params[ $lastReadMessageKey ] ;
    if( !empty( $lastReadMessageDate ) ) $this->log( "==> lastMessageDate=" . $lastReadMessageDate, OrLogger::LOG_DEBUG, __METHOD__ ) ;

    $msgRes             = $this->_msgManager->readMessages( $lastReadMessageDate ) ;  // read all unread user messages from the messaging address
    $msgArr             = $msgRes[ 'newMessages' ] ;
    $this->log( "Fetched " . count( $msgArr ) . " message(s)", OrLogger::LOG_INFO, __METHOD__ ) ;
    if( $lastReadMessageDate !== $msgRes[ 'lastReadMessageDate' ] )
    {
      $this->log( "==> new lastMessageDate=" . $msgRes[ 'lastReadMessageDate' ], OrLogger::LOG_DEBUG, __METHOD__ ) ;
      $parameterBag->setParameter( $lastReadMessageKey, $msgRes[ 'lastReadMessageDate' ] ) ;
    }

    foreach( $msgArr as $msg )
    {
      $this->log( "==> New message : " . json_encode( $msg ), Logger::LOG_TRACE, __METHOD__ ) ;
      $inputMsg = [
        'id'         => $msg[ "id"        ],
        'createdAt'  => $msg[ "createdAt" ],
        'senderId'   => $msg[ "from"      ],
        'senderName' => $msg[ "from"      ],
        'content'    => $msg[ "message"   ],
      ] ;

      $this->log( "=> adding message : " . json_encode( $inputMsg ), OrLogger::LOG_INFO, __METHOD__ ) ;
      $parameterBag->addMessage( $inputMsg ) ;
    }
    
    return $parameterBag;
  }

  public function send( array $messageTask ) : void
  {
    $this->log( "Sending message : " . json_encode( $messageTask ), OrLogger::LOG_INFO, __METHOD__ ) ;

    $msg = $messageTask[ "content" ] ;
    $to  = $messageTask[ "to" ][ "id" ] ;

    $this->log( "Sending message to user id '" . $to . "' : '" . $msg . "'", OrLogger::LOG_INFO, __METHOD__ ) ;
    $this->_msgManager->sendMessage( $to, $msg ) ;
  }


  /* **************************************************************************
     Inner Tools
  */

  public   function log( $str, $level = OrLogger::LOG_DEBUG, $method = '', $indentLevel = 0 )
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
class OrMessagingManager
{
  public    function __construct( $_parent )
  {
    $this->_parent = $_parent ;

    $this->log( "Service : " . $this->getConf( 'self', 'service' ), OrLogger::LOG_DEBUG, __METHOD__ ) ;
    $this->log( "Version : " . $this->getConf( 'self', 'version' ), OrLogger::LOG_DEBUG, __METHOD__ ) ;
    
    $this->initRuntimeData()   ;
    $this->initAccessData()    ;
    $this->initResourceFiles() ;
  }

  public   function initRuntimeData()
  {
    $this->selfSender                = $this->getConf( "self"   , "sender"                                   ) ;
    $this->smsPrefixKeyword          = $this->getConf( "self"   , "smsPrefixKeyword"                         ) . ' ' ;
    $this->messagesLimit             = $this->getConf( "runtime", "pagination.limitPerRequestMessages"       ) ;

    $this->inDateFormat              = $this->getConf( "runtime", "datetimes.inFormat"                       ) ;
    $this->outDateFormat             = $this->getConf( "runtime", "datetimes.outFormat"                      ) ;
    $this->timestampFormat           = OrDatetimes::cleanDateTimeFormat( $this->outDateFormat                  ) ;
    $this->outTimezone               = $this->getConf( "runtime", "datetimes.outTimezone"                    ) ;

    $this->outEncoding               = $this->getConf( "runtime", "encodings.outEncoding"                    ) ;

    $this->customerCacheEnabled      = $this->getConf( 'runtime', 'resources.customerCache.enabled'          ) ;
    $this->customerCacheCheck        = $this->getConf( 'runtime', 'resources.customerCache.checkEveryInSecs' ) ;
    $this->customerCacheExpiration   = $this->getConf( 'runtime', 'resources.customerCache.expirationInSecs' ) ;

    $this->cursorsEnabled            = $this->getConf( 'runtime', 'resources.cursors.enabled'                ) ;
    $this->customersEnabled          = $this->getConf( 'runtime', 'resources.customers.enabled'              ) ;
    $this->conversationsEnabled      = $this->getConf( 'runtime', 'resources.conversations.enabled'          ) ;
  }

  private  function initAccessData()
  {
    $fieldsArr = [ 'apiBaseUrl', 'apiVersion', 'oauthLogin', 'oauthPassword' ] ;
    foreach( $fieldsArr as $field )
    {
      $this->$field = $this->getConf( "access", $field ) ;
      $this->log( "Access data : " . $field . " = " . $this->$field, OrLogger::LOG_INFO, __METHOD__ ) ;
    }
    $this->baseUrl     = $this->apiBaseUrl . '/' . $this->apiVersion . '/' ;
    $this->accessToken = $this->orangeSMSRequest( 'getAccessToken' ) ;
  }


  public   function initResourceFiles()
  {
    if( OrResources::existsDefaultDataFile() ) return ;

    // Main Data
    // => self
    // => last messages sent (ids)
    $mainPattern = [
      'lastReadMessageIds' => [],
      'customerCache'      => [
        'nextCheckTs'         => OrDatetimes::nowMs() + $this->customerCacheCheck,
        'userRecords'         => [],
        'expirationMap'       => [],
      ],
    ] ;
    OrResources::writeDefaultDataFile( $mainPattern ) ;
  }


  /* ----------------
     Cache management
  */
  private  function addUserToCache( $userRecord, &$messagingData = null )
  {
    $_messagingData = &$messagingData ;
    if( empty( $messagingData ) ) $_messagingData = OrResources::readDefaultDataFile() ;

    // If the user is already on the cache
    if(    array_key_exists( $userRecord[ 'id' ], $_messagingData[ 'customerCache' ][ 'userRecords' ] ) )
    {
      // If   the user is already on the cache with the same conversationId
      //   or the current conversationId is older than the cached one
      // ==> skip
      if(    $userRecord[ 'conversationId' ] === $_messagingData[ 'customerCache' ][ 'userRecords' ][ $userRecord[ 'id' ] ][ 'conversationId' ]
          || $userRecord[ 'messageTs'      ] <=  $_messagingData[ 'customerCache' ][ 'userRecords' ][ $userRecord[ 'id' ] ][ 'messageTs'      ] )
      {
        return ;
      }
      
      // The current expiration map must be unset (will be updated by the newer one
      unset( $_messagingData[ 'customerCache' ][ 'expirationMap' ][ $_messagingData[ 'customerCache' ][ 'userRecords' ][ $userRecord[ 'id' ] ][ 'expirationTs' ] ] ) ;
    }

    // Add the user to the cache
    $userRecord[ 'expirationTs' ] = OrDatetimes::nowMs() + $this->customerCacheExpiration * 1000 ;
    $_messagingData[ 'customerCache' ][ 'userRecords'   ][ $userRecord[ 'id'           ] ] = $userRecord ;
    $_messagingData[ 'customerCache' ][ 'expirationMap' ][ $userRecord[ 'expirationTs' ] ] = $userRecord[ 'id' ] ;
    $this->log( "==> record User " . $userRecord[ 'id' ] . ", conversationId='" . $userRecord[ 'conversationId' ] . "' in the customers cache", OrLogger::LOG_DEBUG, __METHOD__ ) ;

    if( empty( $messagingData ) ) OrResources::writeDefaultDataFile( $_messagingData ) ;
  }

  private  function getUserFromCache( $userId, &$messagingData = null )
  {
    if(     empty( $messagingData )
        || !array_key_exists( $userId, $messagingData[ 'customerCache' ][ 'userRecords' ] ) )
    {
      return null ;
    }

    $userRecord = $messagingData[ 'customerCache' ][ 'userRecords' ][ $userId ] ;
    unset( $userRecord[ 'expirationTs' ] ) ;
    $this->log( "==> getting userRecord from cache : " . json_encode( $userRecord ), OrLogger::LOG_DEBUG, __METHOD__ ) ;
    return $userRecord ;
  }

  private  function cleanUserCache( &$messagingData = null )
  {
    $this->log( "Cleaning cache...", OrLogger::LOG_DEBUG, __METHOD__ ) ;
    $nbClean = 0 ;
    $_messagingData = &$messagingData ;
    if( empty( $messagingData ) ) $_messagingData = OrResources::readDefaultDataFile() ;

    $nowMs = OrDatetimes::nowMs() ;
    if( $nowMs <= $_messagingData[ 'customerCache' ][ 'nextCheckTs' ] ) return ;
    $_messagingData[ 'customerCache' ][ 'nextCheckTs' ] = $nowMs + $this->customerCacheCheck * 1000 ;

    foreach( $_messagingData[ 'customerCache' ][ 'expirationMap' ] as $ms => $userId )
    {
      if( $nowMs > intval( $ms ) )
      {
        $this->log( "==> removing userId " . $userId . " from cache", OrLogger::LOG_TRACE, __METHOD__ ) ;
        unset( $_messagingData[ 'customerCache' ][ 'userRecords'   ][ $userId ] ) ;
        unset( $_messagingData[ 'customerCache' ][ 'expirationMap' ][ $ms     ] ) ;
        $nbClean++ ;
      }
    }

    if( empty( $messagingData ) ) OrResources::writeDefaultDataFile( $_messagingData ) ;
    $this->log( "Cleaning cache done, nbClean=" . $nbClean, OrLogger::LOG_DEBUG, __METHOD__ ) ;
  }


  /* -------------------
     Entities management
  */
  public   function buildMessageRecord( &$message )
  {
    $messageRecord = [
      'id'             => $message[ 'id'             ],
      'from'           => null,
      'to'             => $message[ 'recipient'      ],
      'message'        => $message[ 'body'           ],
      'conversationId' => $message[ 'conversationId' ],
      'createdAt'      => null,
      'utcDate'        => null,
      'timestamp'      => null,
    ] ;
    
    // Clean prefix keyword if present
    if( self::strStartsWith( $messageRecord[ 'message' ], $this->smsPrefixKeyword, true ) ) $messageRecord[ 'message' ] = substr( $messageRecord[ 'message' ], strlen( $this->smsPrefixKeyword ) ) ;

    // Manage sender
    if( array_key_exists( 'sender', $message ) )
    {
      $messageRecord[ 'from' ] = $message[ 'sender' ] ;
    }
    else
    {
      $messageRecord[ 'from' ] = $this->selfSender ;
    }

    // Manage date
    if( array_key_exists( 'timestamp', $message ) )
    {
      $messageRecord[ 'createdAt' ] = $message[ 'timestamp' ] ;
    }
    else if( array_key_exists( 'sendDate', $message ) )
    {
      $messageRecord[ 'createdAt' ] = $message[ 'sendDate' ] ;
    }
    else
    {
      $messageRecord[ 'createdAt' ] = OrDatetimes::now( OrDatetimes::millisCleanDateTimeFormat( $this->inDateFormat ) ) ;
    }
    $messageRecord[ 'utcDate'     ] = OrDatetimes::universalDateConversion( $messageRecord[ 'createdAt' ], $this->inDateFormat, $this->outDateFormat, $this->outTimezone ) ;
    $messageRecord[ 'timestamp'   ] = OrDatetimes::dateToTs( $messageRecord[ 'utcDate' ], $this->timestampFormat ) ;

    return $messageRecord ;
  }

  public   function buildUserRecord( &$messageRecord )
  {
    $userRecord = [
      'id'             => $messageRecord[ 'from'           ],
      'messageDate'    => $messageRecord[ 'createdAt'      ],
      'messageTs'      => $messageRecord[ 'timestamp'      ],
      'conversationId' => $messageRecord[ 'conversationId' ],
    ] ;
    return $userRecord ;
  }


  /* ---------------------------
     Orange SMS+ request methods
  */

  public   function buildUrl( $resource = null, $params = null )
  {
    $res = $this->baseUrl ;
    if( !empty( $resource ) )
    {
      $res .= $resource ;
    }
    if( !empty( $params ) )
    {
      if( !is_array( $params ) )
      {
        $res .= '?' . $params . '&' ;
      }
      else
      {
        $first    = true ;
        $res .= '?' ;
        foreach( $params as $k => $v )
        {
          if( $first === true ) $first = false ; else $res .= '&' ;
          $res .= $k . '=' . $v ;
        }
      }
    }
    if( !empty( $this->postUrl ) )
    {
      if( !empty( $params ) )
      {
        $res .= '&' ;
      }
      else
      {
        $res .= '?' ;
      }
      $res .= $this->postUrl ;
    }
    return $res ;
  }

  // Generic Orange SMS+ request caller
  public   function orangeSMSRequest( $verb, $params = null, $noRetry = false )
  {
    // Prepare request
    $body       = null ;
    $header     = null ;
    $urlPostfix = null ;
    $urlParams  = null ;
    switch( $verb )
    {
    case 'getAccessToken' :
      $urlPostfix = 'oauth/token' ;
      $header     = [
        'Content-Type' => 'application/x-www-form-urlencoded',
        'Accept'       => 'application/json',
      ] ;
      $body       = [
        'username'     => $this->oauthLogin,
        'password'     => $this->oauthPassword,
      ] ;
      break ;

    case 'readMessages' :
      $header         = [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $this->accessToken,
      ] ;
      $urlPostfix = 'smsplus' ;
      $urlParams  = [] ;
      $fieldsArr = [ 'dateMin', 'pageNumber', 'pageSize' ] ;
      foreach( $fieldsArr as $field )
      {
        if( array_key_exists( $field, $params ) ) $urlParams[ $field ] = $params[ $field ] ;
      }
      break ;

    case 'sendMessage' :
      $header         = [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $this->accessToken,
      ] ;
      $urlPostfix = 'smsplus/conversations/' . $params[ 'conversationId' ] . '/replies' ;
      $message    = $params[ 'message'  ] ;
      $encoding   = $params[ 'encoding' ] ;
      $body           = '{
        "body":"'     . $params[ 'message'  ] . '", 
        "encoding":"' . $params[ 'encoding' ] . '"
      }' ;
      break ;

    default :
      $this->log( "==> Invalid verb '" . $verb . "'", OrLogger::LOG_ERROR, __METHOD__ ) ;
      return null ;
    }

    $logstr = "Orange SMS+ request '" . $verb . "'" ;
    if( !empty( $urlParams ) ) $logstr .= ", params=" . json_encode( $urlParams ) ;
    if( !empty( $body      ) ) $logstr .= ", body  =" . json_encode( $body      ) ;
    $this->log( $logstr, OrLogger::LOG_INFOP, __METHOD__ ) ;

    $requestUrl    = $this->buildUrl( $urlPostfix, $urlParams ) ;
    $this->log( "Request URL = " . $requestUrl, OrLogger::LOG_DEBUG, __METHOD__ ) ;
    $requestResult = OrWebs::restRequest( $requestUrl, $body, $header ) ;
    $this->log( "==> Result : " . json_encode( $requestResult[ OrWebs::REST_REQUEST_RESULT ] ), OrLogger::LOG_TRACE, __METHOD__ ) ;

    // Manage result depending on verb
    $res = null ;
    switch( $verb )
    {
    case 'getAccessToken' :
      if( $requestResult[ OrWebs::REST_REQUEST_STATUS ] !== true || $requestResult[ OrWebs::REST_REQUEST_HTTPCODE ] !== 200 )
      {
        $this->log( "==> KO request : " . json_encode( $requestResult ), OrLogger::LOG_ERROR, __METHOD__ ) ;
        return null ;
      }
      if( !array_key_exists( 'access_token', $requestResult[ OrWebs::REST_REQUEST_RESULT ] ) )
      {
        $this->log( "==> malformed result : " . json_encode( $requestResult ), OrLogger::LOG_ERROR, __METHOD__ ) ;
        return null ;
      }
      $this->accessToken = $requestResult[ OrWebs::REST_REQUEST_RESULT ][ 'access_token' ] ;
      $res = $this->accessToken ;
      break ;

    case 'readMessages' :
      // Request error
      if( $requestResult[ OrWebs::REST_REQUEST_STATUS ] !== true )
      {
        // Invalid token case : renew and retry the request one time
        if( $requestResult[ OrWebs::REST_REQUEST_HTTPCODE ] === 401 )
        {
          $this->log( "==> Invalid access token : renewal attempt...", OrLogger::LOG_DEBUG, __METHOD__ ) ;
          $tmp = $this->orangeSMSRequest( 'getAccessToken', null, true ) ;
          if( empty( $tmp ) )
          {
            $this->log( "==> Unable to renew access token : " . json_encode( $requestResult ), OrLogger::LOG_ERROR, __METHOD__ ) ;
            return null ;
          }
          if( $noRetry === true )
          {
            $this->log( "==> No more retries : KO request : " . json_encode( $requestResult ), OrLogger::LOG_ERROR, __METHOD__ ) ;
            return null ;
          }
          $requestResult = $this->orangeSMSRequest( $verb, $params, true ) ;
          if( $requestResult[ OrWebs::REST_REQUEST_STATUS ] !== true )
          {
            $this->log( "==> KO request : " . json_encode( $requestResult ), OrLogger::LOG_ERROR, __METHOD__ ) ;
            return null ;
          }
          $res = $requestResult ;
        }
        // Other kind of error
        else
        {
          $this->log( "==> KO request : " . json_encode( $requestResult ), OrLogger::LOG_ERROR, __METHOD__ ) ;
          return null ;
        }
      }
      // No error
      else
      {
        $res = $requestResult ;
      }
      if( empty( $res ) )
      {
        $this->log( "==> KO request : " . json_encode( $requestResult ), OrLogger::LOG_ERROR, __METHOD__ ) ;
        return null ;
      }
      $res = $requestResult[ OrWebs::REST_REQUEST_RESULT ] ;
      break ;

    case 'sendMessage' :
      // Request error
      if( $requestResult[ OrWebs::REST_REQUEST_STATUS ] !== true )
      {
        // Invalid token case : renew and retry the request one time
        if( $requestResult[ OrWebs::REST_REQUEST_HTTPCODE ] === 401 )
        {
          $this->log( "==> Invalid access token : renewal attempt...", OrLogger::LOG_DEBUG, __METHOD__ ) ;
          $tmp = $this->orangeSMSRequest( 'getAccessToken', null, true ) ;
          if( empty( $tmp ) )
          {
            $this->log( "==> Unable to renew access token : " . json_encode( $requestResult ), OrLogger::LOG_ERROR, __METHOD__ ) ;
            $res = [
              'success' => false,
              'error'   => "Can't renew access token",
              'result'  => null,
            ] ;
            return $res ;
          }
          if( $noRetry === true )
          {
            $this->log( "==> No more retries : KO request : " . json_encode( $requestResult ), OrLogger::LOG_ERROR, __METHOD__ ) ;
            $res = [
              'success' => false,
              'error'   => "Several request attempts have failed",
              'result'  => null,
            ] ;
            return $res ;
          }
          // Renew send request
          $requestResult = $this->orangeSMSRequest( $verb, $params, true ) ;
          if( $requestResult[ 'success' ] !== true )
          {
            $this->log( "==> KO request : " . json_encode( $requestResult ), OrLogger::LOG_ERROR, __METHOD__ ) ;
            return $requestResult ;
          }
          $res = $requestResult ;
        }
        // Possibly message too long
        else if( $requestResult[ OrWebs::REST_REQUEST_HTTPCODE ] === 400 )
        {
          if(    !empty( $requestResult[ OrWebs::REST_REQUEST_RESULT ] )
              &&  array_key_exists( 'code', $requestResult[ OrWebs::REST_REQUEST_RESULT ][0] )
              &&  $requestResult[ OrWebs::REST_REQUEST_RESULT ][0][ 'code' ] === 'SmsTooLong' )
          {
            $this->log( "==> KO request : 'SMS too long' issue : " . json_encode( $requestResult ), OrLogger::LOG_ERROR, __METHOD__ ) ;
            $res = [
              'success' => false,
              'error'   => 'SmsTooLong',
              'result'  => null,
            ] ;
            return $res ;
          }
          else
          {
            $this->log( "==> KO request : " . json_encode( $requestResult ), OrLogger::LOG_ERROR, __METHOD__ ) ;
            $res = [
              'success' => false,
              'error'   => !empty( $requestResult[ OrWebs::REST_REQUEST_RESULT ] )
              && array_key_exists( 'code', $requestResult[ OrWebs::REST_REQUEST_RESULT ][0] ) ? $requestResult[ OrWebs::REST_REQUEST_RESULT ][0][ 'code' ] : 'Unknown error code 400',
              'result'  => null,
            ] ;
            return $res ;
          }
        }
        // Other kind of error
        else
        {
          $this->log( "==> KO request : " . json_encode( $requestResult ), OrLogger::LOG_ERROR, __METHOD__ ) ;
          $res = [
            'success' => false,
            'error'   => !empty( $requestResult[ OrWebs::REST_REQUEST_RESULT ] )
            && array_key_exists( 'code', $requestResult[ OrWebs::REST_REQUEST_RESULT ][0] ) ? $requestResult[ OrWebs::REST_REQUEST_RESULT ][0][ 'code' ] : 'Unknown error code ' . $requestResult[ OrWebs::REST_REQUEST_HTTPCODE ],
            'result'  => null,
          ] ;
          return $res ;
        }
      }
      // No error
      else
      {
        $res = [
          'success' => true,
          'error'   => null,
          'result'  => $requestResult[ OrWebs::REST_REQUEST_RESULT ],
        ] ;
      }
      break ;
    }

    $this->log( "==> Request OK", OrLogger::LOG_INFOP, __METHOD__ ) ;
    return $res ;
  }


  /* ----------------------
     Connector entry points
  */

  public   function readMessages( $lastReadMessageDate )
  {
    $res                   = [
      'lastReadMessageDate'  => $lastReadMessageDate,
      'newMessages'          => [],
    ] ;
    $messagingData         = OrResources::readDefaultDataFile() ;

    $slog = "Fetching Orange SMS+ messages" ;
    if( !empty( $lastReadMessageDate ) ) $slog .= ", lastReadMessageDate=" . $lastReadMessageDate ;
    $slog .= "..." ;
    $this->log( $slog, OrLogger::LOG_INFO, __METHOD__ ) ;

    $lastReadMessageTs      = null ;
    if( !empty( $lastReadMessageDate ) ) $lastReadMessageTs = OrDatetimes::dateToTs( $lastReadMessageDate, $this->timestampFormat ) ;
    $newlastReadMessageDate = $lastReadMessageDate ;
    $newlastReadMessageTs   = $lastReadMessageTs   ;
    $messagePageNb          = 1 ;
    $readParams             = [
      'pageSize'              => $this->messagesLimit,
      'pageNumber'            => strval( $messagePageNb ),
    ] ;
    if( !empty( $lastReadMessageDate ) ) $readParams[ 'dateMin' ] = $lastReadMessageDate ;
    $newMsgNb               = 0 ;
    // Loop on messages with pagination
    while( true )
    {
      $pageMessages = $this->orangeSMSRequest( 'readMessages', $readParams ) ;
      if( empty( $pageMessages ) || !array_key_exists( 'content', $pageMessages ) )
      {
        $this->log( "ERROR : read messages request has failed", OrLogger::LOG_ERROR, __METHOD__ ) ;
        return null ;
      }
      foreach( $pageMessages[ 'content' ] as $message )
      {
        $messageRecord = $this->buildMessageRecord( $message ) ;
        $utcDate       = $messageRecord[ 'utcDate'   ] ;
        $messageTs     = $messageRecord[ 'timestamp' ] ;

        $this->log( "==> new message : " . json_encode( $messageRecord ), OrLogger::LOG_DEBUG, __METHOD__ ) ;

        // Manage last read message date
        // ---
        // Really first request, lastReadMessageDate is null
        if( empty( $newlastReadMessageDate ) )
        {
          $newlastReadMessageDate = $utcDate   ;
          $newlastReadMessageTs   = $messageTs ;
          $messagingData[ 'lastReadMessageIds' ][] = $messageRecord[ 'id' ] ;
        }
        // Message date is the same than the last read message date : skip if already read, or add it to the read list
        else if( $messageTs === $newlastReadMessageTs )
        {
          // If the message has already been read
          if( in_array( $messageRecord[ 'id' ], $messagingData[ 'lastReadMessageIds' ] ) ) continue ;
          $messagingData[ 'lastReadMessageIds' ][] = $messageRecord[ 'id' ] ;
        }
        // Message is newer than the last one : update the last read message context
        else if( $messageTs > $newlastReadMessageTs )
        {
          $newlastReadMessageDate = $utcDate   ;
          $newlastReadMessageTs   = $messageTs ;
          $messagingData[ 'lastReadMessageIds' ] = [ $messageRecord[ 'id' ] ] ;  // Wipe the previous array and put this message id
        }
          
        // Add the new message to the result list
        $newMsgNb++ ;
        $res[ 'newMessages' ][ $messageRecord[ 'timestamp' ] ] = $messageRecord ;

        // Manage userRecord
        $userRecord = $this->buildUserRecord( $messageRecord ) ;

        // Customer cache management
        // => we must store the map userId <==> last conversationId in the cache in order the agents can respond to the user
        $this->addUserToCache( $userRecord, $messagingData ) ;
      }

      // Check if we must exit the loop : no content, or page "last" === true
      if( empty( $pageMessages[ 'content' ] ) || array_key_exists( 'last', $pageMessages ) && $pageMessages[ 'last' ] === true ) break ;

      // Prepare the next loop
      $messagePageNb++ ;
      $readParams[ 'pageNumber' ] = strval( $messagePageNb ) ;
    }

    // If new messages occured, save data files
    if( $newlastReadMessageDate !== $lastReadMessageDate )
    {
      // Clean cache before saving the main data file
      $this->cleanUserCache( $messagingData ) ;

      // Save main data file
      OrResources::writeDefaultDataFile( $messagingData ) ;
    }

    $res[ 'lastReadMessageDate' ] = $newlastReadMessageDate ;
    $this->log( "Read message(s) : " . count( $res[ 'newMessages' ] ) . " results", OrLogger::LOG_INFO, __METHOD__ ) ;
    
    // Finally, sort message array by timestamp (useless ?)
    ksort( $res[ 'newMessages' ] ) ;

    return $res ;
  }
  
  public   function sendMessage( $to, $message )
  {
    $messagingData = OrResources::readDefaultDataFile() ;

    $userRecord = $this->getUserFromCache( $to, $messagingData ) ;
    if( empty( $userRecord ) )
    {
      $this->log( "Unknown user '" . $to . "', or expired conversation. Unable to send message...", OrLogger::LOG_WARN, __METHOD__ ) ;
      return false ;
    }

    $conversationId = $userRecord[ 'conversationId' ] ;
    $this->log( "Sending message to user '" . $to . "', conversationId='" . $conversationId .  "', message='" . $message . "'...", OrLogger::LOG_INFO, __METHOD__ ) ;

    $params = [
      'conversationId'  => $conversationId,
      'message'         => $message,
      'encoding'        => $this->outEncoding,
    ] ;
    $requestResult = $this->orangeSMSRequest( 'sendMessage', $params ) ;
    if( $requestResult[ 'success' ] === false )
    {
      $this->log( "ERROR : send message request to user '" . $to . " has failed, error='" . $requestResult[ 'error' ] . "'", OrLogger::LOG_ERROR, __METHOD__ ) ;
      return false ;
    }

    $messageRecord = $this->buildMessageRecord( $requestResult[ 'result' ] ) ;

    OrResources::writeDefaultDataFile( $messagingData ) ;
    $this->log( "==> message sent : " . json_encode( $messageRecord ), OrLogger::LOG_DEBUG, __METHOD__ ) ;

    return true ;
  }


  /* **************************************************************************
     Inner Tools
  */

  public function getClassName()
  {
    return $this->_parent->getClassName() ;
  }
  private  function log( $str, $level = OrLogger::LOG_DEBUG, $method = '', $indentLevel = 0 )
  {
    $this->_parent->logger->log( $str, $level, $method, $indentLevel ) ;
  }
  private  function getConf( $confKey, $key = null )
  {
    return $this->_parent->getConf( $confKey, $key ) ;
  }

  public static function strStartsWith( &$str, &$searchStr, $caseInsensitive = false )
  {
    $_str       = $str ;
    $_searchStr = $searchStr ;
    if( $caseInsensitive === true )
    {
      $_str       = strtolower( $str       ) ;
      $_searchStr = strtolower( $searchStr ) ;
    }
    return $_searchStr === "" || strrpos( $_str, $_searchStr, -strlen( $_str ) ) !== false ;
  }
}


/* ****************************************************************************
   Logger Helper
*/
class OrLogger
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
class OrWebs
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
class OrDatetimes
{
  // Consts
  const DEFAULT_TIMEZONE        = 'Europe/Paris' ;
  const DEFAULT_FORMAT_DATE     =          'Ymd' ;
  const DEFAULT_FORMAT_DATETIME =      'Ymd_His' ;

  // Current timestamp
  public static function nowTs()
  {
    return time() ;
  }
  public static function nowMs()
  {
    return round( microtime( true ) * 1000 ) ;
  }
  // Now datetime, returned as a string of given format
  public static function now( $format = self::DEFAULT_FORMAT_DATETIME, $timezone = self::DEFAULT_TIMEZONE )
  {
    return ( new DateTime( 'now', new DateTimeZone( $timezone ) ) )->format( $format ) ;
  }

  public static function dateToTs( $date, $format = self::DEFAULT_FORMAT_DATE, $timezone = self::DEFAULT_TIMEZONE )
  {
    return DateTime::createFromFormat( $format, $date, new DateTimeZone( $timezone ) )-> getTimestamp() ;
  }
  
  // Example :
  //   dateStr        = '2019-07-04T17:15:44.000+02:00'
  //   inFormat       = 'Y-m-d\TH:i:s.uP'
  //   outFormat      = 'Y-m-d\TH:i:s.v'
  //   targetTimezone = 'UTC' or 'Europe/Paris'
  //   ==> res        = '2019-07-04T15:15:44.000'
  public static function universalDateConversion( $dateStr, $inFormat, $outFormat, $targetTimezone = null )
  {
    $datetime = DateTime::createFromFormat( $inFormat, $dateStr ) ;
    if( !empty( $targetTimezone ) ) $datetime->setTimezone( timezone_open( $targetTimezone ) ) ;
    return $datetime->format( $outFormat ) ;
  }

  // DateTime::createFromFormat does not support the Date 'v' format character for millis, it only supports 'u' for millis or micros (which is dumb but real, yep...)
  public static function cleanDateTimeFormat( $dateTimeFormat )
  {
    $arr = explode( '.v', $dateTimeFormat, 2 ) ;
    if( count( $arr ) === 1 ) return $dateTimeFormat ;
    return implode( '.u', $arr ) ;
  }

  public static function millisCleanDateTimeFormat( $dateTimeFormat )
  {
    $arr = explode( '.u', $dateTimeFormat, 2 ) ;
    if( count( $arr ) === 1 ) return $dateTimeFormat ;
    return implode( '.v', $arr ) ;
  }
}


/***********************************************
  Resources
  ---
  Resources capabilities for 'Modules' or 'SubModules' objects
  The purpose of this tool is to manage files in the ./data/<ModuleName>/ folder
  => for this reason, only pass file names ; the data folder path is automatically resolved
  */
class OrResources
{
  const DATA_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR ;

  public static function getDataFolderPath( $shift = 0 )
  {
    return self::_getDataFolderPath( self::_getModuleName( $shift ) ) ;
  }
  public static function _getDataFolderPath( $moduleName )
  {
    return self::DATA_PATH . $moduleName . DIRECTORY_SEPARATOR ;
  }

  private static function _getFileName( $name )
  {
    return $name . '.json' ;
  }

  public  static function  getModuleName()
  {
    return self::_getModuleName() ;
  }
  private static function _getModuleName( $shift = 0 )
  {
    $modName = '' ;
    try { $modName = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 3 + $shift )[ 2 + $shift ][ 'object' ]->getClassName() ; } catch( \Exception $e ) {}
    return $modName ;
  }

  public static function createDataFolderPath( $pFolderPath = null )
  {
    $folderPath = $pFolderPath ;
    if( $folderPath === null ) $folderPath = self::getDataFolderPath( 1 ) ;
    if( is_dir( $folderPath ) ) return ;
    mkdir( $folderPath ) ;
  }

  public static function existsDataFile( $name, $_moduleName = null )
  {
    $moduleName = empty( $_moduleName ) ? self::_getModuleName() : $_moduleName ;
    $folderPath = self::_getDataFolderPath( $moduleName )  ;
    if( !is_dir( $folderPath    ) ) return false ;
    $filename   = self::_getFileName( $name ) ;
    $filepath   = $folderPath . $filename ;
    if( !file_exists( $filepath ) ) return false ;
    return true ;
  }
  public static function existsDefaultDataFile()
  {
    $moduleName = self::_getModuleName() ;
    return self::existsDataFile( $moduleName, $moduleName ) ;
  }

  public static function readDataFile( $name, $_moduleName = null )
  {
    $moduleName = empty( $_moduleName ) ? self::_getModuleName() : $_moduleName ;
    $res = null ;
    $folderPath = self::_getDataFolderPath( $moduleName )  ;
    if( !is_dir( $folderPath    ) ) return $res ;
    $filename   = self::_getFileName( $name ) ;
    $filepath   = $folderPath . $filename ;
    if( !file_exists( $filepath ) ) return $res ;
    $fileContent = file_get_contents( $filepath ) ;
    $res = json_decode( $fileContent, true ) ;
    if( $res === null ) $res = $fileContent ;
    return $res ;
  }
  public static function readDefaultDataFile()
  {
    $moduleName = self::_getModuleName() ;
    return self::readDataFile( $moduleName, $moduleName ) ;
  }

  public static function writeDataFile( $name, $content, $_moduleName = null )
  {
    $moduleName = empty( $_moduleName ) ? self::_getModuleName() : $_moduleName ;
    $folderPath = self::_getDataFolderPath( $moduleName )  ;
    self::createDataFolderPath( $folderPath ) ;
    $filename   = self::_getFileName( $name ) ;
    $filepath   = $folderPath . $filename ;
    $_content   = json_encode( $content, JSON_PRETTY_PRINT ) ;
    $res = file_put_contents( $filepath, $_content ) ;
    return $res ;
  }
  public static function writeDefaultDataFile( $content )
  {
    $moduleName = self::_getModuleName() ;
    return self::writeDataFile( $moduleName, $content, $moduleName ) ;
  }
}


/* ****************************************************************************
   Command Line Tester
*/
class OrCommandLineTester
{
  const Verb = 'test' ;


  public    function __construct()
  {
    $connectorClass = "UserFiles\\Messaging\\Connector\\" . ORSAMPLE_CONNECTOR ;
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
      'purpose'  => 'Authentication',
      'function' => function()
      {
        // An access token is automatically requested during the MessagingManager instance initialization
        echo "AccessToken = " . $this->connector->_msgManager->accessToken . "\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test02' ] = [
      'purpose'  => 'Read Messages',
      'function' => function()
      {
        //$dateMin = '2019-07-08T13:43:57.000Z' ;
        $dateMin = null ;
        $res     = $this->connector->_msgManager->readMessages( $dateMin ) ;
        echo "RES    : \n" . json_encode( $res, JSON_PRETTY_PRINT ) . "\n" ;
        echo "RES Nb : \n" . count( $res[ "newMessages" ] ) . "\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test03' ] = [
      'purpose'  => 'Send Message',
      'function' => function()
      {
        $to      = 'xxxxxxxxxxxx' ;
        $msg     = "Hello Orange SMS+ !" ;
        $res     = $this->connector->_msgManager->sendMessage( $to, $msg ) ;
        echo "RES : \n" . json_encode( $res, JSON_PRETTY_PRINT ) . "\n" ;
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
if( php_sapi_name() == 'cli' && !empty( getopt( null, [ OrCommandLineTester::Verb . ":" ] ) ) )
{
  // Usage example :
  // > php <ConnectorName>.php -f --test=00
  new OrCommandLineTester() ;
}
?>