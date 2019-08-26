<?php

namespace UserFiles\Messaging\Connector\KConnectorMessagingOrangeSMS ;


require_once __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;


use KiamoConnectorSampleToolsOrangeSMS\Datetimes ;
use KiamoConnectorSampleToolsOrangeSMS\Logger    ;
use KiamoConnectorSampleToolsOrangeSMS\SubModule ;
use KiamoConnectorSampleToolsOrangeSMS\Resources ;
use KiamoConnectorSampleToolsOrangeSMS\Strings   ;
use KiamoConnectorSampleToolsOrangeSMS\Uuids     ;
use KiamoConnectorSampleToolsOrangeSMS\Webs      ;


class MessagingManager extends SubModule
{
  public    function __construct( &$_parent )
  {
    parent::__construct( $_parent, get_class( $_parent ) ) ;

    $this->log( "Service : " . $this->getConf( "self.service" ), Logger::LOG_INFO, __METHOD__ ) ;
    $this->log( "Version : " . $this->getConf( "self.version" ), Logger::LOG_INFO, __METHOD__ ) ;
    
    $this->initRuntimeData()   ;
    $this->initAccessData()    ;
    $this->initResourceFiles() ;
  }

  public   function initRuntimeData()
  {
    $this->selfSender                = $this->getConf( "self.sender"                                        ) ;
    $this->smsPrefixKeyword          = $this->getConf( "self.smsPrefixKeyword"                        ) . ' ' ;
    $this->messagesLimit             = $this->getConf( "runtime.pagination.limitPerRequestMessages"         ) ;

    $this->inDateFormat              = $this->getConf( "runtime.datetimes.inFormat"                         ) ;
    $this->outDateFormat             = $this->getConf( "runtime.datetimes.outFormat"                        ) ;
    $this->timestampFormat           = Datetimes::cleanDateTimeFormat( $this->outDateFormat                          ) ;
    $this->outTimezone               = $this->getConf( "runtime.datetimes.outTimezone"                      ) ;

    $this->outEncoding               = $this->getConf( "runtime.encodings.outEncoding"                      ) ;

    $this->customerCacheEnabled      = $this->getConf( 'runtime.resources.customerCache.enabled'            ) ;
    $this->customerCacheCheck        = $this->getConf( 'runtime.resources.customerCache.checkEveryInSecs'   ) ;
    $this->customerCacheExpiration   = $this->getConf( 'runtime.resources.customerCache.expirationInSecs'   ) ;

    $this->cursorsEnabled            = $this->getConf( 'runtime.resources.cursors.enabled'                  ) ;
    $this->customersEnabled          = $this->getConf( 'runtime.resources.customers.enabled'                ) ;
    $this->conversationsEnabled      = $this->getConf( 'runtime.resources.conversations.enabled'            ) ;
  }

  private  function initAccessData()
  {
    $fieldsArr = [ 'apiBaseUrl', 'apiVersion', 'oauthLogin', 'oauthPassword' ] ;
    foreach( $fieldsArr as $field )
    {
      $this->$field = $this->getConf( "accessData." . $field ) ;
      $this->log( "Access data : " . $field . " = " . $this->$field, Logger::LOG_INFO, __METHOD__ ) ;
    }
    $this->baseUrl     = $this->apiBaseUrl . '/' . $this->apiVersion . '/' ;
    $this->accessToken = $this->orangeSMSRequest( 'getAccessToken' ) ;
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
    $mainPattern = [
      'lastReadMessageIds' => [],
    ] ;
    if( $this->customerCacheEnabled )
    {
      $mainPattern[ 'customerCache' ] = [
        'nextCheckTs'                   => Datetimes::nowMs() + $this->customerCacheCheck,
        'userRecords'                   => [],
        'expirationMap'                 => [],
      ] ;
    }
    Resources::writeDefaultDataFile( $mainPattern ) ;

    // Optional (debug purpose) : Cursors
    // ==> lastReadMessageDate (message list optimization)
    if( $this->cursorsEnabled )
    {
      $cursorsPattern = [
        'cursors' => [
          'lastReadMessageDate' => '',
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
        'outMessageIds' => [],
      ] ;
      Resources::writeDataFile( 'conversations', $conversationsPattern ) ;
    }
  }
  

  /* ----------------
     Cache management
  */
  private  function addUserToCache( $userRecord, &$messagingData = null, &$customersData = null, &$conversationsData = null )
  {
    if( !$this->customerCacheEnabled ) return ;

    $_messagingData = &$messagingData ;
    if( empty( $messagingData ) ) $_messagingData = Resources::readDefaultDataFile() ;

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
    $userRecord[ 'expirationTs' ] = Datetimes::nowMs() + $this->customerCacheExpiration * 1000 ;
    $_messagingData[ 'customerCache' ][ 'userRecords'   ][ $userRecord[ 'id'           ] ] = $userRecord ;
    $_messagingData[ 'customerCache' ][ 'expirationMap' ][ $userRecord[ 'expirationTs' ] ] = $userRecord[ 'id' ] ;
    $this->log( "==> record User " . $userRecord[ 'id' ] . ", conversationId='" . $userRecord[ 'conversationId' ] . "' in the customers cache", Logger::LOG_DEBUG, __METHOD__ ) ;

    if( empty( $messagingData ) ) Resources::writeDefaultDataFile( $_messagingData ) ;
  }

  private  function getUserFromCache( $userId, &$messagingData = null )
  {
    if(    !$this->customerCacheEnabled
        ||  empty( $messagingData )
        || !array_key_exists( $userId, $messagingData[ 'customerCache' ][ 'userRecords' ] ) )
    {
      return null ;
    }

    $userRecord = $messagingData[ 'customerCache' ][ 'userRecords' ][ $userId ] ;
    unset( $userRecord[ 'expirationTs' ] ) ;
    $this->log( "==> getting userRecord from cache : " . json_encode( $userRecord ), Logger::LOG_DEBUG, __METHOD__ ) ;
    return $userRecord ;
  }

  private  function cleanUserCache( &$messagingData = null )
  {
    if( !$this->customerCacheEnabled ) return ;

    $this->log( "Cleaning cache...", Logger::LOG_DEBUG, __METHOD__ ) ;
    $nbClean = 0 ;
    $_messagingData = &$messagingData ;
    if( empty( $messagingData ) ) $_messagingData = Resources::readDefaultDataFile() ;

    $nowMs = Datetimes::nowMs() ;
    if( $nowMs <= $_messagingData[ 'customerCache' ][ 'nextCheckTs' ] ) return ;
    $_messagingData[ 'customerCache' ][ 'nextCheckTs' ] = $nowMs + $this->customerCacheCheck * 1000 ;

    foreach( $_messagingData[ 'customerCache' ][ 'expirationMap' ] as $ms => $userId )
    {
      if( $nowMs > intval( $ms ) )
      {
        $this->log( "==> removing userId " . $userId . " from cache", Logger::LOG_TRACE, __METHOD__ ) ;
        unset( $_messagingData[ 'customerCache' ][ 'userRecords'   ][ $userId ] ) ;
        unset( $_messagingData[ 'customerCache' ][ 'expirationMap' ][ $ms     ] ) ;
        $nbClean++ ;
      }
    }

    if( empty( $messagingData ) ) Resources::writeDefaultDataFile( $_messagingData ) ;
    $this->log( "Cleaning cache done, nbClean=" . $nbClean, Logger::LOG_DEBUG, __METHOD__ ) ;
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
    if( Strings::strStartsWith( $messageRecord[ 'message' ], $this->smsPrefixKeyword, true ) ) $messageRecord[ 'message' ] = substr( $messageRecord[ 'message' ], strlen( $this->smsPrefixKeyword ) ) ;

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
      $messageRecord[ 'createdAt' ] = Datetimes::now( Datetimes::millisCleanDateTimeFormat( $this->inDateFormat ) ) ;
    }
    $messageRecord[ 'utcDate'     ] = Datetimes::universalDateConversion( $messageRecord[ 'createdAt' ], $this->inDateFormat, $this->outDateFormat, $this->outTimezone ) ;
    $messageRecord[ 'timestamp'   ] = Datetimes::dateToTs( $messageRecord[ 'utcDate' ], $this->timestampFormat ) ;

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
      $this->log( "==> Invalid verb '" . $verb . "'", Logger::LOG_ERROR, __METHOD__ ) ;
      return null ;
    }

    $logstr = "Orange SMS+ request '" . $verb . "'" ;
    if( !empty( $urlParams ) ) $logstr .= ", params=" . json_encode( $urlParams ) ;
    if( !empty( $body      ) ) $logstr .= ", body  =" . json_encode( $body      ) ;
    $this->log( $logstr, Logger::LOG_INFOP, __METHOD__ ) ;

    $requestUrl    = $this->buildUrl( $urlPostfix, $urlParams ) ;
    $this->log( "Request URL = " . $requestUrl, Logger::LOG_DEBUG, __METHOD__ ) ;
    $requestResult = Webs::restRequest( $requestUrl, $body, $header ) ;
    $this->log( "==> Result : " . json_encode( $requestResult[ Webs::REST_REQUEST_RESULT ] ), Logger::LOG_TRACE, __METHOD__ ) ;

    // Manage result depending on verb
    $res = null ;
    switch( $verb )
    {
    case 'getAccessToken' :
      if( $requestResult[ Webs::REST_REQUEST_STATUS ] !== true || $requestResult[ Webs::REST_REQUEST_HTTPCODE ] !== 200 )
      {
        $this->log( "==> KO request : " . json_encode( $requestResult ), Logger::LOG_ERROR, __METHOD__ ) ;
        return null ;
      }
      if( !array_key_exists( 'access_token', $requestResult[ Webs::REST_REQUEST_RESULT ] ) )
      {
        $this->log( "==> malformed result : " . json_encode( $requestResult ), Logger::LOG_ERROR, __METHOD__ ) ;
        return null ;
      }
      $this->accessToken = $requestResult[ Webs::REST_REQUEST_RESULT ][ 'access_token' ] ;
      $res = $this->accessToken ;
      break ;

    case 'readMessages' :
      // Request error
      if( $requestResult[ Webs::REST_REQUEST_STATUS ] !== true )
      {
        // Invalid token case : renew and retry the request one time
        if( $requestResult[ Webs::REST_REQUEST_HTTPCODE ] === 401 )
        {
          $this->log( "==> Invalid access token : renewal attempt...", Logger::LOG_DEBUG, __METHOD__ ) ;
          $tmp = $this->orangeSMSRequest( 'getAccessToken', null, true ) ;
          if( empty( $tmp ) )
          {
            $this->log( "==> Unable to renew access token : " . json_encode( $requestResult ), Logger::LOG_ERROR, __METHOD__ ) ;
            return null ;
          }
          if( $noRetry === true )
          {
            $this->log( "==> No more retries : KO request : " . json_encode( $requestResult ), Logger::LOG_ERROR, __METHOD__ ) ;
            return null ;
          }
          $requestResult = $this->orangeSMSRequest( $verb, $params, true ) ;
          if( $requestResult[ Webs::REST_REQUEST_STATUS ] !== true )
          {
            $this->log( "==> KO request : " . json_encode( $requestResult ), Logger::LOG_ERROR, __METHOD__ ) ;
            return null ;
          }
          $res = $requestResult ;
        }
        // Other kind of error
        else
        {
          $this->log( "==> KO request : " . json_encode( $requestResult ), Logger::LOG_ERROR, __METHOD__ ) ;
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
        $this->log( "==> KO request : " . json_encode( $requestResult ), Logger::LOG_ERROR, __METHOD__ ) ;
        return null ;
      }
      $res = $requestResult[ Webs::REST_REQUEST_RESULT ] ;
      break ;

    case 'sendMessage' :
      // Request error
      if( $requestResult[ Webs::REST_REQUEST_STATUS ] !== true )
      {
        // Invalid token case : renew and retry the request one time
        if( $requestResult[ Webs::REST_REQUEST_HTTPCODE ] === 401 )
        {
          $this->log( "==> Invalid access token : renewal attempt...", Logger::LOG_DEBUG, __METHOD__ ) ;
          $tmp = $this->orangeSMSRequest( 'getAccessToken', null, true ) ;
          if( empty( $tmp ) )
          {
            $this->log( "==> Unable to renew access token : " . json_encode( $requestResult ), Logger::LOG_ERROR, __METHOD__ ) ;
            $res = [
              'success' => false,
              'error'   => "Can't renew access token",
              'result'  => null,
            ] ;
            return $res ;
          }
          if( $noRetry === true )
          {
            $this->log( "==> No more retries : KO request : " . json_encode( $requestResult ), Logger::LOG_ERROR, __METHOD__ ) ;
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
            $this->log( "==> KO request : " . json_encode( $requestResult ), Logger::LOG_ERROR, __METHOD__ ) ;
            return $requestResult ;
          }
          $res = $requestResult ;
        }
        // Possibly message too long
        else if( $requestResult[ Webs::REST_REQUEST_HTTPCODE ] === 400 )
        {
          if(    !empty( $requestResult[ Webs::REST_REQUEST_RESULT ] )
              &&  array_key_exists( 'code', $requestResult[ Webs::REST_REQUEST_RESULT ][0] )
              &&  $requestResult[ Webs::REST_REQUEST_RESULT ][0][ 'code' ] === 'SmsTooLong' )
          {
            $this->log( "==> KO request : 'SMS too long' issue : " . json_encode( $requestResult ), Logger::LOG_ERROR, __METHOD__ ) ;
            $res = [
              'success' => false,
              'error'   => 'SmsTooLong',
              'result'  => null,
            ] ;
            return $res ;
          }
          else
          {
            $this->log( "==> KO request : " . json_encode( $requestResult ), Logger::LOG_ERROR, __METHOD__ ) ;
            $res = [
              'success' => false,
              'error'   => !empty( $requestResult[ Webs::REST_REQUEST_RESULT ] )
              && array_key_exists( 'code', $requestResult[ Webs::REST_REQUEST_RESULT ][0] ) ? $requestResult[ Webs::REST_REQUEST_RESULT ][0][ 'code' ] : 'Unknown error code 400',
              'result'  => null,
            ] ;
            return $res ;
          }
        }
        // Other kind of error
        else
        {
          $this->log( "==> KO request : " . json_encode( $requestResult ), Logger::LOG_ERROR, __METHOD__ ) ;
          $res = [
            'success' => false,
            'error'   => !empty( $requestResult[ Webs::REST_REQUEST_RESULT ] )
            && array_key_exists( 'code', $requestResult[ Webs::REST_REQUEST_RESULT ][0] ) ? $requestResult[ Webs::REST_REQUEST_RESULT ][0][ 'code' ] : 'Unknown error code ' . $requestResult[ Webs::REST_REQUEST_HTTPCODE ],
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
          'result'  => $requestResult[ Webs::REST_REQUEST_RESULT ],
        ] ;
      }
      break ;
    }

    $this->log( "==> Request OK", Logger::LOG_INFOP, __METHOD__ ) ;
    return $res ;
  }


  /* ----------------------
     Connector entry points
  */

  public   function readMessages( $lastReadMessageDate )
  {
    $this->setActionId() ;
    $res                   = [
      'lastReadMessageDate'  => $lastReadMessageDate,
      'newMessages'          => [],
    ] ;
    $messagingData         = Resources::readDefaultDataFile() ;
    $cursorsData           = $this->cursorsEnabled       ? Resources::readDataFile( 'cursors'       ) : null ;
    $customersData         = $this->customersEnabled     ? Resources::readDataFile( 'customers'     ) : null ;
    $conversationsData     = $this->conversationsEnabled ? Resources::readDataFile( 'conversations' ) : null ;

    if( empty( $lastReadMessageDate ) && $this->cursorsEnabled ) $lastReadMessageDate = $cursorsData[ 'cursors' ][ 'lastReadMessageDate' ] ;

    $slog = "Fetching Orange SMS+ messages" ;
    if( !empty( $lastReadMessageDate ) ) $slog .= ", lastReadMessageDate=" . $lastReadMessageDate ;
    $slog .= "..." ;
    $this->log( $slog, Logger::LOG_INFO, __METHOD__ ) ;

    $lastReadMessageTs      = null ;
    if( !empty( $lastReadMessageDate ) ) $lastReadMessageTs = Datetimes::dateToTs( $lastReadMessageDate, $this->timestampFormat ) ;
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
        $this->log( "ERROR : read messages request has failed", Logger::LOG_ERROR, __METHOD__ ) ;
        return null ;
      }
      foreach( $pageMessages[ 'content' ] as $message )
      {
        $messageRecord = $this->buildMessageRecord( $message ) ;
        $utcDate       = $messageRecord[ 'utcDate'   ] ;
        $messageTs     = $messageRecord[ 'timestamp' ] ;

        // Robustness : if the messageId is already known, skip it (should not occur if lastReadMessageDate is correctly set)
        if( $this->conversationsEnabled && in_array( $messageRecord[ 'id' ], $conversationsData[ 'inMessageIds' ] ) ) continue ;

        $this->log( "==> new message : " . json_encode( $messageRecord ), Logger::LOG_DEBUG, __METHOD__ ) ;

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
        if( $this->customerCacheEnabled ) $this->addUserToCache( $userRecord, $messagingData, $customersData, $conversationsData ) ;

        // Optional : customers database
        if( $this->customersEnabled )
        {
          // Update record if newer message
          if(    !array_key_exists( $messageRecord[ 'from' ], $customersData[ 'customers' ] )
              ||  $messageRecord[ 'timestamp' ] > $customersData[ 'customers' ][ $messageRecord[ 'from' ] ][ 'messageTs' ] )
          {
            $customersData[ 'customers' ][ $userRecord[ 'id' ] ] = $userRecord ;
          }
        }

        // Optional : customers database
        if( $this->conversationsEnabled )
        {
          // Init the conversation (key : userId)
          if( !array_key_exists( $messageRecord[ 'from' ], $conversationsData[ 'conversations' ] ) )
          {
            $conversationsData[ 'conversations' ][ $messageRecord[ 'from' ] ] = [
              'user'     => $userRecord,
              'messages' => [],
            ] ;
          }
          // Add the message to the user's conversation
          $conversationsData[ 'conversations' ][ $messageRecord[ 'from' ] ][ 'messages' ][ $messageRecord[ 'id' ] ] = $messageRecord ;
          // Add the message to the messageIds list
          $conversationsData[ 'inMessageIds' ][] = $messageRecord[ 'id' ] ;
          // Update user record if newer message
          if( $messageRecord[ 'timestamp' ] > $conversationsData[ 'conversations' ][ $messageRecord[ 'from' ] ][ 'user' ][ 'messageTs' ] )
          {
            $conversationsData[ 'conversations' ][ $messageRecord[ 'from' ] ][ 'user' ] = $userRecord ;
          }
        }
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
      Resources::writeDefaultDataFile( $messagingData ) ;

      // Save cursors       file
      if( $this->cursorsEnabled )
      {
        $cursorsData[ 'cursors' ][ 'lastReadMessageDate' ] = $newlastReadMessageDate ;
        Resources::writeDataFile( 'cursors', $cursorsData ) ;
      }

      // Save customers     file
      if( $this->customersEnabled )
      {
        Resources::writeDataFile( 'customers', $customersData ) ;
      }

      // Save conversations file
      if( $this->conversationsEnabled )
      {
        Resources::writeDataFile( 'conversations', $conversationsData ) ;
      }
    }

    $res[ 'lastReadMessageDate' ] = $newlastReadMessageDate ;
    $this->log( "Read message(s) : " . count( $res[ 'newMessages' ] ) . " results", Logger::LOG_INFO, __METHOD__ ) ;
    
    // Finally, sort message array by timestamp (useless ?)
    ksort( $res[ 'newMessages' ] ) ;

    $this->clearActionId() ;
    return $res ;
  }
  
  public   function sendMessage( $to, $message )
  {
    $this->setActionId() ;
    $messagingData     = Resources::readDefaultDataFile() ;

    $userRecord = $this->getUserFromCache( $to, $messagingData ) ;
    if( empty( $userRecord ) )
    {
      $this->log( "Unknown user '" . $to . "', or expired conversation. Unable to send message...", Logger::LOG_WARN, __METHOD__ ) ;
      return false ;
    }

    $conversationId = $userRecord[ 'conversationId' ] ;
    $this->log( "Sending message to user '" . $to . "', conversationId='" . $conversationId .  "', message='" . $message . "'...", Logger::LOG_INFO, __METHOD__ ) ;

    $params = [
      'conversationId'  => $conversationId,
      'message'         => $message,
      'encoding'        => $this->outEncoding,
    ] ;
    $requestResult = $this->orangeSMSRequest( 'sendMessage', $params ) ;
    if( $requestResult[ 'success' ] === false )
    {
      $this->log( "ERROR : send message request to user '" . $to . " has failed, error='" . $requestResult[ 'error' ] . "'", Logger::LOG_ERROR, __METHOD__ ) ;
      return false ;
    }

    $messageRecord = $this->buildMessageRecord( $requestResult[ 'result' ] ) ;

    if( $this->conversationsEnabled )
    {
      $conversationsData = Resources::readDataFile( 'conversations' ) ;
      // Add the message to the user's conversation
      $conversationsData[ 'conversations' ][ $messageRecord[ 'to' ] ][ 'messages' ][ $messageRecord[ 'id' ] ] = $messageRecord ;
      // Add the message to the messageIds list
      $conversationsData[ 'outMessageIds' ][] = $messageRecord[ 'id' ] ;
      Resources::writeDataFile( 'conversations', $conversationsData ) ;
    }
    Resources::writeDefaultDataFile( $messagingData ) ;
    $this->log( "==> message sent : " . json_encode( $messageRecord ), Logger::LOG_DEBUG, __METHOD__ ) ;

    $this->clearActionId() ;
    return true ;
  }
}
?>
