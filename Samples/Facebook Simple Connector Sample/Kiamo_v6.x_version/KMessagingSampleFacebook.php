<?php
namespace UserFiles\Messaging\Connector ;

define( "FBSAMPLE_CONNECTOR", "KMessagingSampleFacebook" ) ;


const FACEBOOK_SAMPLE_KIAMO_ROOT              = __DIR__ . "/../../../../../" ;
const FACEBOOK_SAMPLE_KIAMO_MESSAGING_UTILITY = FACEBOOK_SAMPLE_KIAMO_ROOT . "www/Symfony/src/Kiamo/Bundle/AdminBundle/Utility/Messaging/" ;

require_once FACEBOOK_SAMPLE_KIAMO_MESSAGING_UTILITY . "ParameterBag.php"              ;
require_once FACEBOOK_SAMPLE_KIAMO_MESSAGING_UTILITY . "ConnectorConfiguration.php"    ;
require_once FACEBOOK_SAMPLE_KIAMO_MESSAGING_UTILITY . "GenericConnectorInterface.php" ;

use Kiamo\Bundle\AdminBundle\Utility\Messaging\ParameterBag              ;
use Kiamo\Bundle\AdminBundle\Utility\Messaging\ConnectorConfiguration    ;
use Kiamo\Bundle\AdminBundle\Utility\Messaging\GenericConnectorInterface ;


use \DateTime, \DateTimeZone ;


// Kiamo Messaging Connector
// ---
class KMessagingSampleFacebook implements GenericConnectorInterface
{
  const RootPath = __DIR__ ;

  public function __construct( ConnectorConfiguration $configuration )
  {
    $this->initConfig( $configuration ) ;
    $this->logger      = new FbLogger(           $this ) ;
    $this->log( "------------------------------------------------------------------------------", FbLogger::LOG_INFOP, __METHOD__ ) ;
    $this->_msgManager = new FbMessagingManager( $this ) ;
    $this->log( "INIT : OK", FbLogger::LOG_INFOP, __METHOD__ ) ;
    $this->log( "------------------------------------------------------------------------------", FbLogger::LOG_INFOP, __METHOD__ ) ;
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
      'service'                     => 'Simple Connector Facebook',
      'version'                     => 'sample',
    ] ;

    // Runtime configuration
    // ---
    $this->runtimeConf = [
      'logLevel'                    => FbLogger::LOG_TRACE,
      'pagination'                  => [
        'limitPerRequestConversations' =>  '10',
        'limitPerRequestMessages'      =>  '25',
      ],
      'datetimes'                   => [
        'dateFormat'                   => 'Y-m-d\TH:i:s\+0000',
      ],
    ] ;

    // External Messaging API Access configuration
    // ---
    $this->accessConf = [
      'apiBaseUrl'                  => 'https://graph.facebook.com',
      'apiVersion'                  => 'v3.3',
      'appName'                     => 'xxxxxxxxxxxx',
      'appId'                       => 'xxxxxxxxxxxxxxxx',
      'appSecret'                   => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
      'accessToken'                 =>  'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
      'pageName'                    => 'xxxxxxxxxxxx',
      'pageId'                      => 'xxxxxxxxxxxxxx',
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
    $this->log( "Fetching message(s)", FbLogger::LOG_INFO, __METHOD__ ) ;

    $params             = $parameterBag->getParameters() ;
    $lastReadMessageKey = FBSAMPLE_CONNECTOR . '.lastReadMessageId' ;
    $lastReadMessageTs  = '' ;
    if( array_key_exists( $lastReadMessageKey, $params ) ) $lastReadMessageTs = $params[ $lastReadMessageKey ] ;
    if( !empty( $lastReadMessageTs ) ) $this->log( "==> lastMessageTs=" . $lastReadMessageTs, FbLogger::LOG_DEBUG, __METHOD__ ) ;

    $msgRes             = $this->_msgManager->readMessages( $lastReadMessageTs ) ;  // read all unread user messages from the messaging address
    $msgArr             = array_reverse( $msgRes[ 'newMessages' ] ) ;
    $this->log( "Fetched " . count( $msgArr ) . " message(s)", FbLogger::LOG_INFO, __METHOD__ ) ;
    if( $lastReadMessageTs !== $msgRes[ 'lastReadMessageTs' ] )
    {
      $this->log( "==> new lastMessageTs=" . $msgRes[ 'lastReadMessageTs' ], FbLogger::LOG_DEBUG, __METHOD__ ) ;
      $parameterBag->setParameter( $lastReadMessageKey, $msgRes[ 'lastReadMessageTs' ] ) ;
    }

    foreach( $msgArr as $msg )
    {
      $this->log( "==> New message : " . json_encode( $msg ), FbLogger::LOG_TRACE, __METHOD__ ) ;
      $inputMsg = [
        'id'         => $msg[ "id"       ],
        'createdAt'  => $msg[ "date"     ],
        'senderId'   => $msg[ "uuid"     ],
        'senderName' => $msg[ "userName" ],
        'content'    => $msg[ "message"  ],
      ] ;

      // Special case : history before connector
      if( $msg[ "from" ][ "id" ] === $this->getConf( 'access', 'pageId' ) )
      {
        $inputMsg[ "content" ] = '[' . $this->getConf( 'access', 'pageName' ) . '] ' . $msg[ "message" ] ;
      }

      $this->log( "=> adding message : " . json_encode( $inputMsg ), FbLogger::LOG_INFO, __METHOD__ ) ;
      $parameterBag->addMessage( $inputMsg ) ;
    }
    
    return $parameterBag;
  }

  public function send( array $messageTask )
  {
    $this->log( "Sending message : " . json_encode( $messageTask ), FbLogger::LOG_INFO, __METHOD__ ) ;

    $msg            = $messageTask[ "content" ]         ;
    $uuid           = $messageTask[ "to"      ][ "id" ] ;
    $idArr          = explode( '.', $uuid, 2 ) ;
    $conversationId = $idArr[ 0 ] ;
    $to             = $idArr[ 1 ] ;

    $this->log( "Sending message to user id '" . $to . "', conversationId='" . $conversationId . "' : '" . $msg . "'", FbLogger::LOG_INFO, __METHOD__ ) ;
    $this->_msgManager->sendMessage( $to, $msg, $conversationId ) ;

    return true ;
  }


  /* **************************************************************************
     Inner Tools
  */

  public   function log( $str, $level = FbLogger::LOG_DEBUG, $method = '', $indentLevel = 0 )
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
class FbMessagingManager
{
  public    function __construct( &$_parent )
  {
    $this->_parent = $_parent ;

    $this->log( "Service : " . $this->getConf( 'self', 'service' )                              , FbLogger::LOG_DEBUG, __METHOD__ ) ;
    $this->log( "Version : " . $this->getConf( 'self', 'version' )                              , FbLogger::LOG_DEBUG, __METHOD__ ) ;
    $this->log( "INIT : OK"                                                                     , FbLogger::LOG_TRACE, __METHOD__ ) ;
    
    $this->initRuntimeData()   ;
    $this->initAccessData()    ;
  }

  public   function initRuntimeData()
  {
    $this->selfName                  = $this->getConf( "access" , "pageName"                                 ) ;
    $this->selfId                    = $this->getConf( "access" , "pageId"                                   ) ;

    $this->conversationsLimit        = $this->getConf( "runtime", "pagination.limitPerRequestConversations"  ) ;
    $this->messagesLimit             = $this->getConf( "runtime", "pagination.limitPerRequestMessages"       ) ;

    $this->dateFormat                = $this->getConf( "runtime", "datetimes.dateFormat"                     ) ;
  }

  private  function initAccessData()
  {
    $fieldsArr = [ 'apiBaseUrl', 'apiVersion', 'appName', 'appId', 'appSecret', 'pageName', 'pageId', 'accessToken' ] ;
    foreach( $fieldsArr as $field )
    {
      $this->$field = $this->getConf( "access", $field ) ;
      $this->log( "Access data : " . $field . " = " . $this->$field, FbLogger::LOG_INFO, __METHOD__ ) ;
    }
    $this->baseUrl = $this->apiBaseUrl . '/' . $this->apiVersion . '/' ;
    $this->postUrl = 'access_token=' . $this->accessToken ;
  }


  /* ------------------------
     Facebook request methods
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
    else
    {
      $res .= '?' ;
    }
    if( !empty( $this->postUrl ) )
    {
      if( !empty( $params ) ) $res .= '&' ;
      $res .= $this->postUrl ;
    }
    return $res ;
  }

  // Generic Facebook request caller
  public   function facebookRequest( $verb, $entityId = null, $urlParams = null )
  {
    $resource  = '' ;
    $_entityId = $entityId ;
    $body      = null ;
    $header    = null ;
    if( empty( $entityId ) ) $_entityId = $this->pageId ;
    switch( $verb )
    {
    case 'getConversation' :
      $urlParams[ 'fields' ] = 'id,updated_time,participants' ;
      $resource = $_entityId ;
      break ;
    case 'getConversations' :
      $urlParams[ 'fields' ] = 'id,updated_time' ;
      if( !empty( $this->conversationsLimit ) ) $urlParams[ 'limit' ] = $this->conversationsLimit ;
      $resource = $_entityId . '/conversations' ;
      break ;
    case 'getMessages' :
      $urlParams[ 'fields' ] = 'id,from,message,created_time' ;
      if( !empty( $this->messagesLimit      ) ) $urlParams[ 'limit' ] = $this->messagesLimit      ;
      $resource = $_entityId . '/messages' ;
      break ;
    case 'sendMessage' :
      $body      = [
        "messaging_type" => "RESPONSE",
        "recipient"      => [
          'id'              => $urlParams[ 'to'      ],
        ],
        "message"        => [
          'text'            => $urlParams[ 'message' ],
        ],
      ] ;
      $urlParams = null ;
      $header    = [ 
        "Content-Type" => "application/json"
      ] ;
      $resource = $_entityId . '/messages' ;
      break ;
    default :
      $this->log( "==> Invalid verb '" . $verb . "'", FbLogger::LOG_ERROR, __METHOD__ ) ;
      return null ;
    }

    $logstr = "Facebook request '" . $verb . "', entityId='" . $_entityId . "'" ;
    if( !empty( $urlParams ) ) $logstr .= ", urlParams=" . json_encode( $urlParams ) ;
    if( !empty( $body      ) ) $logstr .= ", body=" . json_encode( $body ) ;
    $this->log( $logstr, FbLogger::LOG_INFOP, __METHOD__ ) ;

    $requestUrl    = $this->buildUrl( $resource, $urlParams ) ;
    $this->log( "Request URL = " . $requestUrl, FbLogger::LOG_TRACE, __METHOD__ ) ;
    $requestResult = FbWebs::restRequest( $requestUrl, $body, $header ) ;
    if( $requestResult[ FbWebs::REST_REQUEST_STATUS ] !== true || $requestResult[ FbWebs::REST_REQUEST_HTTPCODE ] !== 200 )
    {
      $this->log( "==> KO request : " . json_encode( $requestResult ), FbLogger::LOG_ERROR, __METHOD__ ) ;
      return null ;
    }
    $this->log( "==> Request OK", FbLogger::LOG_INFOP, __METHOD__ ) ;
    $this->log( "==> Result : " . json_encode( $requestResult[ FbWebs::REST_REQUEST_RESULT ] ), FbLogger::LOG_TRACE, __METHOD__ ) ;
    return $requestResult[ FbWebs::REST_REQUEST_RESULT ] ;
  }


  public   function getFacebookConversationRecipientRecord( $conversationId )
  {
    $callRes = $this->facebookRequest( 'getConversation', $conversationId ) ;
    if( empty( $callRes ) ) return null ;
    $res = null ;
    foreach( $callRes[ "participants" ][ "data" ] as $userRecord )
    {
      if( $userRecord[ 'id' ] === $this->selfId ) continue ;
      $res = $userRecord ;
      break ;
    }
    $res[ 'conversationId' ] = $conversationId ;
    return $res ;
  }


  /* ----------------------
     Connector entry points
  */

  public   function readMessages( $lastReadMessageTs )
  {
    $res                   = [
      'lastReadMessageTs'    => $lastReadMessageTs,
      'newMessages'          => [],
    ] ;

    $slog = "Fetching Facebook messages" ;
    if( !empty( $lastReadMessageTs ) ) $slog .= ", lastReadMessageTs=" . $lastReadMessageTs ;
    $slog .= "..." ;
    $this->log( $slog, FbLogger::LOG_INFO, __METHOD__ ) ;

    $newLastReadMessageTs      = $lastReadMessageTs ;
    $foundLastReadConversation = false ;
    $newMsgNb                  = 0 ;
    $convsUrlParams            = [] ;
    // Loop on conversations with pagination
    while( true )
    {
      $conversations = $this->facebookRequest( 'getConversations', null, $convsUrlParams ) ;
      if( empty( $conversations ) ) break ;
      $this->log( "==> " . count( $conversations[ 'data' ] ) . " paging conversation(s)", FbLogger::LOG_INFO, __METHOD__ ) ;

      // Loop on conversations returned on this pagination page
      foreach( $conversations[ 'data' ] as $conversation )
      {
        $conversationId = $conversation[ 'id' ] ;
        $conversationTs = FbDatetimes::dateToTs( $conversation[ 'updated_time' ], $this->dateFormat ) ;
        if( $conversationTs <= $lastReadMessageTs )
        {
          $this->log( "==> already read conversation, stop reading here", FbLogger::LOG_DEBUG, __METHOD__ ) ;
          $foundLastReadConversation = true ;
          break ;
        }

        // Loop on conversation messages with pagination
        $foundLastReadMessage = false ;
        $msgsUrlParams        = [] ;
        while( true )
        {
          $messages = $this->facebookRequest( 'getMessages', $conversation[ 'id' ], $msgsUrlParams ) ;
          if( empty( $messages ) ) break ;
          $this->log( "===> " . count( $messages[ 'data' ] ) . " paging message(s)", FbLogger::LOG_INFO, __METHOD__ ) ;
          $this->log( "Full Messages : " . json_encode( $messages ), FbLogger::LOG_VERBOZE, __METHOD__ ) ;

          // New or updated conversation : loop on conversation messages
          foreach( $messages[ 'data' ] as $message )
          {
            //$this->log( "Full Message Data : " . json_encode( $message ), FbLogger::LOG_VERBOZE, __METHOD__ ) ;

            $messageTs = FbDatetimes::dateToTs( $message[ 'created_time' ], $this->dateFormat ) ;
            if( $messageTs <= $lastReadMessageTs )
            {
              $this->log( "==> already read message, stop reading here", FbLogger::LOG_DEBUG, __METHOD__ ) ;
              $foundLastReadMessage = true ;
              break ;
            }

            // Message sent by support user : consider it as already read
            if( $message[ 'from' ][ 'id' ] === $this->selfId )
            {
              $messageRecord = [
                'conversationId' => $conversationId,
                'id'             => $message[ 'id'           ],
                'date'           => $message[ 'created_time' ],
                'from'           => [
                  'id'              => $message[ 'from'      ][ 'id'   ],
                  'name'            => $message[ 'from'      ][ 'name' ],
                ],
                'userName'       => '',
                'message'        => $message[ 'message'      ],
              ] ;
              $this->log( "==> skipping self message in conversation id '" . $conversationId . "' : " . json_encode( $messageRecord ), FbLogger::LOG_DEBUG, __METHOD__ ) ;
              if( $newLastReadMessageTs === $lastReadMessageTs ) $newLastReadMessageTs = $messageTs ;   // Assuming messages are sorted in reverse-chronological order
              continue ;
            }

            // Create message record
            $newMsgNb++  ;
            $messageRecord = [
              'conversationId' => $conversationId,
              'id'             => $message[ 'id'           ],
              'date'           => $message[ 'created_time' ],
              'from'           => [
                'id'              => $message[ 'from'      ][ 'id'   ],
                'name'            => $message[ 'from'      ][ 'name' ],
              ],
              'userName'       => $message[ 'from'      ][ 'name' ],
              'message'        => $message[ 'message'      ],
            ] ;
            if( $newLastReadMessageTs === $lastReadMessageTs ) $newLastReadMessageTs = $messageTs ;   // Assuming messages are sorted in reverse-chronological order
          
            $this->log( "==> new  message from user '" . $messageRecord[ 'from' ][ 'name' ] . "', conversationId='" . $conversationId . "' : '" . $messageRecord[ 'message' ] . "'", FbLogger::LOG_DEBUG, __METHOD__ ) ;

            // Prepare user record
            $userRecord = $messageRecord[ 'from' ] ;
            $userRecord[ 'conversationId' ] = $conversationId ;

            // Complete the message record
            if( $userRecord[ 'id' ] === $this->selfId )
            {
              $recipient = $this->getFacebookConversationRecipientRecord( $conversationId ) ;
              $messageRecord[ 'userName' ] =                         $recipient[ 'name' ] ;
              $messageRecord[ 'uuid'     ] = $conversationId . '.' . $recipient[ 'id'   ] ;
            }
            else
            {
              $messageRecord[ 'uuid' ] = $conversationId . '.' . $userRecord[ 'id' ] ;
            }
            $messageRecord[ 'timestamp' ] = FbDatetimes::dateToTs( $messageRecord[ 'date' ], FbDatetimes::DEFAULT_RFC2822_DATEFORMAT ) ;
            $this->log( "==> new  message in conversation id '" . $conversationId . "' : " . json_encode( $messageRecord ), FbLogger::LOG_DEBUG, __METHOD__ ) ;

            // Add the new message to the result array
            array_push( $res[ 'newMessages' ], $messageRecord ) ;
          }

          // Check if we have to break out of the loop
          if( $foundLastReadMessage === true ) break ;

          // Check if there is a next messages pagination page
          if( array_key_exists( 'paging', $messages ) && array_key_exists( 'next', $messages[ 'paging' ] ) )
          {
            $msgsUrlParams[ 'after' ] = $messages[ 'paging' ][ 'cursors' ][ 'after' ] ;
            if( empty( $msgsUrlParams[ 'after' ] ) ) break ;
            $this->log( "==> Another messages pagination page available : loop...", FbLogger::LOG_DEBUG, __METHOD__ ) ;
            continue ;
          }
          break ;
        }
      }
        
      // Check if we have to break out of the loop
      if( $foundLastReadConversation === true ) break ;

      // Check if there is a next conversations pagination page
      if( array_key_exists( 'paging', $conversations ) && array_key_exists( 'next', $conversations[ 'paging' ] ) )
      {
        $convsUrlParams[ 'after' ] = $conversations[ 'paging' ][ 'cursors' ][ 'after' ] ;
        if( empty( $convsUrlParams[ 'after' ] ) ) break ;
        $this->log( "==> Another conversations pagination page available : loop...", FbLogger::LOG_DEBUG, __METHOD__ ) ;
        continue ;
      }
      
      break ;
    }

    $res[ 'lastReadMessageTs' ] = $newLastReadMessageTs ;
    $this->log( "Read message(s) : " . count( $res[ 'newMessages' ] ) . " results", FbLogger::LOG_INFO, __METHOD__ ) ;

    return $res ;
  }
  
  public   function sendMessage( $to, $message, $conversationId = null )
  {
    $_conversationId   = $conversationId ;

    $_to = $to ;
    $this->log( "Sending message to user '" . $_to . "', conversationId='" . $_conversationId .  "', message='" . $message . "'...", FbLogger::LOG_INFO, __METHOD__ ) ;

    $dateTs = FbDatetimes::nowTs() ;
    $dateFs = FbDatetimes::tsToDate( $dateTs, $this->dateFormat ) ;

    $params = [
      'to'      => $to,
      'message' => $message,
    ] ;
    $messageData = $this->facebookRequest( 'sendMessage', null, $params ) ;
    if( empty( $messageData ) )
    {
      $this->log( "! Facebook request issue while trying to send message to user id=" . $to, FbLogger::LOG_WARN, __METHOD__ ) ;
      return false ;
    }
    
    $messageRecord = [
      'conversationId' => $_conversationId,
      'id'             => $messageData[ 'message_id' ],
      'date'           => $dateFs,
      'timestamp'      => $dateTs,
      'from'           => [
        'id'              => $this->selfId,
        'name'            => $this->selfName,
      ],
      'userName'       => $_to,
      'message'        => $message,
    ] ;
    $this->log( "==> message sent : " . json_encode( $messageRecord ), FbLogger::LOG_DEBUG, __METHOD__ ) ;

    return true ;
  }


  /* **************************************************************************
     Inner Tools
  */

  public function getClassName()
  {
    return $this->_parent->getClassName() ;
  }
  private  function log( $str, $level = FbLogger::LOG_DEBUG, $method = '', $indentLevel = 0 )
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
class FbLogger
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
class FbWebs
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
class FbDatetimes
{
  const DEFAULT_TIMEZONE           =       'Europe/Paris' ;
  const DEFAULT_FORMAT_DATE        =                'Ymd' ;
  const DEFAULT_FORMAT_DATETIME    =            'Ymd_His' ;
  const DEFAULT_RFC2822_DATEFORMAT = 'Y-m-d\TH:i:s\+0000' ;

  // Current timestamp
  public static function nowTs()
  {
    return time() ;
  }
  public static function nowMs()
  {
    return round( microtime( true ) * 1000 ) ;
  }

  // Date as string to timestamp
  public static function dateToTs( $date, $format = self::DEFAULT_FORMAT_DATE, $timezone = self::DEFAULT_TIMEZONE )
  {
    return DateTime::createFromFormat( $format, $date, new DateTimeZone( $timezone ) )-> getTimestamp() ;
  }

  // Timestamp to date as string
  public static function tsToDate( $ts, $format = self::DEFAULT_FORMAT_DATETIME, $timezone = self::DEFAULT_TIMEZONE )
  {
    $res = new DateTime( 'now', new DateTimeZone( $timezone ) ) ;
    $res->setTimestamp( $ts ) ;
    return $res->format( $format ) ;
  }
}


/* ****************************************************************************
   Command Line Tester
*/
class FbCommandLineTester
{
  const Verb = 'test' ;


  public    function __construct()
  {
    $connectorClass = "UserFiles\\Messaging\\Connector\\" . FBSAMPLE_CONNECTOR ;
    
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
      'purpose'  => 'Request Conversations (generic facebookRequest caller)',
      'function' => function()
      {
        $callRes = $this->connector->_msgManager->facebookRequest( 'getConversations' ) ;
        echo "RES = \n" . json_encode( $callRes, JSON_PRETTY_PRINT ) . "\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test02' ] = [
      'purpose'  => 'Request Messages (generic facebookRequest caller)',
      'function' => function()
      {
        $convId  = 't_xxxxxxxxxxxxxx' ;
        $callRes = $this->connector->_msgManager->facebookRequest( 'getMessages', $convId ) ;
        echo "RES = \n" . json_encode( $callRes, JSON_PRETTY_PRINT ) . "\n" ;
      }
    ] ;


    $this->testFunctions[ 'test03' ] = [
      'purpose'  => 'Read messages',
      'function' => function()
      {
        //$lastReadMessageTs = 'xxxxxxxxxxxxxx' ;
        $lastReadMessageTs = null ;
        $res = $this->connector->_msgManager->readMessages( $lastReadMessageTs ) ;
        echo "RES = \n" . json_encode( $res, JSON_PRETTY_PRINT ) . "\n" ;
        echo "new = " . count( $res[ 'newMessages'       ] ) . " message(s)\n" ;
        echo "LRM = " .        $res[ 'lastReadMessageTs' ]   . "\n" ;
      }
    ] ;


    $this->testFunctions[ 'test04' ] = [
      'purpose'  => 'Get Conversation User Record',
      'function' => function()
      {
        $convId   = 't_xxxxxxxxxxxxxxxxx' ;
        $callRes = $this->connector->_msgManager->getFacebookConversationRecipientRecord( $convId ) ;
        echo "RES = \n" . json_encode( $callRes, JSON_PRETTY_PRINT ) . "\n" ;
      }
    ] ;


    $this->testFunctions[ 'test05' ] = [
      'purpose'  => 'Send message',
      'function' => function()
      {
        
        $to      = 'xxxxxxxxxxxxxxxxxxxx' ;
        $message = 'Hello Facebook !' ;
        $callRes = $this->connector->_msgManager->sendMessage( $to, $message ) ;
        echo "RES = " . $callRes . "\n" ;
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
if( php_sapi_name() == 'cli' && !empty( getopt( null, [ FbCommandLineTester::Verb . ":" ] ) ) )
{
  // Usage example :
  // > php <ConnectorName>.php -f --test=00
  new FbCommandLineTester() ;
}
?>