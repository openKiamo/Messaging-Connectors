<?php

namespace UserFiles\Messaging\Connector\KConnectorMessagingTwitter ;


require_once __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;


use KiamoConnectorSampleToolsTwitter\Datetimes ;
use KiamoConnectorSampleToolsTwitter\Logger    ;
use KiamoConnectorSampleToolsTwitter\Module    ;
use KiamoConnectorSampleToolsTwitter\Resources ;
use KiamoConnectorSampleToolsTwitter\Uuids     ;
use KiamoConnectorSampleToolsTwitter\Webs      ;


class MessagingManager extends Module
{
  public    function __construct( &$_parent )
  {
    parent::__construct() ;
    $this->_parent = $_parent ;

    $this->log( "Service : " . $this->_parent->getConf( "self.service" ), Logger::LOG_INFO, __METHOD__ ) ;
    $this->log( "Version : " . $this->_parent->getConf( "self.version" ), Logger::LOG_INFO, __METHOD__ ) ;
    
    $this->initResourceFile()  ;
  }


  // Create a messaging address if it does not already exists
  public   function initResourceFile()
  {
    if( Resources::existsDefaultDataFile() ) return ;
    $pattern = [
      'mainData'       => [
        'lastReadMessageId' => '',
      ],
      'outMessageIds'  => [],
      'customers'      => [],
    ] ;
    $selfUserId =  $this->_parent->getConf( 'accessData.credentials.userId' ) ;
    if( !empty( $selfUserId ) )
    {
      $this->getUserRecord( $pattern, $selfUserId ) ;
    }
    if( $this->_parent->getConf( 'runtime.dbFile.debugMode' ) )
    {
      $pattern[ 'conversations' ] = [] ;
      $pattern[ 'inMessageIds'  ] = [] ;
    }
    Resources::writeDefaultDataFile( $pattern ) ;  // Init the data file if it does not already exists
  }
  

  public   function _buildCallContext( $verb, $urlParams = null )
  {
    $baseUrl =         $this->_parent->getConf( 'accessData.apiBaseUrl'    )
               . '/' . $this->_parent->getConf( 'accessData.apiVersion'    )
               . '/' . $this->_parent->getConf( 'accessData.verbs.' . $verb . '.route' )
               . '/' . $this->_parent->getConf( 'accessData.verbs.' . $verb . '.verb'  ) ;
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
      'method'  => $this->_parent->getConf( 'accessData.verbs.' . $verb . '.method' ),
      'type'    => $this->_parent->getConf( 'accessData.verbs.' . $verb . '.type'   ),
    ] ;
    return $res ;
  }


  public   function _buildOAuthBaseParams()
  {
    $res = [
      $this->_parent->getConf( 'accessData.oauthData.keys.consumer'        ) => $this->_parent->getConf( 'accessData.credentials.consumerKey'   ),
      $this->_parent->getConf( 'accessData.oauthData.keys.nonce'           ) => Uuids::get( true, 32 ),
      $this->_parent->getConf( 'accessData.oauthData.keys.signatureMethod' ) => $this->_parent->getConf( 'accessData.oauthData.signatureMethod' ),
      $this->_parent->getConf( 'accessData.oauthData.keys.timestamp'       ) => Datetimes::nowTs(),
      $this->_parent->getConf( 'accessData.oauthData.keys.token'           ) => $this->_parent->getConf( 'accessData.credentials.oauthToken'    ),
      $this->_parent->getConf( 'accessData.oauthData.keys.version'         ) => $this->_parent->getConf( 'accessData.oauthData.version'         ),
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
    $signKey =         rawurlencode( $this->_parent->getConf( 'accessData.credentials.consumerSecret'   ) )
               . '&' . rawurlencode( $this->_parent->getConf( 'accessData.credentials.oauthTokenSecret' ) ) ;


    // OAuth Signature
    $oauthSign = base64_encode( hash_hmac( $this->_parent->getConf( 'accessData.oauthData.hashMacMethod' ), $signBaseStr, $signKey, $raw_output = true ) ) ;

    // Add signature to oauth base params
    $oauthBaseParams[ $this->_parent->getConf( 'accessData.oauthData.keys.signature' ) ] = $oauthSign ;
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
    $callRes = Webs::restRequest( $callContext[ 'fullUrl' ], $bodyJsonStr, $header, null ) ;
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
    $this->log( $logstr, Logger::LOG_INFOP, __METHOD__ ) ;
    $requestContext = $this->buildRequestContext( $verb, $urlParams, $bodyJsonStr ) ;
    $requestResult  = $this->_requestTwitterAPI( $requestContext[ 'callContext' ], $requestContext[ 'header' ], $requestContext[ 'bodyJson' ] ) ;
    if( $requestResult[ Webs::REST_REQUEST_STATUS ] !== true || $requestResult[ Webs::REST_REQUEST_HTTPCODE ] !== 200 )
    {
      $this->log( "==> KO request : " . json_encode( $requestResult ), Logger::LOG_ERROR, __METHOD__ ) ;
      return null ;
    }
    $this->log( "==> Request OK", Logger::LOG_INFOP, __METHOD__ ) ;
    $this->log( "==> Result : " . json_encode( $requestResult[ Webs::REST_REQUEST_RESULT ] ), Logger::LOG_DEBUG, __METHOD__ ) ;
    return $requestResult[ Webs::REST_REQUEST_RESULT ] ;
  }


  private  function getUserRecord( &$messagingData, $userId )
  {
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
      $this->log( "Unable to recover data for user id=" . $userId, Logger::LOG_WARN, __METHOD__ ) ;
    }
    else
    {
      $userRecord[ 'name'   ] = $userData[ 'name'        ] ;
      $userRecord[ 'screen' ] = $userData[ 'screen_name' ] ;
      $userRecord[ 'key'    ] = $userData[ 'name'        ] . '@' . $userData[ 'screen_name' ] ;
      $messagingData[ 'customers' ][ $userRecord[ 'id' ] ] = $userRecord ;
    }
    $this->log( "==> User : " . json_encode( $userRecord ), Logger::LOG_INFO, __METHOD__ ) ;
    return $userRecord ;
  }

  public   function sendMessage( $to, $message )
  {
    $messagingData = Resources::readDefaultDataFile() ;
    $_to = $to ;
    if( array_key_exists( $to, $messagingData[ 'customers' ] ) && !empty( $messagingData[ 'customers' ][ $to ][ 'key' ] ) ) $_to = $messagingData[ 'customers' ][ $to ][ 'key' ] ;
    $this->log( "Sending message to user '" . $_to . "', message='" . $message . "'...", Logger::LOG_INFO, __METHOD__ ) ;
    $body = '{"event": {"type": "message_create", "message_create": {"target": {"recipient_id": "'
                               . $to
                               . '"}, "message_data": {"text": "' 
                               . $message
                               . '"}}}}' ;
    $messageData = $this->twitterRequest( 'messageNew', null, $body ) ;
    if( empty( $messageData ) )
    {
      $this->log( "! Twitter request issue while trying to send message to user id=" . $to, Logger::LOG_WARN, __METHOD__ ) ;
      return false ;
    }
    
    $messageRecord = [
      'id'           => $messageData[ 'event' ][ 'id'                ],
      'timestamp'    => $messageData[ 'event' ][ 'created_timestamp' ],
      'from'         => $messageData[ 'event' ][ 'message_create'    ][ 'sender_id'    ],
      'to'           => $messageData[ 'event' ][ 'message_create'    ][ 'target'       ][ 'recipient_id' ],
      'message'      => $messageData[ 'event' ][ 'message_create'    ][ 'message_data' ][ 'text'         ],
    ] ;
    $messagingData[ 'outMessageIds' ][] = $messageRecord[ 'id' ] ;
    if( $this->_parent->getConf( 'runtime.dbFile.debugMode' ) )
    {
      $messagingData[ 'conversations' ][    $messageRecord[ 'to' ] ][ 'messages' ][ $messageRecord[ 'id' ] ] = $messageRecord ;
    }
    Resources::writeDefaultDataFile( $messagingData ) ;
    $this->log( "==> message sent : " . json_encode( $messageRecord ), Logger::LOG_DEBUG, __METHOD__ ) ;

    return true ;
  }


  public   function readMessages()
  {
    $res             = [] ;
    $messagingData   = Resources::readDefaultDataFile() ;
    $dbFileDebugMode = $this->_parent->getConf( 'runtime.dbFile.debugMode' ) ;

    $this->log( "Fetching Twitter messages...", Logger::LOG_INFO, __METHOD__ ) ;
    
    $urlParams = [] ;
    $pageCount = $this->_parent->getConf( 'accessData.verbs.messageList.paginationCount' ) ;
    if( empty( $pageCount ) )
    {
      $urlParams = null ;
    }
    else
    {
      $urlParams[ 'count' ] = $pageCount ;
    }

    $lastReadMessageId    = $messagingData[ 'mainData' ][ 'lastReadMessageId' ] ;
    $newLastReadMessageId = $lastReadMessageId ;
    $foundLastReadMessage = false ;
    $newMsgNb             = 0 ;
    $newConvNb            = 0 ;
    while( true )
    {
      $twitterMessages = $this->twitterRequest( 'messageList', $urlParams ) ;
      if( empty( $twitterMessages ) ) break ;
      $this->log( "==> " . count( $twitterMessages[ 'events' ] ) . " message(s)", Logger::LOG_INFO, __METHOD__ ) ;

      // Loop on messages returned on this pagination page
      $pageMsgNb = 0 ;
      foreach( $twitterMessages[ 'events' ] as $message )
      {
        if( $message[ 'id' ] === $lastReadMessageId )
        {
          $this->log( "==> already read message, stopping reading here", Logger::LOG_DEBUG, __METHOD__ ) ;
          $foundLastReadMessage = true ;
          break ;
        }
        if( $message[ 'type' ] !== 'message_create' ) continue ;
        // Message sent by support : consider it as already read
        if( !empty( $messagingData[ 'outMessageIds' ] ) && in_array( $message[ 'id' ], $messagingData[ 'outMessageIds' ] ) )
        {
          $messageRecord = [
            'id'           => $message[ 'id'                ],
            'timestamp'    => $message[ 'created_timestamp' ],
            'from'         => $message[ 'message_create'    ][ 'sender_id'    ],
            'to'           => $message[ 'message_create'    ][ 'target'       ][ 'recipient_id' ],
            'message'      => $message[ 'message_create'    ][ 'message_data' ][ 'text'         ],
          ] ;
          $this->log( "==> sent message : " . json_encode( $messageRecord ), Logger::LOG_DEBUG, __METHOD__ ) ;
          if( $newLastReadMessageId === $lastReadMessageId ) $newLastReadMessageId = $message[ 'id' ] ;   // Pick the first one, because they are "Sorted in reverse-chronological order" (https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/list-events)
          if( $dbFileDebugMode ) $messagingData[ 'inMessageIds' ][] = $messageRecord[ 'id' ] ;
          unset( $messagingData[ 'outMessageIds' ][ array_search( $messageRecord[ 'id' ], $messagingData[ 'outMessageIds' ] ) ] ) ;
          continue ;
        }
        if( $dbFileDebugMode && in_array( $message[ 'id' ], $messagingData[ 'inMessageIds' ] ) ) continue ;
      
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
        $selfId = $this->_parent->getConf( 'accessData.credentials.userId' ) ;
        $conversationId = null ;
        if( $messageRecord[ 'from' ] === $selfId )
        {
          $conversationId = $messageRecord[ 'to'   ] ;
        }
        else
        {
          $conversationId = $messageRecord[ 'from' ] ;
        }
      
        // If it's a new conversation, get the user data and insert the conversation in the database
        if( !array_key_exists( $conversationId, $messagingData[ 'customers' ] ) )
        {
          $this->log( "==> new conversation from user id=" . $conversationId, Logger::LOG_INFO, __METHOD__ ) ;
          $newConvNb++ ;
          if( $dbFileDebugMode ) $messagingData[ 'conversations' ][ $conversationId ] = [] ;

          $userRecord = $this->getUserRecord( $messagingData, $conversationId ) ;
          if( $dbFileDebugMode )
          {
            $messagingData[ 'conversations' ][ $conversationId ][ 'user'     ] = $userRecord ;
            $messagingData[ 'conversations' ][ $conversationId ][ 'messages' ] = [] ;
          }
        }
        
        // Complete the message record
        $messageRecord[ 'date'      ] = Datetimes::getRFC2822FromTimestamp( $messageRecord[ 'timestamp' ] ) ;
        $messageRecord[ 'sender'    ] = $messageRecord[ 'from' ] ;
        $messageRecord[ 'recipient' ] = $messageRecord[ 'to'   ] ;
        if( array_key_exists( $messageRecord[ 'from' ], $messagingData[ 'customers' ] ) && !empty( $messagingData[ 'customers' ][ $messageRecord [ 'from' ] ][ 'key' ] ) ) $messageRecord[ 'sender'    ] = $messagingData[ 'customers' ][ $messageRecord [ 'from' ] ][ 'key' ] ;
        if( array_key_exists( $messageRecord[ 'to'   ], $messagingData[ 'customers' ] ) && !empty( $messagingData[ 'customers' ][ $messageRecord [ 'to'   ] ][ 'key' ] ) ) $messageRecord[ 'recipient' ] = $messagingData[ 'customers' ][ $messageRecord [ 'to'   ] ][ 'key' ] ;
        $this->log( "==> new  message : " . json_encode( $messageRecord ), Logger::LOG_DEBUG, __METHOD__ ) ;

        array_push( $res, $messageRecord ) ;
        if( $dbFileDebugMode )
        {
          $messagingData[ 'conversations' ][ $conversationId ][ "messages" ][ $messageRecord[ 'id' ] ] = $messageRecord ;
          $messagingData[ 'inMessageIds'  ][] = $messageRecord[ 'id' ] ;
        }
      }
      
      // Check if there is a next pagination page
      if( array_key_exists( 'next_cursor', $twitterMessages ) )
      {
        $urlParams[ 'cursor' ] = $twitterMessages[ 'next_cursor' ] ;
      }
      
      // Check if we have to break out of the loop
      if( $foundLastReadMessage === true || $pageMsgNb === 0 || !array_key_exists( 'next_cursor', $twitterMessages ) ) break ;

      $this->log( "==> Next page cursor : '" . $urlParams[ 'cursor' ] . "'", Logger::LOG_DEBUG, __METHOD__ ) ;
    }

    if( $newLastReadMessageId !== $lastReadMessageId )
    {
      $messagingData[ 'mainData' ][ 'lastReadMessageId' ] = $newLastReadMessageId ;
      Resources::writeDefaultDataFile( $messagingData ) ;
    }
    if( $dbFileDebugMode ) $this->log( "==> new conversations : " .  $newConvNb . ", new messages : " . $newMsgNb, Logger::LOG_INFO, __METHOD__ ) ;
    $this->log( "Read message(s) : " . count( $res ) . " results", Logger::LOG_INFO, __METHOD__ ) ;
    
    return $res ;
  }
}
?>
