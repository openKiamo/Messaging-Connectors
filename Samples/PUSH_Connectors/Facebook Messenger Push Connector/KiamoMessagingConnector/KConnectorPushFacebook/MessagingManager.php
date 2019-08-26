<?php

namespace UserFiles\Messaging\Connector\KConnectorPushFacebook ;


require_once __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;


use KiamoConnectorSampleToolsPushFacebook\Datetimes ;
use KiamoConnectorSampleToolsPushFacebook\Logger    ;
use KiamoConnectorSampleToolsPushFacebook\SubModule ;
use KiamoConnectorSampleToolsPushFacebook\Resources ;
use KiamoConnectorSampleToolsPushFacebook\Uuids     ;
use KiamoConnectorSampleToolsPushFacebook\Webs      ;


class MessagingManager extends SubModule
{
  public    function __construct( &$_parent )
  {
    parent::__construct( $_parent, get_class( $_parent ) ) ;

    $this->initRuntimeData()   ;
    $this->initAccessData()    ;

    $this->log( "INIT : OK", Logger::LOG_INFO, __METHOD__ ) ;
  }

  private  function initRuntimeData()
  {
    $this->selfName = $this->getConf( "identity.name" ) ;
    $this->selfId   = $this->getConf( "identity.id"   ) ;

    $this->log( "Name    : " . $this->selfName, Logger::LOG_INFO, __METHOD__ ) ;
  }
  private  function initAccessData()
  {
    $protocol          = $this->getConf( "pushServer.protocol"    ) ;
    $domain            = $this->getConf( "pushServer.domain"      ) ;
    $port              = $this->getConf( "pushServer.port"        ) ;
    $this->serverUrl   = $protocol . '://' . $domain . ':' . $port  ;
    $this->verifyToken = $this->getConf( "pushServer.verifyToken" ) ;

    $this->log( "Push Server URL : " . $this->serverUrl, Logger::LOG_INFOP, __METHOD__ ) ;
  }


  /* ----------------------
     Connector entry points
  */

  public   function readMessages()
  {
    $url    = $this->serverUrl . '/messages?way=inp&sort=false&token=' . $this->verifyToken ;
    $header = [ "Content-Type" => "application/json" ] ;
    $res    = Webs::restRequest( $url, null, $header ) ;
    $ret    = null ;

    if( $res[ Webs::REST_REQUEST_STATUS ] !== true || $res[ Webs::REST_REQUEST_HTTPCODE ] !== 200 )
    {
      $ret = [] ;
      if( $res[ Webs::REST_REQUEST_HTTPCODE ] === 0 ) $this->log( "Read message(s) issue : unable to establish connection with the Node Server...", Logger::LOG_WARN, __METHOD__ ) ;
      else                                            $this->log( "Read message(s) issue : code=" . $res[ Webs::REST_REQUEST_HTTPCODE ] . "comment='" . $res[ Webs::REST_REQUEST_RESULT ] . "'", Logger::LOG_WARN, __METHOD__ ) ;
    }
    else
    {
      $ret = $res[ Webs::REST_REQUEST_RESULT ] ;
      if( gettype( $ret ) === string ) $ret = json_decode( $res[ Webs::REST_REQUEST_RESULT ], true ) ;
      $this->log( "Read message(s) : " . count( $ret ) . " results", Logger::LOG_INFO, __METHOD__ ) ;
    }

    return $ret ;
  }
  
  public   function sendMessage( $messageData )
  {
    $this->log( "Sending message : " . json_encode( $messageData ), Logger::LOG_DEBUG, __METHOD__ ) ;

    $url                    = $this->serverUrl . '/send' ;
    $messageData[ "token" ] = $this->verifyToken ;
    $header                 = [ "Content-Type" => "application/json" ] ;
    $res                    = Webs::restRequest( $url, json_encode( $messageData ), $header ) ;
    $ret                    = true ;

    if( $res[ Webs::REST_REQUEST_STATUS ] !== true || $res[ Webs::REST_REQUEST_HTTPCODE ] !== 200 )
    {
      $ret = false ;
      $this->log( "Send message issue : '" . $res[ Webs::REST_REQUEST_RESULT ] . "'", Logger::LOG_WARN, __METHOD__ ) ;
    }
    else
    {
      unset( $messageData[ "token" ] ) ;
      $this->log( "==> message sent : " . json_encode( $messageData ), Logger::LOG_DEBUG, __METHOD__ ) ;
    }

    return true ;
  }
}
?>
