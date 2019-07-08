<?php

namespace UserFiles\Messaging\Connector\KConnectorMessagingFacebook ;


require_once __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;


use KiamoConnectorSampleToolsFacebook\Datetimes ;
use KiamoConnectorSampleToolsFacebook\Logger    ;
use KiamoConnectorSampleToolsFacebook\Module    ;
use KiamoConnectorSampleToolsFacebook\Resources ;
use KiamoConnectorSampleToolsFacebook\Uuids     ;
use KiamoConnectorSampleToolsFacebook\Webs      ;


class MessagingManager extends Module
{
  public    function __construct( &$_parent )
  {
    parent::__construct() ;
    $this->_parent = $_parent ;

    $this->log( "Service : " . $this->_parent->getConf( "self.service" ), Logger::LOG_INFO, __METHOD__ ) ;
    $this->log( "Version : " . $this->_parent->getConf( "self.version" ), Logger::LOG_INFO, __METHOD__ ) ;
    
    $this->initRuntimeData()   ;
    $this->initAccessData()    ;
    $this->initResourceFiles() ;
  }

  public   function initRuntimeData()
  {
    $this->selfName                  = $this->_parent->getConf( "accessData.pageName"                              ) ;
    $this->selfId                    = $this->_parent->getConf( "accessData.pageId"                                ) ;

    $this->conversationsLimit        = $this->_parent->getConf( "runtime.pagination.limitPerRequestConversations"  ) ;
    $this->messagesLimit             = $this->_parent->getConf( "runtime.pagination.limitPerRequestMessages"       ) ;

    $this->dateFormat                = $this->_parent->getConf( "runtime.datetimes.dateFormat"                     ) ;

    $this->customerCacheEnabled      = $this->_parent->getConf( 'runtime.resources.customerCache.enabled'          ) ;
    $this->customerCacheCheck        = $this->_parent->getConf( 'runtime.resources.customerCache.checkEveryInSecs' ) ;
    $this->customerCacheExpiration   = $this->_parent->getConf( 'runtime.resources.customerCache.expirationInSecs' ) ;

    $this->cursorsEnabled            = $this->_parent->getConf( 'runtime.resources.cursors.enabled'                ) ;
    $this->customersEnabled          = $this->_parent->getConf( 'runtime.resources.customers.enabled'              ) ;
    $this->conversationsEnabled      = $this->_parent->getConf( 'runtime.resources.conversations.enabled'          ) ;
  }

  private  function initAccessData()
  {
    $fieldsArr = [ 'apiBaseUrl', 'apiVersion', 'appName', 'appId', 'appSecret', 'pageName', 'pageId', 'accessToken' ] ;
    foreach( $fieldsArr as $field )
    {
      $this->$field = $this->_parent->getConf( "accessData." . $field ) ;
      $this->log( "Access data : " . $field . " = " . $this->$field, Logger::LOG_INFO, __METHOD__ ) ;
    }
    $this->baseUrl = $this->apiBaseUrl . '/' . $this->apiVersion . '/' ;
    $this->postUrl = 'access_token=' . $this->accessToken ;
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
      'self'          => [
        'name'           => $this->pageName,
        'id'             => $this->pageId,
        'email'          => $this->pageId . '@facebook.com',
      ],
      'outMessageIds' => [],
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
    // ==> lastReadMessageTs (message list optimization)
    if( $this->cursorsEnabled )
    {
      $cursorsPattern = [
        'cursors' => [
          'lastReadMessageTs' => '',
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
        'map'           => [],
        'inMessageIds'  => [],
      ] ;
      Resources::writeDataFile( 'conversations', $conversationsPattern ) ;
    }
  }
  

  /* ----------------
     Cache management
  */
  private  function addUserToCache( $userRecord, &$messagingData = null, $update = false )
  {
    if( !$this->customerCacheEnabled ) return ;

    $_messagingData = &$messagingData ;
    if( empty( $messagingData ) ) $_messagingData = Resources::readDefaultDataFile() ;

    // If the user is already on the cache, update the expirationTs
    if( array_key_exists( $userRecord[ 'conversationId' ], $_messagingData[ 'customerCache' ][ 'userRecords' ] ) && !$update )
    {
      $this->updateUserCacheExpiration( $userRecord[ 'conversationId' ], $userRecord[ 'id' ], $_messagingData ) ;
      Resources::writeDefaultDataFile( $_messagingData ) ;
      return ;
    }

    $userRecord[ 'expirationTs' ] = Datetimes::nowMs() + $this->customerCacheExpiration * 1000 ;
    $_messagingData[ 'customerCache' ][ 'userRecords'   ][ $userRecord[ 'conversationId' ] ] = $userRecord ;
    $_messagingData[ 'customerCache' ][ 'expirationMap' ][ $userRecord[ 'expirationTs'   ] ] = $userRecord[ 'conversationId' ] ;
    $this->log( "==> record User " . $userRecord[ 'id' ] . ", conversationId='" . $userRecord[ 'conversationId' ] . "' in the customers cache", Logger::LOG_DEBUG, __METHOD__ ) ;

    if( empty( $messagingData ) ) Resources::writeDefaultDataFile( $_messagingData ) ;
  }

  private  function updateUserCacheExpiration( $conversationId, $userId, &$messagingData = null )
  {
    if( !$this->customerCacheEnabled ) return ;

    $_messagingData = &$messagingData ;
    if( empty( $messagingData ) ) $_messagingData = Resources::readDefaultDataFile() ;

    if( !array_key_exists( $conversationId, $_messagingData[ 'customerCache' ][ 'userRecords' ] ) ) return ;

    unset( $_messagingData[ 'customerCache' ][ 'expirationMap' ][ $_messagingData[ 'customerCache' ][ 'userRecords'   ][ $conversationId ][ 'expirationTs' ] ] ) ;
    $_messagingData[ 'customerCache' ][ 'userRecords'   ][ $conversationId ][ 'expirationTs' ] = Datetimes::nowMs() + $this->customerCacheExpiration * 1000 ;
    $_messagingData[ 'customerCache' ][ 'expirationMap' ][ $_messagingData[ 'customerCache' ][ 'userRecords'   ][ $conversationId ][ 'expirationTs' ] ] = $conversationId ;
    $this->log( "==> updated expiration for user " . $userId . ", conversationId='" . $conversationId . "' in the customers cache", Logger::LOG_DEBUG, __METHOD__ ) ;

    if( empty( $messagingData ) ) Resources::writeDefaultDataFile( $_messagingData ) ;
  }

  private  function getUserFromCache( $conversationId, &$messagingData = null )
  {
    if( !$this->customerCacheEnabled || empty( $messagingData ) ) return null ;

    if( array_key_exists( $conversationId, $messagingData[ 'customerCache' ][ 'userRecords' ] ) )
    {
      $userRecord = $messagingData[ 'customerCache' ][ 'userRecords' ][ $conversationId ] ;
      unset( $userRecord[ 'expirationTs' ] ) ;
      $this->log( "==> getting userRecord from cache : " . json_encode( $userRecord ), Logger::LOG_DEBUG, __METHOD__ ) ;
      return $userRecord ;
    }
    
    return null ;
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

    foreach( $_messagingData[ 'customerCache' ][ 'expirationMap' ] as $ms => $conversationId )
    {
      if( $nowMs > intval( $ms ) )
      {
        $this->log( "==> removing conversationId " . $conversationId . " from cache", Logger::LOG_TRACE, __METHOD__ ) ;
        unset( $_messagingData[ 'customerCache' ][ 'userRecords'   ][ $conversationId ] ) ;
        unset( $_messagingData[ 'customerCache' ][ 'expirationMap' ][ $ms             ] ) ;
        $nbClean++ ;
      }
    }

    if( empty( $messagingData ) ) Resources::writeDefaultDataFile( $_messagingData ) ;
    $this->log( "Cleaning cache done, nbClean=" . $nbClean, Logger::LOG_DEBUG, __METHOD__ ) ;
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
      $this->log( "==> Invalid verb '" . $verb . "'", Logger::LOG_ERROR, __METHOD__ ) ;
      return null ;
    }

    $logstr = "Facebook request '" . $verb . "', entityId='" . $_entityId . "'" ;
    if( !empty( $urlParams ) ) $logstr .= ", urlParams=" . json_encode( $urlParams ) ;
    if( !empty( $body      ) ) $logstr .= ", body=" . json_encode( $body ) ;
    $this->log( $logstr, Logger::LOG_INFOP, __METHOD__ ) ;

    $requestUrl    = $this->buildUrl( $resource, $urlParams ) ;
    $this->log( "Request URL = " . $requestUrl, Logger::LOG_TRACE, __METHOD__ ) ;
    $requestResult = Webs::restRequest( $requestUrl, $body, $header ) ;
    if( $requestResult[ Webs::REST_REQUEST_STATUS ] !== true || $requestResult[ Webs::REST_REQUEST_HTTPCODE ] !== 200 )
    {
      $this->log( "==> KO request : " . json_encode( $requestResult ), Logger::LOG_ERROR, __METHOD__ ) ;
      return null ;
    }
    $this->log( "==> Request OK", Logger::LOG_INFOP, __METHOD__ ) ;
    $this->log( "==> Result : " . json_encode( $requestResult[ Webs::REST_REQUEST_RESULT ] ), Logger::LOG_TRACE, __METHOD__ ) ;
    return $requestResult[ Webs::REST_REQUEST_RESULT ] ;
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


  /* -------------------
     Entities management
  */
  private  function getConversationUserRecord( $conversationId, &$messagingData = null, &$conversationsData = null, $doRequest = true )
  {
    $userRecord     = null ;
    $_messagingData = $messagingData ;
    if( empty( $messagingData ) ) $_messagingData = Resources::readDefaultDataFile() ;

    // Search in the cache
    $userRecord = $this->getUserFromCache( $conversationId, $_messagingData ) ;
    if( !empty( $userRecord ) ) return $userRecord ;
    
    // Search in the conversations
    $_conversationsData = $conversationsData ;
    if( $this->conversationsEnabled && empty( $conversationsData ) ) $_conversationsData = Resources::readDataFile( 'conversations' ) ;
    if(    $this->conversationsEnabled
        && array_key_exists( $conversationId, $_conversationsData[ 'conversations' ] )
        && array_key_exists( 'user'         , $_conversationsData[ 'conversations' ][ $conversationId ] ) )
    {
      $userRecord = $_conversationsData[ 'conversations' ][ $conversationId ][ 'user' ] ;
      if( $this->customerCacheEnabled )
      {
        $this->addUserToCache( $userRecord, $_messagingData ) ;
        Resources::writeDefaultDataFile( $_messagingData ) ;
      }
      return $userRecord ;
    }
    
    // Get it from Facebook
    if( !$doRequest ) return $userRecord ;
    $userRecord = $this->getFacebookConversationRecipientRecord( $conversationId ) ;
    if( !empty( $userRecord ) && $this->customerCacheEnabled )
    {
      $this->addUserToCache( $userRecord, $_messagingData ) ;
      Resources::writeDefaultDataFile( $_messagingData ) ;
    }
    return $userRecord ;
  }
  


  /* ----------------------
     Connector entry points
  */

  public   function readMessages( $lastReadMessageTs )
  {
    $this->setActionId() ;
    $res                   = [
      'lastReadMessageTs'    => $lastReadMessageTs,
      'newMessages'          => [],
    ] ;
    $messagingData         = Resources::readDefaultDataFile() ;
    $cursorsData           = $this->cursorsEnabled       ? Resources::readDataFile( 'cursors'       ) : null ;
    $customersData         = $this->customersEnabled     ? Resources::readDataFile( 'customers'     ) : null ;
    $conversationsData     = $this->conversationsEnabled ? Resources::readDataFile( 'conversations' ) : null ;

    if( empty( $lastReadMessageTs ) && $this->cursorsEnabled ) $lastReadMessageTs = $cursorsData[ 'cursors' ][ 'lastReadMessageTs' ] ;

    $slog = "Fetching Facebook messages" ;
    if( !empty( $lastReadMessageTs ) ) $slog .= ", lastReadMessageTs=" . $lastReadMessageTs ;
    $slog .= "..." ;
    $this->log( $slog, Logger::LOG_INFO, __METHOD__ ) ;

    $newLastReadMessageTs      = $lastReadMessageTs ;
    $foundLastReadConversation = false ;
    $newMsgNb                  = 0 ;
    $newConvNb                 = 0 ;
    $convsUrlParams            = [] ;
    // Loop on conversations with pagination
    while( true )
    {
      $conversations = $this->facebookRequest( 'getConversations', null, $convsUrlParams ) ;
      if( empty( $conversations ) ) break ;
      $this->log( "==> " . count( $conversations[ 'data' ] ) . " paging conversation(s)", Logger::LOG_INFO, __METHOD__ ) ;

      // Loop on conversations returned on this pagination page
      foreach( $conversations[ 'data' ] as $conversation )
      {
        $conversationId = $conversation[ 'id' ] ;
        $conversationTs = Datetimes::dateToTs( $conversation[ 'updated_time' ], $this->dateFormat ) ;
        if( $conversationTs <= $lastReadMessageTs )
        {
          $this->log( "==> already read conversation, stop reading here", Logger::LOG_DEBUG, __METHOD__ ) ;
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
          $this->log( "===> " . count( $messages[ 'data' ] ) . " paging message(s)", Logger::LOG_INFO, __METHOD__ ) ;

          // New or updated conversation : loop on conversation messages
          foreach( $messages[ 'data' ] as $message )
          {
            $messageTs = Datetimes::dateToTs( $message[ 'created_time' ], $this->dateFormat ) ;
            if( $messageTs <= $lastReadMessageTs )
            {
              $this->log( "==> already read message, stop reading here", Logger::LOG_DEBUG, __METHOD__ ) ;
              $foundLastReadMessage = true ;
              break ;
            }

            // Message sent by support user : consider it as already read
            if( !empty( $messagingData[ 'outMessageIds' ] ) && in_array( $message[ 'id' ], $messagingData[ 'outMessageIds' ] ) )
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
              $this->log( "==> sent message in conversation id '" . $conversationId . "' : " . json_encode( $messageRecord ), Logger::LOG_DEBUG, __METHOD__ ) ;
              if( $newLastReadMessageTs === $lastReadMessageTs ) $newLastReadMessageTs = $messageTs ;   // Assuming messages are sorted in reverse-chronological order
              if( $this->conversationsEnabled ) $conversationsData[ 'inMessageIds' ][] = $messageRecord[ 'id' ] ;
              unset( $messagingData[ 'outMessageIds' ][ array_search( $messageRecord[ 'id' ], $messagingData[ 'outMessageIds' ] ) ] ) ;
              continue ;
            }

            // Double check if already read
            if( $this->conversationsEnabled && in_array( $message[ 'id' ], $conversationsData[ 'inMessageIds' ] ) ) continue ;
        

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
          
            $this->log( "==> new  message from user '" . $messageRecord[ 'from' ][ 'name' ] . "', conversationId='" . $conversationId . "' : '" . $messageRecord[ 'message' ] . "'", Logger::LOG_DEBUG, __METHOD__ ) ;

            // Add user to cache
            $userRecord = $messageRecord[ 'from' ] ;
            $userRecord[ 'conversationId' ] = $conversationId ;
            if( $userRecord[ 'id' ] !== $this->selfId ) $this->addUserToCache( $userRecord, $messagingData ) ;

            // If it's a new conversation, get the user data and insert the conversation in the database
            if( $this->conversationsEnabled )
            {
              if( !array_key_exists( $conversationId, $conversationsData[ 'conversations' ] ) )
              {
                $this->log( "==> new conversation id='" . $conversationId . "'", Logger::LOG_INFO, __METHOD__ ) ;
                $newConvNb++ ;
                $conversationsData[ 'conversations' ][ $conversationId ]               = [] ;
                $conversationsData[ 'conversations' ][ $conversationId ][ 'messages' ] = [] ;
              }
              if(    !array_key_exists( 'user', $conversationsData[ 'conversations' ][ $conversationId ] )
                  &&  $userRecord[ 'id' ] !== $this->selfId )
              {
                $this->log( "==> new conversation id='" . $conversationId . "' with customer id='" . $userRecord[ 'id' ] . "', name='" . $userRecord[ 'name' ] . "'", Logger::LOG_INFO, __METHOD__ ) ;

                $conversationsData[ 'conversations' ][ $conversationId     ][ 'user' ] = $userRecord     ;
                $conversationsData[ 'map'           ][ $userRecord[ 'id' ] ]           = $conversationId ;
                
                // Save customer
                if( $this->customersEnabled )
                {
                  $customersData[ 'customers' ][ $userRecord[ 'id' ] ] = $userRecord ;
                }
              }
            }

            // Complete the message record
            if( $userRecord[ 'id' ] === $this->selfId )
            {
              $recipient = $this->getConversationUserRecord( $conversationId, $messagingData, $conversationsData ) ;
              $messageRecord[ 'userName' ] =                         $recipient[ 'name' ] ;
              $messageRecord[ 'uuid'     ] = $conversationId . '.' . $recipient[ 'id'   ] ;
            }
            else
            {
              $messageRecord[ 'uuid' ] = $conversationId . '.' . $userRecord[ 'id' ] ;
            }
            $messageRecord[ 'timestamp' ] = Datetimes::dateToTs( $messageRecord[ 'date' ], Datetimes::DEFAULT_RFC2822_DATEFORMAT ) ;
            $this->log( "==> new  message in conversation id '" . $conversationId . "' : " . json_encode( $messageRecord ), Logger::LOG_DEBUG, __METHOD__ ) ;

            // Add the new message to the result array
            array_push( $res[ 'newMessages' ], $messageRecord ) ;
            if( $this->conversationsEnabled )
            {
              $conversationsData[ 'conversations' ][ $conversationId ][ "messages" ][ $messageRecord[ 'id' ] ] = $messageRecord ;
              $conversationsData[ 'inMessageIds'  ][] = $messageRecord[ 'id' ] ;
            }
          }

          // Check if we have to break out of the loop
          if( $foundLastReadMessage === true ) break ;

          // Check if there is a next messages pagination page
          if( array_key_exists( 'paging', $messages ) && array_key_exists( 'next', $messages[ 'paging' ] ) )
          {
            $msgsUrlParams[ 'after' ] = $messages[ 'paging' ][ 'cursors' ][ 'after' ] ;
            if( empty( $msgsUrlParams[ 'after' ] ) ) break ;
            $this->log( "==> Another messages pagination page available : loop...", Logger::LOG_DEBUG, __METHOD__ ) ;
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
        $this->log( "==> Another conversations pagination page available : loop...", Logger::LOG_DEBUG, __METHOD__ ) ;
        continue ;
      }
      
      break ;
    }

    // If new messages occured, save data files
    if( $newLastReadMessageTs !== $lastReadMessageTs )
    {
      // Clean cache before saving the main data file
      $this->cleanUserCache( $messagingData ) ;

      // Save main data file
      Resources::writeDefaultDataFile( $messagingData ) ;

      // Save cursors       file
      if( $this->cursorsEnabled       )
      {
        $cursorsData[ 'cursors' ][ 'lastReadMessageTs' ] = $newLastReadMessageTs ;
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

    $res[ 'lastReadMessageTs' ] = $newLastReadMessageTs ;
    $this->log( "Read message(s) : " . count( $res[ 'newMessages' ] ) . " results", Logger::LOG_INFO, __METHOD__ ) ;

    $this->clearActionId() ;
    return $res ;
  }
  
  public   function sendMessage( $to, $message, $conversationId = null )
  {
    $this->setActionId() ;
    $messagingData     = Resources::readDefaultDataFile() ;
    $_conversationId   = $conversationId ;
    $conversationsData = $this->conversationsEnabled ? Resources::readDataFile( 'conversations' ) : null ;

    $_to = $to ;
    // Only for debug log
    if( !empty( $_conversationId ) && ( $this->customerCacheEnabled || $this->conversationsEnabled ) )
    {
      $userRecord = $this->getConversationUserRecord( $_conversationId, $messagingData, $conversationsData, false ) ;
      if( !empty( $userRecord ) )
      {
        $_to             = $userRecord[ 'name'           ] ;
        $_conversationId = $userRecord[ 'conversationId' ] ;
      }
    }
    $this->log( "Sending message to user '" . $_to . "', conversationId='" . $_conversationId .  "', message='" . $message . "'...", Logger::LOG_INFO, __METHOD__ ) ;

    $dateTs = Datetimes::nowTs() ;
    $dateFs = Datetimes::tsToDate( $dateTs, $this->dateFormat ) ;

    $params = [
      'to'      => $to,
      'message' => $message,
    ] ;
    $messageData = $this->facebookRequest( 'sendMessage', null, $params ) ;
    if( empty( $messageData ) )
    {
      $this->log( "! Facebook request issue while trying to send message to user id=" . $to, Logger::LOG_WARN, __METHOD__ ) ;
      $this->clearActionId() ;
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
    $messagingData[ 'outMessageIds' ][] = $messageRecord[ 'id' ] ;
    if( $this->conversationsEnabled && !empty( $_conversationId ) )
    {
      $messageRecord[ 'uuid' ] = $_conversationId . '.' . $to ; 
      $conversationsData[ 'conversations' ][ $_conversationId ][ 'messages' ][ $messageRecord[ 'id' ] ] = $messageRecord ;
      Resources::writeDataFile( 'conversations', $conversationsData ) ;
    }
    if( !empty( $_conversationId ) ) $this->updateUserCacheExpiration( $_conversationId, $to, $messagingData ) ;
    Resources::writeDefaultDataFile( $messagingData ) ;
    $this->log( "==> message sent : " . json_encode( $messageRecord ), Logger::LOG_DEBUG, __METHOD__ ) ;

    $this->clearActionId() ;
    return true ;
  }
}
?>
