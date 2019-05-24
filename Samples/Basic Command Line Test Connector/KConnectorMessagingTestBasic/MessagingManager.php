<?php

namespace UserFiles\Messaging\Connector\KConnectorMessagingTestBasic ;


require_once __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;


use KiamoConnectorSampleTools\Datetimes ;
use KiamoConnectorSampleTools\Logger    ;
use KiamoConnectorSampleTools\Module    ;
use KiamoConnectorSampleTools\Resources ;
use KiamoConnectorSampleTools\Uuids     ;


class MessagingManager extends Module
{
  public    function __construct( $messagingAddress = null )
  {
    parent::__construct() ;
    if( !Resources::existsDefaultDataFile() ) Resources::writeDefaultDataFile( [] ) ;  // Init the data file if it does not already exists
    if( !empty( $messagingAddress ) ) $this->initAddress( $messagingAddress ) ;
  }


  // Create a message object
  public   function createMessage( $msg, $from, $to, $senderName = '', $read = false )
  {
    $msgItem = [] ;
    $msgItem[ "id"     ] = Uuids::get() ;
    $msgItem[ "date"   ] = gmdate( \DateTime::RFC2822 ) ;
    $msgItem[ "from"   ] = $from ;
    $msgItem[ "to"     ] = $to ;
    $msgItem[ "sender" ] = $senderName ;
    $msgItem[ "text"   ] = $msg ;
    $msgItem[ "read"   ] = $read ;
    $this->log( "Created  message    : " . $this->messageDesc( $msgItem ), Logger::LOG_DEBUG, __METHOD__ ) ;
    return $msgItem ;
  }

  public   function messageDesc( &$msgItem, $pretty = false )
  {
    if( $pretty ) return json_encode( $msgItem, JSON_PRETTY_PRINT ) ;
    return json_encode( $msgItem ) ;
  }


  // Create a messaging address if it does not already exists
  public   function initAddress( $address )
  {
    $messagingData = Resources::readDefaultDataFile() ;
    if( array_key_exists( $address, $messagingData ) ) return ;
    $messagingData[ $address ] = [] ;
    Resources::writeDefaultDataFile( $messagingData ) ;
  }
  
  // Two cases :
  // #1 : a customer is sending a message to a messaging address
  // #2 : the owner of a messaging address sends a message to a customer
  public   function sendMessage( &$msgItem )
  {
    $from = $msgItem[ "from" ] ;
    $to   = $msgItem[ "to"   ] ;
    $messagingData = Resources::readDefaultDataFile() ;
    
    // Case #2 : owner -> customer
    if( array_key_exists( $from, $messagingData ) )
    {
      if( !array_key_exists( $to, $messagingData[ $from ] ) ) $messagingData[ $from ][ $to ] = [] ;
      array_push( $messagingData[ $from ][ $to ], $msgItem ) ;
    }
    // Case #1 : customer -> messaging address
    else if( array_key_exists( $to, $messagingData ) )
    {
      if( !array_key_exists( $from, $messagingData[ $to ] ) ) $messagingData[ $to ][ $from ] = [] ;
      array_push( $messagingData[ $to ][ $from ], $msgItem ) ;
    }
    // Case #1 : customer -> unknown messaging address
    else
    {
      return false ;
    }
    
    $this->log( "Sending  message    : " . $this->messageDesc( $msgItem ), Logger::LOG_INFO, __METHOD__ ) ;
    Resources::writeDefaultDataFile( $messagingData ) ;

    return true ;
  }

  public   function readMessages( $address, $type, $from = null, $markAsRead = true )   // type in [all|unread], from in [null|"<specific customer>"]
  {
    $messagingData = Resources::readDefaultDataFile() ;
    if( !array_key_exists( $address, $messagingData ) ) return [] ;
    
    $res = [] ;
    if( empty( $from ) )  // All customer messages
    {
      foreach( $messagingData[ $address ] as $key => $val )
      {
        for( $i = 0 ; $i < count( $val ) ; $i++ )
        {
          if( ( $type === "all" ) || ( ( $type === "unread" ) && ( $messagingData[ $address ][ $key ][ $i ][ "read" ] === false ) ) )
          {
            if( $markAsRead === true )
            {
              $messagingData[ $address ][ $key ][ $i ][ "read" ] = true ;
            }
            array_push( $res, $messagingData[ $address ][ $key ][ $i ] ) ;
          }
        }
      }
    }
    else  // one specific customer
    {
      if( !array_key_exists( $from, $messagingData[ $address ] ) ) return [] ;
      
      for( $i = 0 ; $i < count( $messagingData[ $address ][ $from ] ) ; $i++ )
      {
        if( ( $type === "all" ) || ( ( $type === "unread" ) && ( $messagingData[ $address ][ $from ][ $i ][ "read" ] === false ) ) )
        {
          if( $markAsRead === true )
          {
            $messagingData[ $address ][ $from ][ $i ][ "read" ] = true ;
          }
          array_push( $res, $messagingData[ $address ][ $from ][ $i ] ) ;
        }
      }
    }

    $slog = "Reading  message(s) : " . count( $res ) . " results" ;
    if( $markAsRead === true )
    {
      $slog .= " marked as read" ;
      Resources::writeDefaultDataFile( $messagingData ) ;
    }
    $this->log( $slog, Logger::LOG_INFO, __METHOD__ ) ;
    
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
