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
    
    $this->initRuntimeData()   ;
    $this->initResourceFiles() ;
  }

  public   function initRuntimeData()
  {
    $this->selfId                = $this->_parent->getConf( 'accessData.credentials.userId'             ) ;
    $this->customersCacheEnabled = $this->_parent->getConf( 'runtime.resources.customers.cache.enabled' ) ;
    $this->cursorsEnabled        = $this->_parent->getConf( 'runtime.resources.cursors.enabled'         ) ;
    $this->customersEnabled      = $this->_parent->getConf( 'runtime.resources.customers.enabled'       ) ;
    $this->conversationsEnabled  = $this->_parent->getConf( 'runtime.resources.conversations.enabled'   ) ;
  }

  public   function initResourceFiles()
  {
    if( Resources::existsDefaultDataFile() ) return ;

    Resources::srm( 'cursors'       ) ;
    Resources::srm( 'customers'     ) ;
    Resources::srm( 'conversations' ) ;


    // Main Data
    // => self
    // => last messages sent (ids)
    // => (optional) customers data cache
    $mainPattern = [
      'outMessageIds' => [],
    ] ;
    // Optional (debug purpose) : Customers cache
    // ==> map customerId <==> customerName for the cache duration (requests optimization)
    if( $this->customersCacheEnabled )
    {
      $mainPattern[ 'customersCache' ] = [
        'nextCheckTs'                    => Datetimes::nowTs() + $this->_parent->getConf( 'runtime.resources.customers.cache.checkEveryInSecs' ),
        'userRecords'                    => [],
        'expirationMap'                  => [],
      ] ;
    }
    if( !empty( $this->selfId ) )
    {
      $selfUserRecord = $this->getUserRecord( $this->selfId ) ;
      $mainPattern[ 'self' ] = $selfUserRecord ;
    }
    Resources::writeDefaultDataFile( $mainPattern ) ;

    // Optional (debug purpose) : Cursors
    // ==> lastReadMessageId (message list optimization)
    if( $this->cursorsEnabled )
    {
      $cursorsPattern = [
        'cursors' => [
          'lastReadMessageId' => '',
        ],
      ] ;
      Resources::writeDataFile( 'cursors', $cursorsPattern ) ;
    }

    // Optional (debug purpose) : Customers
    // ==> customers data (id, name, pageName, ...) for each received message
    if( $this->customersEnabled )
    {
      $customersPattern = [
        'customers' => [],
      ] ;
      Resources::writeDataFile( 'customers', $customersPattern ) ;
    }

    // Optional (debug purpose) : Conversations
    // ==> conversation list
    if( $this->conversationsEnabled )
    {
      $conversationsPattern = [
        'conversations' => [],
        'inMessageIds'  => [],
      ] ;
      Resources::writeDataFile( 'conversations', $conversationsPattern ) ;
    }
  }
  

  /* --------------------
     Twitter call methods
  */

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


  /* --------------------------
     Customers cache management
  */

  private  function addUserToCache( $userRecord, &$messagingData = null )
  {
    if( !$this->customersCacheEnabled ) return ;

    $_messagingData = &$messagingData ;
    if( empty( $messagingData ) ) $_messagingData = Resources::readDefaultDataFile() ;

    $userRecord[ 'expirationTs' ] = Datetimes::nowTs() + $this->_parent->getConf( 'runtime.resources.customers.cache.expirationInSecs' ) ;
    $_messagingData[ 'customersCache' ][ 'userRecords' ][ $userRecord[ 'id' ] ] = $userRecord ;
    $_messagingData[ 'customersCache' ][ 'expirationMap' ][ $userRecord[ 'expirationTs' ] ] = $userRecord[ 'id' ] ;
    $this->log( "==> record User " . $userRecord[ 'id' ] . " in the customers cache", Logger::LOG_DEBUG, __METHOD__ ) ;

    if( empty( $messagingData ) ) Resources::writeDefaultDataFile( $_messagingData ) ;
  }

  private  function getUserFromCache( $userId, &$messagingData = null )
  {
    if( !$this->customersCacheEnabled || empty( $messagingData ) ) return null ;

    if( array_key_exists( $userId, $messagingData[ 'customersCache' ][ 'userRecords' ] ) )
    {
      $userRecord = $messagingData[ 'customersCache' ][ 'userRecords' ][ $userId ] ;
      unset( $userRecord[ 'expirationTs' ] ) ;
      $this->log( "==> getting userRecord from cache : " . json_encode( $userRecord ), Logger::LOG_TRACE, __METHOD__ ) ;
      return $userRecord ;
    }
    
    return null ;
  }

  private  function cleanUserCache( &$messagingData = null )
  {
    if( !$this->customersCacheEnabled ) return ;

    $_messagingData = &$messagingData ;
    if( empty( $messagingData ) ) $_messagingData = Resources::readDefaultDataFile() ;

    $nowTs = Datetimes::nowTs() ;
    if( $nowTs <= $_messagingData[ 'customersCache' ][ 'nextCheckTs' ] ) return ;
    $_messagingData[ 'customersCache' ][ 'nextCheckTs' ] = $nowTs + $this->_parent->getConf( 'runtime.resources.customers.cache.checkEveryInSecs' ) ;

    foreach( $_messagingData[ 'customersCache' ][ 'expirationMap' ] as $ts => $userId )
    {
      if( $nowTs > intval( $ts ) )
      {
        $this->log( "==> removing userId " . $userId . " from cache", Logger::LOG_TRACE, __METHOD__ ) ;
        unset( $_messagingData[ 'customersCache' ][ 'userRecords'   ][ $userId ] ) ;
        unset( $_messagingData[ 'customersCache' ][ 'expirationMap' ][ $ts     ] ) ;
      }
    }

    if( empty( $messagingData ) ) Resources::writeDefaultDataFile( $_messagingData ) ;
  }


  /* -------------------
     Entities management
  */

  private  function getUserRecord( $userId, &$messagingData = null, &$customersData = null )
  {
    // If messagingData and customersData are empty, we simply are in initialization phase, no customer is recorded yet

    // First, check if the user is self
    if(    !empty( $messagingData )
        &&  $userId === $this->selfId )
    {
      return $messagingData[ 'self' ] ;
    }

    // Second, check if the user is already in the customers cache
    $userRecord = $this->getUserFromCache( $userId, $messagingData ) ;
    if( !empty( $userRecord ) ) return $userRecord ;

    // Third, check if the user is already in the customer list
    if(    !empty( $customersData )
        &&  array_key_exists( $userId, $customersData[ 'customers' ] ) )
    {
      return $customersData[ 'customers' ][ $userId ] ;
    }
      
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
      
      // Add the user record to the customers cache
      if(    !empty( $messagingData )
          &&  $this->customersCacheEnabled )
      {
        $this->addUserToCache( $userRecord, $messagingData ) ;
      }

      // Add the user record to the customer list
      if( !empty( $customersData ) )
      {
        $customersData[ 'customers' ][ $userId ] = $userRecord ;
        $this->log( "==> record User " . $userId . " in the customer list", Logger::LOG_DEBUG, __METHOD__ ) ;
      }
    }
    $this->log( "==> User : " . json_encode( $userRecord ), Logger::LOG_INFO, __METHOD__ ) ;
    return $userRecord ;
  }


  /* ----------------------
     Connector entry points
  */

  public   function readMessages( $lastReadMessageId )
  {
    $this->setActionId() ;
    $res                   = [
      'lastReadMessageId'    => $lastReadMessageId,
      'newMessages'          => [],
    ] ;
    $messagingData         = Resources::readDefaultDataFile() ;
    $cursorsData           = $this->cursorsEnabled       ? Resources::readDataFile( 'cursors'       ) : null ;
    $customersData         = $this->customersEnabled     ? Resources::readDataFile( 'customers'     ) : null ;
    $conversationsData     = $this->conversationsEnabled ? Resources::readDataFile( 'conversations' ) : null ;

    $slog = "Fetching Twitter messages" ;
    if( !empty( $lastReadMessageId ) ) $slog .= ", lastReadMessageId=" . $lastReadMessageId ;
    $slog .= "..." ;
    $this->log( $slog, Logger::LOG_INFO, __METHOD__ ) ;
    
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

    if( empty( $lastReadMessageId ) && $this->cursorsEnabled ) $lastReadMessageId = $cursorsData[ 'cursors' ][ 'lastReadMessageId' ] ;
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
          $this->log( "==> already read message, stop reading here", Logger::LOG_DEBUG, __METHOD__ ) ;
          $foundLastReadMessage = true ;
          break ;
        }
        if( $message[ 'type' ] !== 'message_create' ) continue ;

        // Message sent by support user : consider it as already read
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
          if( $this->conversationsEnabled ) $conversationsData[ 'inMessageIds' ][] = $messageRecord[ 'id' ] ;
          unset( $messagingData[ 'outMessageIds' ][ array_search( $messageRecord[ 'id' ], $messagingData[ 'outMessageIds' ] ) ] ) ;
          continue ;
        }

        // Double check if already read
        if( $this->conversationsEnabled && in_array( $message[ 'id' ], $conversationsData[ 'inMessageIds' ] ) ) continue ;
      
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
        $userFrom = $this->getUserRecord( $conversationId       , $messagingData, $customersData ) ;
        $userTo   = $this->getUserRecord( $messageRecord[ 'to' ], $messagingData, $customersData ) ;
        $this->log( "==> new  message from '" . $userFrom[ 'key' ] . "' to '" . $userTo[ 'key' ] . "'", Logger::LOG_DEBUG, __METHOD__ ) ;

        // If it's a new conversation, get the user data and insert the conversation in the database
        if( $this->conversationsEnabled && !array_key_exists( $conversationId, $conversationsData[ 'conversations' ] ) )
        {
          $this->log( "==> new conversation from user id=" . $conversationId, Logger::LOG_INFO, __METHOD__ ) ;
          $newConvNb++ ;
          $conversationsData[ 'conversations' ][ $conversationId ] = [] ;
          $conversationsData[ 'conversations' ][ $conversationId ][ 'user'     ] = $userFrom ;
          $conversationsData[ 'conversations' ][ $conversationId ][ 'messages' ] = [] ;
        }
        
        // Complete the message record
        $messageRecord[ 'date'      ] = Datetimes::getRFC2822FromTimestamp( $messageRecord[ 'timestamp' ] ) ;
        $messageRecord[ 'sender'    ] = $messageRecord[ 'from' ] ;
        $messageRecord[ 'recipient' ] = $messageRecord[ 'to'   ] ;
        $messageRecord[ 'sender'    ] = $userFrom[      'key'  ] ;
        $messageRecord[ 'recipient' ] = $userTo[        'key'  ] ;
        $this->log( "==> new  message : " . json_encode( $messageRecord ), Logger::LOG_DEBUG, __METHOD__ ) ;

        array_push( $res[ 'newMessages' ], $messageRecord ) ;
        if( $this->conversationsEnabled )
        {
          $conversationsData[ 'conversations' ][ $conversationId ][ "messages" ][ $messageRecord[ 'id' ] ] = $messageRecord ;
          $conversationsData[ 'inMessageIds'  ][] = $messageRecord[ 'id' ] ;
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

    // If new messages occured, save data files
    if( $newLastReadMessageId !== $lastReadMessageId )
    {
      // Clean cache before saving the main data file
      $this->cleanUserCache( $messagingData ) ;

      // Save main data file
      Resources::writeDefaultDataFile( $messagingData ) ;

      // Save cursors       file
      if( $this->cursorsEnabled       )
      {
        $cursorsData[ 'cursors' ][ 'lastReadMessageId' ] = $newLastReadMessageId ;
        Resources::writeDataFile( 'cursors'      , $cursorsData       ) ;
      }

      // Save customers     file
      if( $this->customersEnabled     )
      {
        Resources::writeDataFile( 'customers'    , $customersData     ) ;
      }

      // Save conversations file
      if( $this->conversationsEnabled )
      {
        Resources::writeDataFile( 'conversations', $conversationsData ) ;
        $this->log( "==> new conversations : " .  $newConvNb . ", new messages : " . $newMsgNb, Logger::LOG_INFO, __METHOD__ ) ;
      }
    }

    $res[ 'lastReadMessageId' ] = $newLastReadMessageId ;
    $this->log( "Read message(s) : " . count( $res[ 'newMessages' ] ) . " results", Logger::LOG_INFO, __METHOD__ ) ;

    $this->clearActionId() ;
    return $res ;
  }
  
  public   function sendMessage( $to, $message )
  {
    $this->setActionId() ;
    $messagingData = Resources::readDefaultDataFile() ;

    $_to = $to ;
    // Only for debug log
    if( $this->customersCacheEnabled || $this->customersEnabled )
    {
      $customersData = null ;
      if( $this->customersEnabled ) $customersData = Resources::readDataFile( 'customers' ) ;
      $userRecord = $this->getUserRecord( $to, $messagingData, $customersData ) ;
      $_to = $userRecord[ 'key' ] ;
    }
    $this->log( "Sending message to user '" . $_to . "', message='" . $message . "'...", Logger::LOG_INFO, __METHOD__ ) ;
    $body =   '{"event": {"type": "message_create", "message_create": {"target": {"recipient_id": "'
            . $to
            . '"}, "message_data": {"text": "' 
            . $message
            . '"}}}}' ;
    $messageData = $this->twitterRequest( 'messageNew', null, $body ) ;
    if( empty( $messageData ) )
    {
      $this->log( "! Twitter request issue while trying to send message to user id=" . $to, Logger::LOG_WARN, __METHOD__ ) ;
      $this->clearActionId() ;
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
    if( $this->conversationsEnabled )
    {
      $conversationsData = Resources::readDataFile( 'conversations' ) ;
      $conversationsData[ 'conversations' ][ $messageRecord[ 'to' ] ][ 'messages' ][ $messageRecord[ 'id' ] ] = $messageRecord ;
    }
    Resources::writeDefaultDataFile( $messagingData ) ;
    if( $this->customersEnabled     ) Resources::writeDataFile( 'customers'    , $customersData     ) ;
    if( $this->conversationsEnabled ) Resources::writeDataFile( 'conversations', $conversationsData ) ;
    $this->log( "==> message sent : " . json_encode( $messageRecord ), Logger::LOG_DEBUG, __METHOD__ ) ;

    $this->clearActionId() ;
    return true ;
  }
}
?>
