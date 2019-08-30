<?php
namespace UserFiles\Messaging\Connector ;


/**/
// Kiamo v6.x : Messaging Utilities
// -----

const KIAMO_ROOT              = __DIR__ . "/../../../../../" ;
const KIAMO_MESSAGING_UTILITY = KIAMO_ROOT . "www/Symfony/src/Kiamo/Bundle/AdminBundle/Utility/Messaging/" ;

require_once KIAMO_MESSAGING_UTILITY . "ParameterBag.php"              ;
require_once KIAMO_MESSAGING_UTILITY . "ConnectorConfiguration.php"    ;
require_once KIAMO_MESSAGING_UTILITY . "GenericConnectorInterface.php" ;

use Kiamo\Bundle\AdminBundle\Utility\Messaging\ParameterBag              ;
use Kiamo\Bundle\AdminBundle\Utility\Messaging\ConnectorConfiguration    ;
use Kiamo\Bundle\AdminBundle\Utility\Messaging\GenericConnectorInterface ;
/**/


/*
// Kiamo v7.x : Messaging Utilities
// -----

const KIAMO_ROOT              = __DIR__ . "/../../../../../" ;
const KIAMO_MESSAGING_UTILITY = KIAMO_ROOT . "www/Symfony/src/Kiamo/Admin/Utility/Messaging/" ;

require_once KIAMO_MESSAGING_UTILITY . "ParameterBag.php"              ;
require_once KIAMO_MESSAGING_UTILITY . "ConnectorConfiguration.php"    ;
require_once KIAMO_MESSAGING_UTILITY . "GenericConnectorInterface.php" ;

use Kiamo\Admin\Utility\Messaging\ParameterBag              ;
use Kiamo\Admin\Utility\Messaging\ConnectorConfiguration    ;
use Kiamo\Admin\Utility\Messaging\GenericConnectorInterface ;
*/


// Messaging Connector Toolkit
// ---
require_once __DIR__ . DIRECTORY_SEPARATOR . 'KConnectorMessagingTwitter' . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'KConnectorMessagingTwitter' . DIRECTORY_SEPARATOR . "core"  . DIRECTORY_SEPARATOR . "MessagingManager.php" ;

use KiamoConnectorSampleToolsTwitter\Logger ;
use KiamoConnectorSampleToolsTwitter\Module ;
use UserFiles\Messaging\Connector\KConnectorMessagingTwitter\MessagingManager ;


// Kiamo Messaging Connector
// ---
class KConnectorMessagingTwitter extends    Module
                                 implements GenericConnectorInterface
{
  const RootPath = __DIR__ . DIRECTORY_SEPARATOR . 'KConnectorMessagingTwitter' ;

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
    $this->setActionId() ;
    $this->log( "Fetching message(s)", Logger::LOG_INFO, __METHOD__ ) ;

    $params             = $parameterBag->getParameters() ;
    $lastReadMessageKey = 'KConnectorMessagingTwitter' . '.lastReadMessageId' ;
    $lastReadMessageId  = '' ;
    if( array_key_exists( $lastReadMessageKey, $params ) ) $lastReadMessageId = $params[ $lastReadMessageKey ] ;
    if( !empty( $lastReadMessageId ) ) $this->log( "==> lastMessageId=" . $lastReadMessageId, Logger::LOG_DEBUG, __METHOD__ ) ;

    $msgRes             = $this->_msgManager->readMessages( $lastReadMessageId ) ;  // read all unread user messages from the messaging address
    $msgArr             = $msgRes[ 'newMessages'       ] ;
    $this->log( "Fetched " . count( $msgArr ) . " message(s)", Logger::LOG_INFO, __METHOD__ ) ;
    if( $lastReadMessageId !== $msgRes[ 'lastReadMessageId' ] )
    {
      $this->log( "==> new lastMessageId=" . $msgRes[ 'lastReadMessageId' ], Logger::LOG_DEBUG, __METHOD__ ) ;
      $parameterBag->setParameter( $lastReadMessageKey, $msgRes[ 'lastReadMessageId' ] ) ;
    }

    foreach( $msgArr as $msg )
    {
      $inputMsg = [
        'id'         => $msg[ "id"      ],
        'createdAt'  => $msg[ "date"    ],
        'senderId'   => $msg[ "from"    ],
        'senderName' => $msg[ "sender"  ],
        'content'    => $msg[ "message" ],
      ] ;

      // Special case : history before connector
      if( $msg[ "from" ] === $this->getConf( 'accessData.credentials.userId' ) )
      {
        $inputMsg[ "senderId"   ] = $msg[ "to"        ] ;
        $inputMsg[ "senderName" ] = $msg[ "recipient" ] ;
        $inputMsg[ "content"    ] = $inputMsg[ "senderName" ] . ' ==> ' . $msg[ "message" ] ;
      }

      $this->log( "=> adding message : " . json_encode( $inputMsg ), Logger::LOG_INFO, __METHOD__ ) ;
      $parameterBag->addMessage( $inputMsg ) ;
    }
    
    $this->clearActionId() ;

    return $parameterBag;
  }

  public function send( array $messageTask )
  {
    $this->setActionId() ;
    $this->log( "Sending message : " . json_encode( $messageTask ), Logger::LOG_INFO, __METHOD__ ) ;

    $msg = $messageTask[ "content" ] ;
    $to  = $messageTask[ "to" ][ "id" ] ;

    $this->log( "Sending message to user id '" . $to . "' : '" . $msg . "'", Logger::LOG_INFO, __METHOD__ ) ;
    $this->_msgManager->sendMessage( $to, $msg ) ;
    $this->clearActionId() ;
  }
}
?>