<?php

namespace UserFiles\Messaging\Connector\KConnectorMessagingFBMessenger ;


require_once __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;


use KiamoConnectorSampleToolsFB\Datetimes ;
use KiamoConnectorSampleToolsFB\Logger    ;
use KiamoConnectorSampleToolsFB\Module    ;
use KiamoConnectorSampleToolsFB\Resources ;
use KiamoConnectorSampleToolsFB\Uuids     ;
use KiamoConnectorSampleToolsFB\Webs      ;


class MessagingManager extends Module
{
  public    function __construct( &$_parent )
  {
    parent::__construct() ;
    $this->_parent = $_parent ;

    $this->log( "Service : " . $this->_parent->getConf( "self.service" ), Logger::LOG_INFO, __METHOD__ ) ;
    $this->log( "Version : " . $this->_parent->getConf( "self.version" ), Logger::LOG_INFO, __METHOD__ ) ;
    
    $this->initAccessData() ;
    $this->initResourceFile() ;
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


  // Create a messaging address if it does not already exists
  public   function initResourceFile()
  {
    if( !Resources::existsDefaultDataFile() ) Resources::writeDefaultDataFile( [] ) ;  // Init the data file if it does not already exists
    $messagingData = Resources::readDefaultDataFile() ;
    if( array_key_exists( $this->pageName, $messagingData ) ) return ;
    $messagingData[ $this->pageName ] = [] ;
    $messagingData[ $this->pageName ][ "conversations" ] = [] ;
    $messagingData[ $this->pageName ][ "customers"     ] = [] ;
    Resources::writeDefaultDataFile( $messagingData ) ;
  }
  

  public   function buildUrl( $resource = null, $params = null )
  {
    $res = $this->baseUrl ;
    if( !empty( $resource ) )
    {
      $res .= $resource ;
    }
    if( !empty( $params ) )
    {
      $res .= '?' . $params . '&' ;
    }
    else
    {
      $res .= '?' ;
    }
    $res .= $this->postUrl ;
    return $res ;
  }

  // Create a message object
  public   function createMessage( $msg, $from = null, $to = null, $id = null, $date = null, $senderName = '', $read = false )
  {
    $msgItem = [] ;
    if( empty( $id   ) )
    {
      $msgItem[ "id"      ] = Uuids::get() ;
    }
    else
    {
      $msgItem[ "id"      ] = $id ;
    }
    if( empty( $date ) )
    {
      $msgItem[ "date"    ] = Datetimes::getRFC2822Now( 'timezoned' ) ;
    }
    else
    {
      $msgItem[ "date"    ] = \DateTime::createFromFormat( $this->_parent->getConf( "data.dateFormat" ), $date )->format( \DateTime::RFC2822 );
    }
    $fromToArr = [ "from", "to" ] ;
    foreach( $fromToArr as $cur )
    {
      $msgItem[ $cur ] = $$cur ;
      if( empty( $$cur ) )
      {
        if( $senderName != $this->pageName )
        {
          $msgItem[ $cur ] = $this->pageName ;
        }
        else
        {
          $msgItem[ $cur ] = "Customer" ;
        }
      }
    }
    $msgItem[ "sender"    ] = $senderName ;
    $msgItem[ "text"      ] = $msg ;
    $msgItem[ "read"      ] = $read ;
    $this->log( "Created  message    : " . $this->messageDesc( $msgItem ), Logger::LOG_DEBUG, __METHOD__ ) ;
    return $msgItem ;
  }

  public   function messageDesc( &$msgItem, $pretty = false )
  {
    if( $pretty ) return json_encode( $msgItem, JSON_PRETTY_PRINT ) ;
    return json_encode( $msgItem ) ;
  }


  public   function sendFacebookMessage( $to, $msg, $messageType = null )
  {
    $this->log( "Sending Message to Facebook : to='" . $to . "'; msg='" . $msg . "'", Logger::LOG_DEBUG, __METHOD__ ) ;
    $fbMsg = [] ;
    $fbMsg[ "messaging_type" ] = $messageType ;
    if( empty( $messageType ) ) $fbMsg[ "messaging_type" ] = "RESPONSE" ;
    $fbMsg[ "recipient" ] = [] ;
    $fbMsg[ "recipient" ][ "id"   ] = $to ;
    $fbMsg[ "message"   ] = [] ;
    $fbMsg[ "message"   ][ "text" ] = $msg ;
    $sendUrl = $this->buildUrl( $this->pageId . '/messages', null ) ;
    $sendRes = Webs::restRequest( $sendUrl, $fbMsg, "Content-Type: application/json" ) ;
    $this->log( "Sending Message to Facebook : Result : " . json_encode( $sendRes ), Logger::LOG_DEBUG, __METHOD__ ) ;
  }

  // The owner of the Facebook page sends a message to a customer
  public   function sendMessage( &$msgItem )
  {
    $from = $msgItem[ "from" ] ;
    $to   = $msgItem[ "to"   ] ;
    $messagingData = Resources::readDefaultDataFile() ;
    
    if( !array_key_exists( $msgItem[ "to" ], $messagingData[ $this->pageName ][ "customers" ] ) ) return false ;
    
    $convId = $messagingData[ $this->pageName ][ "customers" ][ $msgItem[ "to" ] ] ;
    array_push( $messagingData[ $this->pageName ][ "conversations" ][ $convId ][ "unsorted" ], $msgItem ) ;
    
    $this->log( "Sending  message    : " . $this->messageDesc( $msgItem ), Logger::LOG_INFO, __METHOD__ ) ;

    $this->sendFacebookMessage( $msgItem[ "to" ], $msgItem[ "text" ] ) ;

    Resources::writeDefaultDataFile( $messagingData ) ;

    return true ;
  }


  public   function fetchFacebookMessages()
  {
    $messagingData = Resources::readDefaultDataFile() ;

    $convsUrl = $this->buildUrl( $this->pageId . '/conversations', null ) ;
    $convsRes = Webs::restRequest( $convsUrl ) ;

    $msgs   = [] ;
    foreach( $convsRes[ Webs::REST_REQUEST_RESULT ][ 'data' ] as $convItem )
    {
      if( !array_key_exists( $convItem[ "id" ], $messagingData[ $this->pageName ][ "conversations" ] ) )
      {
        $messagingData[ $this->pageName ][ "conversations" ][ $convItem[ "id" ] ]                = [] ;
        $messagingData[ $this->pageName ][ "conversations" ][ $convItem[ "id" ] ][ "recipient" ] = [] ;
        $messagingData[ $this->pageName ][ "conversations" ][ $convItem[ "id" ] ][ "msgIds"    ] = [] ;
        $messagingData[ $this->pageName ][ "conversations" ][ $convItem[ "id" ] ][ "messages"  ] = [] ;
        $messagingData[ $this->pageName ][ "conversations" ][ $convItem[ "id" ] ][ "unsorted"  ] = [] ;
      }
      $msgUrl = $this->buildUrl( $convItem[ "id" ] . '/messages', 'fields=id' ) ;
      $msgRes = Webs::restRequest( $msgUrl ) ;
      foreach( $msgRes[ Webs::REST_REQUEST_RESULT ][ 'data' ] as $msgItem )
      {
        $msgItemId  = $msgItem[ "id" ] ;
        
        if( in_array( $msgItemId, $messagingData[ $this->pageName ][ "conversations" ][ $convItem[ "id" ] ][ "msgIds" ] ) ) continue ;
        
        $msgItemUrl = $this->buildUrl( $msgItemId, 'fields=id,from,message,created_time' ) ;
        $msgItemRes = Webs::restRequest( $msgItemUrl ) ;
        array_push( $msgs, $msgItemRes[ Webs::REST_REQUEST_RESULT ] ) ;

        /*
        [true,"CURLE_OK",200,{"id":"m_xfnsmijUygLWwdEdbUP_ET49zaQB_98CxfYA08gANnX7nLDuS5MjYAtuGDA8RQJ2Q43MYU7Pzf3EQ73lV-uhng","from":{"name":"Julien Doremus","email":"1955697271212191@facebook.com","id":"1955697271212191"},"message":"Salut kiamo","created_time":"2019-01-10T11:18:46+0000"}]
        */
        $msgItemDat = $this->createMessage( $msgItemRes[ Webs::REST_REQUEST_RESULT ][ "message" ],
                                            $msgItemRes[ Webs::REST_REQUEST_RESULT ][ "from" ][ "id"   ],
                                            null,
                                            $msgItemRes[ Webs::REST_REQUEST_RESULT ][ "id" ],
                                            $msgItemRes[ Webs::REST_REQUEST_RESULT ][ "created_time" ],
                                            $msgItemRes[ Webs::REST_REQUEST_RESULT ][ "from" ][ "name" ],
                                            false ) ;
        array_push( $messagingData[ $this->pageName ][ "conversations" ][ $convItem[ "id" ] ][ "msgIds"   ], $msgItemId  ) ;
        array_push( $messagingData[ $this->pageName ][ "conversations" ][ $convItem[ "id" ] ][ "messages" ], $msgItemDat ) ;
        
        // Init the recipient data if required, and the customer <==> conversation link at the same time
        if( empty( $messagingData[ $this->pageName ][ "conversations" ][ $convItem[ "id" ] ][ "recipient" ] ) && $msgItemRes[ Webs::REST_REQUEST_RESULT ][ "from" ][ "id" ] != $this->pageId )
        {
          $messagingData[ $this->pageName ][ "conversations" ][ $convItem[ "id" ] ][ "recipient" ][ "id"   ] = $msgItemRes[ Webs::REST_REQUEST_RESULT ][ "from" ][ "id"   ] ;
          $messagingData[ $this->pageName ][ "conversations" ][ $convItem[ "id" ] ][ "recipient" ][ "name" ] = $msgItemRes[ Webs::REST_REQUEST_RESULT ][ "from" ][ "name" ] ;
          $messagingData[ $this->pageName ][ "customers" ][ $msgItemRes[ Webs::REST_REQUEST_RESULT ][ "from" ][ "id" ] ] = $convItem[ "id" ] ;
        }
      }
    }
    
    Resources::writeDefaultDataFile( $messagingData ) ;
    return $messagingData ;
  }
  
  public   function readMessages( $from = null )   // from in [null|"<specific customer>"]
  {
    $messagingData   = $this->fetchFacebookMessages() ;
    
    $res     = [] ;
    $loopArr = [] ;
    if( empty( $from ) )
    {
      $loopArr = array_keys( $messagingData[ $this->pageName ][ "customers" ] ) ;
    }
    else
    {
      array_push( $loopArr, $from ) ;
    }
    
    foreach( $loopArr as $customerId )
    {
      if( !array_key_exists( $customerId, $messagingData[ $this->pageName ][ "customers" ] ) )
      {
        $this->log( "No such customer id '" . $customerId . "'", Logger::LOG_WARN, __METHOD__ ) ;
        continue ;
      }
      $conversationId =  $messagingData[ $this->pageName ][ "customers"     ][ $customerId     ] ;
      $conversation   = &$messagingData[ $this->pageName ][ "conversations" ][ $conversationId ] ;
      $customerName   =  $conversation[ "recipient" ][ "name" ] ;
      $this->log( "conversationId : " . $conversationId . ", customerId : " . $customerId . " => " . $customerName, Logger::LOG_DEBUG, __METHOD__ ) ;

      for( $j = 0 ; $j < count( $conversation[ "messages" ] ) ; $j++ )
      {
        $message = $conversation[ "messages" ][ $j ] ;

        if( $message[ "read" ] === true ) continue ;  // Skip already read messages

        // Message sent by the page admin
        if( $message[ "from" ] === $this->pageId )
        {
          // Don't push it if it has been sent from Kiamo
          $skipUnsorted = false ;
          for( $i = 0 ; $i < count( $conversation[ "unsorted" ] ) ; $i++ )
          {
            $dateMarginInSec = $this->_parent->getConf( "data.sendReceiveAvgMarginInSec" ) ;
            $unsorted = $conversation[ "unsorted" ][ $i ] ;
            // Searching same text and same date
            if( $unsorted[ "text" ] == $message[ "text" ] )
            {
              $deltaTime = Datetimes::deltaDatesInSecs( $unsorted[ "date" ], $message[ "date" ], \DateTime::RFC2822 ) ;
              if( abs( $deltaTime ) <= $dateMarginInSec )
              {
                $skipUnsorted = true ;
                array_splice( $conversation[ "unsorted" ], $i, 1 ) ;
                break ;
              }
            }
          }
          $conversation[ "messages" ][ $j ][ "read" ] = true ;
          if( $skipUnsorted === false )
          {
            array_push( $res, $message ) ;
          }
        }
        // Message sent by the customer
        else
        {
          $conversation[ "messages" ][ $j ][ "read" ] = true ;
          array_push( $res, $message ) ;
        }
      }
    }
    
    $this->log( "Reading  message(s) : " . count( $res ) . " results", Logger::LOG_INFO, __METHOD__ ) ;
    Resources::writeDefaultDataFile( $messagingData ) ;
    
    return array_reverse( $res ) ;
  }

  private  function cleanAddressMessages( $type, $address, $from = null )   // type in [all|read], from in [null|"<specific customer>"]
  {
    $messagingData = Resources::readDefaultDataFile() ;
    if( !array_key_exists( $address, $messagingData ) ) return $messagingData ;
    
    $deletedNb = 0 ;
    if( empty( $from ) )  // All customer messages
    {
      if( $type === "all" )
      {
        $deletedNb += count( $messagingData[ $address ] ) ;
        $messagingData[ $address ] = [] ;
      }
      else if( $type === "read" )
      {
        foreach( $messagingData[ $address ] as $user => $userMessages )
        {
          for( $i = 0 ; $i < count( $userMessages ) ; $i++ )
          {
            if( $messagingData[ $address ][ $user ][ $i ][ "read" ] === true )
            {
              $deletedNb++ ;
              unset( $messagingData[ $address ][ $user ][ $i ] ) ;
            }
          }
        }
      }
    }
    else  // one specific customer
    {
      if( !array_key_exists( $from, $messagingData[ $address ] ) ) return $messagingData ;
      
      if( $type === "all" )
      {
        $deletedNb += count( $messagingData[ $address ][ $from ] ) ;
        $messagingData[ $address ][ $from ] = [] ;
      }
      else if( $type === "read" )
      {
        for( $i = 0 ; $i < count( $messagingData[ $address ][ $from ] ) ; $i++ )
        {
          if( $messagingData[ $address ][ $from ][ $i ][ "read" ] === true )
          {
            $deletedNb++ ;
            unset( $messagingData[ $address ][ $from ][ $i ] ) ;
          }
        }
      }
    }

    $slog  = "Cleaning address message(s) : " ;
    $slog .= "type : '" . $type . "'" ;
    $slog .= ", messaging address : '" . $address . "'" ;
    if( empty( $from ) )
    {
      $slog .= ", all customer messages" ;
    }
    else
    {
      $slog .= ", customer : '" . $from . "'" ;
    }
    $slog .= " => nb deleted : " . $deletedNb ;
    $this->log( $slog, Logger::LOG_DEBUG, __METHOD__ ) ;

    return $messagingData ;
  }
  
  public   function cleanMessages( $type, $address = null, $from = null )   // type in [all|read], address in [null|"<specific address>"], from in [null|"<specific customer>"]
  {
    $messagingData = Resources::readDefaultDataFile() ;

    if( empty( $address ) )  // All messaging addresses
    {
      foreach( $messagingData as $messagingAddress => $content )
      {
        $messagingTmp = $this->cleanAddressMessages( $type, $messagingAddress, $from ) ;
        $messagingData[ $messagingAddress ] = $messagingTmp[ $messagingAddress ] ;
      }
    }
    else if( array_key_exists( $address, $messagingData ) )  // One specific messaging address
    {
      $messagingData = $this->cleanAddressMessages( $type, $address, $from ) ;
    }
    
    $slog  = "Cleaning message(s) : " ;
    $slog .= "type : '" . $type . "'" ;
    if( empty( $address ) )
    {
      $slog .= ", all messaging addresses" ;
    }
    else
    {
      $slog .= ", messaging address : '" . $address . "'" ;
    }
    if( empty( $from ) )
    {
      $slog .= ", all customer messages" ;
    }
    else
    {
      $slog .= ", customer : '" . $from . "'" ;
    }
    $this->log( $slog, Logger::LOG_INFO, __METHOD__ ) ;
    
    Resources::writeDefaultDataFile( $messagingData ) ;
  }
}
?>
