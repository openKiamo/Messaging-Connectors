<?php
namespace UserFiles\Messaging\Connector ;


require_once __DIR__ . DIRECTORY_SEPARATOR . "KConnectorMessagingFBMessenger" . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR . "KConnectorMessagingFBMessenger" . DIRECTORY_SEPARATOR . "MessagingManager.php" ;

use KiamoConnectorSampleToolsFB\Logger ;
use KiamoConnectorSampleToolsFB\Module ;
use UserFiles\Messaging\Connector\KConnectorMessagingFBMessenger\MessagingManager ;

use Kiamo\Bundle\AdminBundle\Utility\Messaging\ConnectorConfiguration    ;
use Kiamo\Bundle\AdminBundle\Utility\Messaging\GenericConnectorInterface ;


class KConnectorMessagingFBMessenger extends    Module
                                     implements GenericConnectorInterface
{
  const RootPath    = __DIR__ . DIRECTORY_SEPARATOR . "KConnectorMessagingFBMessenger" ;


  public function __construct( ConnectorConfiguration $configuration )
  {
    parent::__construct( self::RootPath,
                         self::RootPath . DIRECTORY_SEPARATOR . "logs", 
                         self::RootPath . DIRECTORY_SEPARATOR . "conf" ) ;
    $this->log( "------------------------------------------------------------------------------", Logger::LOG_INFO, __METHOD__ ) ;
    $this->_parameters = $configuration ;
    $this->_msgManager = new MessagingManager( $this ) ;
    $this->log( "INIT : OK", Logger::LOG_INFO, __METHOD__ ) ;
  }

  public function getName()
  {
    return $this->getConf( "self.service" ) ;
  }

  public function fetch( $parameterBag )
  {
    $this->log( "Fetching message(s)", Logger::LOG_INFO, __METHOD__ ) ;
    $msgArr     = $this->_msgManager->readMessages() ;  // read all unread user messages from the messaging address, and mark them as read
    $this->log( "Fetched " . count( $msgArr ) . " message(s)", Logger::LOG_INFO, __METHOD__ ) ;
    foreach( $msgArr as $msg )
    {
      $inputMsg = [
        'id'         => $msg[ "id"     ],
        'createdAt'  => $msg[ "date"   ],
        'senderId'   => $msg[ "from"   ],
        'senderName' => $msg[ "sender" ],
        'content'    => $msg[ "text"   ],
      ] ;
      $this->log( "=> adding message : " . json_encode( $inputMsg ), Logger::LOG_INFO, __METHOD__ ) ;
      $parameterBag->addMessage( $inputMsg ) ;
    }

    return $parameterBag;
  }

  public function send( array $messageTask )
  {
    $this->log( "Sending message : " . json_encode( $messageTask ), Logger::LOG_INFO, __METHOD__ ) ;

    $msg    = $messageTask[ "content" ] ;
    $to     = $messageTask[ "to" ][ "id" ] ;
    $from   = $this->_msgManager->pageId ;
    $sender = $messageTask[ "from" ][ "name" ] ;
    $read   = true ;

    $msgItem = $this->_msgManager->createMessage( $msg, $from, $to, null, null, $sender, $read ) ;
    $this->log( "Sending mmessage : " . json_encode( $msgItem ), Logger::LOG_INFO, __METHOD__ ) ;
    $this->_msgManager->sendMessage( $msgItem ) ;
  }
}
?>