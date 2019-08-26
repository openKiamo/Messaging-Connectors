<?php
namespace UserFiles\Messaging\Connector ;


// Kiamo Messaging Connector Utilities
// ---
define( 'KIAMO_MESSAGING_UTILITY', '../../../../../www/Symfony/src/Kiamo/Bundle/AdminBundle/Utility/Messaging' ) ;

require_once __DIR__ . DIRECTORY_SEPARATOR . KIAMO_MESSAGING_UTILITY . DIRECTORY_SEPARATOR . "ConnectorConfiguration.php"    ;
require_once __DIR__ . DIRECTORY_SEPARATOR . KIAMO_MESSAGING_UTILITY . DIRECTORY_SEPARATOR . "GenericConnectorInterface.php" ;

use Kiamo\Bundle\AdminBundle\Utility\Messaging\ConnectorConfiguration    ;
use Kiamo\Bundle\AdminBundle\Utility\Messaging\GenericConnectorInterface ;


// Messaging Connector Toolkit
// ---
require_once __DIR__ . DIRECTORY_SEPARATOR . 'KConnectorPushFacebook' . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'KConnectorPushFacebook' . DIRECTORY_SEPARATOR . "MessagingManager.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'KConnectorPushFacebook' . DIRECTORY_SEPARATOR . "MessageMapper.php"    ;

use KiamoConnectorSampleToolsPushFacebook\Logger ;
use KiamoConnectorSampleToolsPushFacebook\Module ;
use UserFiles\Messaging\Connector\KConnectorPushFacebook\MessagingManager ;
use UserFiles\Messaging\Connector\KConnectorPushFacebook\MessageMapper    ;


// Kiamo Messaging Connector
// ---
class KConnectorPushFacebook extends    Module
                             implements GenericConnectorInterface
{
  const RootPath = __DIR__ . DIRECTORY_SEPARATOR . 'KConnectorPushFacebook' ;

  public function __construct( ConnectorConfiguration $configuration )
  {
    parent::__construct( self::RootPath,
                         self::RootPath . DIRECTORY_SEPARATOR . "logs", 
                         self::RootPath . DIRECTORY_SEPARATOR . "conf" ) ;
    $this->log( "------------------------------------------------------------------------------", Logger::LOG_INFO, __METHOD__ ) ;
    $this->log( "Service : " . $this->getConf( "self.service" )                                 , Logger::LOG_INFO, __METHOD__ ) ;
    $this->log( "Version : " . $this->getConf( "self.version" )                                 , Logger::LOG_INFO, __METHOD__ ) ;

    $this->_parameters = $configuration ;
    $this->_msgManager = new MessagingManager( $this ) ;
    $this->_msgMapper  = new MessageMapper(    $this ) ;

    $this->log( "INIT : OK"                                                                     , Logger::LOG_INFO, __METHOD__ ) ;
  }

  public function getName()
  {
    return $this->getConf( "self.service" ) ;
  }

  private function mapMessages( $messageArr, &$parameterBag )
  {
    foreach( $messageArr as $msg )
    {
      $this->log( "==> New message : " . json_encode( $msg ), Logger::LOG_DEBUG, __METHOD__ ) ;
      $inputMsg = $this->_msgMapper->mapMessage( $msg, MessageMapper::WAY_INP ) ;
      $this->log( "=> adding message : " . json_encode( $inputMsg ), Logger::LOG_INFO, __METHOD__ ) ;
      $parameterBag->addMessage( $inputMsg ) ;
    }
  }

  public  function fetch( $parameterBag )
  {
    $this->setActionId() ;
    $this->log( "Fetching message(s)", Logger::LOG_INFO, __METHOD__ ) ;

    $msgRes = $this->_msgManager->readMessages() ;  // read all unread user messages
    $this->log( "Fetched " . count( $msgRes ) . " message(s)", Logger::LOG_INFO, __METHOD__ ) ;

    $this->mapMessages( $msgRes, $parameterBag ) ;
    
    $this->clearActionId() ;

    return $parameterBag;
  }

  public function send( array $messageTask )
  {
    $this->setActionId() ;
    $this->log( "Sending message : " . json_encode( $messageTask ), Logger::LOG_INFO, __METHOD__ ) ;

    $outputMsg = $this->_msgMapper->mapMessage( $messageTask, MessageMapper::WAY_OUT ) ;
    $res = $this->_msgManager->sendMessage( $outputMsg ) ;
    if( $res === true ) $this->log( "==> message sent : OK"   , Logger::LOG_INFO, __METHOD__ ) ;
    else                $this->log( "==> message sent : KO...", Logger::LOG_WARN, __METHOD__ ) ;
    $this->clearActionId() ;
  }
}
?>