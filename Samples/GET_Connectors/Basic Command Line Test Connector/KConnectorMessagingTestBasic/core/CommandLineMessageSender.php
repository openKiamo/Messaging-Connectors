<?php

namespace UserFiles\Messaging\Connector\KConnectorMessagingTestBasic ;


require_once __DIR__ . DIRECTORY_SEPARATOR . "../tools" . DIRECTORY_SEPARATOR . "autoload.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR . "MessagingManager.php" ;


use KiamoConnectorSampleToolsBasic\Datetimes ;
use KiamoConnectorSampleToolsBasic\Logger    ;
use KiamoConnectorSampleToolsBasic\Module    ;
use KiamoConnectorSampleToolsBasic\Resources ;
use KiamoConnectorSampleToolsBasic\Uuids     ;


class CommandLineMessageSender extends Module
{
  const DefaultMessagingAddress = 'contact@openKiamo' ;

  const VerbMsg    = 'msg'    ;
  const VerbTo     = 'to'     ;
  const VerbFrom   = 'from'   ;
  const VerbSender = 'sender' ;
  const VerbRead   = 'read'   ;


  public    function __construct( $strictMode = false )
  {
    parent::__construct() ;

    $this->msgMgr = new MessagingManager( self::DefaultMessagingAddress ) ;
    $this->sendMessage() ;
  }

  private  function getLineParam( $name, $default = null )
  {
    $args = getopt( null, [ $name . ":" ] ) ;
    if( !array_key_exists( $name, $args ) ) return $default ;
    return $args[ $name ] ;
  }
  
  private  function sendMessage()
  {
    $msg    = $this->getLineParam( self::VerbMsg   , '<NO_TEXT>'  ) ;
    $to     = $this->getLineParam( self::VerbTo    , self::DefaultMessagingAddress ) ;
    $from   = $this->getLineParam( self::VerbFrom  , '0666666666' ) ;
    $sender = $this->getLineParam( self::VerbSender, 'SIn Seb' ) ;
    $read   = $this->getLineParam( self::VerbRead  , false ) ;
    if( $read === "true" ) $read = true  ;
    if( $read !=   true  ) $read = false ;

    $msgItem = $this->msgMgr->createMessage( $msg, $from, $to, $sender, $read ) ;
    $this->msgMgr->sendMessage( $msgItem ) ;
  }
}

// Usage example :
// > php CommandLineMessageSender.php -f --msg="<text>" --to="<to>" --from="<from>" [ --sender="<senderName>" --read="<read status>" ]
new CommandLineMessageSender() ;
?>
