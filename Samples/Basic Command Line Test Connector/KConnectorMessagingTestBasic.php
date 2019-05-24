<?php
namespace UserFiles\Messaging\Connector ;


require_once __DIR__ . DIRECTORY_SEPARATOR . "KConnectorMessagingTestBasic" . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR . "KConnectorMessagingTestBasic" . DIRECTORY_SEPARATOR . "MessagingManager.php" ;

use KiamoConnectorSampleTools\Logger ;
use KiamoConnectorSampleTools\Module ;
use UserFiles\Messaging\Connector\KConnectorMessagingTestBasic\MessagingManager ;

use Kiamo\Bundle\AdminBundle\Utility\Messaging\ConnectorConfiguration    ;
use Kiamo\Bundle\AdminBundle\Utility\Messaging\GenericConnectorInterface ;


class KConnectorMessagingTestBasic extends    Module
                                 implements GenericConnectorInterface
{
  const DisplayName = 'Kiamo Connector Messaging Test Basic' ;
  const RootPath    = __DIR__ . DIRECTORY_SEPARATOR . "KConnectorMessagingTestBasic" ;

  const MessagingAddress = 'contact@openKiamo' ;


  public function __construct( ConnectorConfiguration $configuration )
  {
    parent::__construct( self::RootPath,
                         self::RootPath . DIRECTORY_SEPARATOR . "logs", 
                         self::RootPath . DIRECTORY_SEPARATOR . "conf" ) ;
    $this->log( "------------------------------------------------------------------------------", Logger::LOG_INFO, __METHOD__ ) ;
    $this->_parameters = $configuration ;
    $this->_msgManager = new MessagingManager( self::MessagingAddress ) ;
    $this->log( "INIT : OK", Logger::LOG_INFO, __METHOD__ ) ;
  }

  public function getName()
  {
    return self::DisplayName ;
  }

  public function fetch( $parameterBag )
  {
    $type       = "unread" ;
    $markAsRead = true ;
    $msgArr     = $this->_msgManager->readMessages( self::MessagingAddress, $type, null, $markAsRead ) ;  // read all unread user messages from the messaging address, and mark them as read
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

  /* $messageTask sample :
      {
        "to" : {
          "id":"0655443322",
          "name":"SIn Seb"
        },
        "reference":"GsxpZVGFRTOEWADS_1558445827507",
        "status":"sending",
        "task_ref":101283,
        "from" : {
          "name":"Remi",
          "agent_id":1
        },
        "content" : "Hello Mr, que puis je faire pour vous aider ?",
        "connector_id":"4",
        "stream":"generic"
      }
  */
  public function send( array $messageTask )
  {
    $this->log( "Sending message : " . json_encode( $messageTask ), Logger::LOG_INFO, __METHOD__ ) ;

    $msg    = $messageTask[ "content" ] ;
    $to     = $messageTask[ "to" ][ "id" ] ;
    $from   = self::MessagingAddress ;
    $sender = $messageTask[ "from" ][ "name" ] ;
    $read   = true ;

    $msgItem = $this->_msgManager->createMessage( $msg, $from, $to, $sender, $read ) ;
    $this->_msgManager->sendMessage( $msgItem ) ;
  }
}
?>