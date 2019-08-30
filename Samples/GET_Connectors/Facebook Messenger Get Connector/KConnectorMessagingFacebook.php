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
require_once __DIR__ . DIRECTORY_SEPARATOR . 'KConnectorMessagingFacebook' . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'KConnectorMessagingFacebook' . DIRECTORY_SEPARATOR . "core"  . DIRECTORY_SEPARATOR . "MessagingManager.php" ;

use KiamoConnectorSampleToolsFacebook\Logger ;
use KiamoConnectorSampleToolsFacebook\Module ;
use UserFiles\Messaging\Connector\KConnectorMessagingFacebook\MessagingManager ;


// Kiamo Messaging Connector
// ---
class KConnectorMessagingFacebook extends    Module
                                  implements GenericConnectorInterface
{
  const RootPath = __DIR__ . DIRECTORY_SEPARATOR . 'KConnectorMessagingFacebook' ;

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
    $lastReadMessageKey = 'KConnectorMessagingFacebook' . '.lastReadMessageTs' ;
    $lastReadMessageTs  = '' ;
    if( array_key_exists( $lastReadMessageKey, $params ) ) $lastReadMessageTs = $params[ $lastReadMessageKey ] ;
    if( !empty( $lastReadMessageTs ) ) $this->log( "==> lastMessageTs=" . $lastReadMessageTs, Logger::LOG_DEBUG, __METHOD__ ) ;

    $msgRes             = $this->_msgManager->readMessages( $lastReadMessageTs ) ;  // read all unread user messages from the messaging address
    $msgArr             = $msgRes[ 'newMessages' ] ;
    $this->log( "Fetched " . count( $msgArr ) . " message(s)", Logger::LOG_INFO, __METHOD__ ) ;
    if( $lastReadMessageTs !== $msgRes[ 'lastReadMessageTs' ] )
    {
      $this->log( "==> new lastMessageTs=" . $msgRes[ 'lastReadMessageTs' ], Logger::LOG_DEBUG, __METHOD__ ) ;
      $parameterBag->setParameter( $lastReadMessageKey, $msgRes[ 'lastReadMessageTs' ] ) ;
    }

    foreach( $msgArr as $msg )
    {
      $this->log( "==> New message : " . json_encode( $msg ), Logger::LOG_TRACE, __METHOD__ ) ;
      $inputMsg = [
        'id'         => $msg[ "id"       ],
        'createdAt'  => $msg[ "date"     ],
        'senderId'   => $msg[ "uuid"     ],
        'senderName' => $msg[ "userName" ],
        'content'    => $msg[ "message"  ],
      ] ;

      // Special case : history before connector
      if( $msg[ "from" ][ "id" ] === $this->getConf( 'accessData.pageId' ) )
      {
        $inputMsg[ "content"    ] = '[' . $this->getConf( 'accessData.pageName' ) . '] ' . $msg[ "message" ] ;
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

    $msg            = $messageTask[ "content" ]         ;
    $uuid           = $messageTask[ "to"      ][ "id" ] ;
    $idArr          = explode( '.', $uuid, 2 ) ;
    $conversationId = $idArr[ 0 ] ;
    $to             = $idArr[ 1 ] ;

    $this->log( "Sending message to user id '" . $to . "', conversationId='" . $conversationId . "' : '" . $msg . "'", Logger::LOG_INFO, __METHOD__ ) ;
    $this->_msgManager->sendMessage( $to, $msg, $conversationId ) ;
    $this->clearActionId() ;
  }
}
?>