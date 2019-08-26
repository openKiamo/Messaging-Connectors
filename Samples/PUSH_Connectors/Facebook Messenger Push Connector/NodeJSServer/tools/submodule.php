<?php 

namespace KiamoConnectorSampleToolsFacebookPush ;


require_once( __DIR__ . DIRECTORY_SEPARATOR . 'dict.php'        ) ;
require_once( __DIR__ . DIRECTORY_SEPARATOR . 'confManager.php' ) ;
require_once( __DIR__ . DIRECTORY_SEPARATOR . 'logger.php'      ) ;

use KiamoConnectorSampleToolsFacebookPush\Dict        ;
use KiamoConnectorSampleToolsFacebookPush\ConfManager ;
use KiamoConnectorSampleToolsFacebookPush\Logger      ;


/**
  SubModule
  */
class SubModule
{
  public   function __construct( $_parent, $_name = null, $_confType = ConfManager::UserConfType )  // The _parent must be a Module
  {
    $this->_parent   = $_parent   ;
    $this->_name     = $_name     ;
    if( empty( $this->_name ) ) $this->_name = get_class( $this ) ;
    $this->_name     = array_slice( explode( '\\', $this->_name ), -1 )[0] ;
    $this->_confType = $_confType ;

    $logMethod = $this->_name . '::__construct' ;
    $this->log( "------------------------------------------------------------------------------", Logger::LOG_VERBOSE, $logMethod ) ;
    $this->log( "INIT : OK", Logger::LOG_VERBOSE, $logMethod ) ;
  }

  public   function __destruct()
  {
  }


  public   function getName()
  {
    return $this->_name ;
  }
  
  // Configuration
  public   function getGlobalConf( $arr = null )
  {
    return $this->_parent->ConfManager->getConf( $arr ) ;
  }
  public   function getConf( $arr = null )
  {
    if( empty( $this->_name ) ) return null ;
    $tgtConf = $this->_confType . '.' . $this->_name ;
    if( !empty( $arr ) ) $tgtConf .= '.' . Dict::joinKey( $arr ) ;
    return $this->_parent->ConfManager->getConf( $tgtConf ) ;
  }

  // Logs
  public   function log( $str, $level = Logger::LOG_DEBUG, $method = '', $actionId = null, $indentLevel = 0 )
  {
    return $this->_parent->Logger->log( $str, $level, $method, $actionId, $indentLevel ) ;
  }

  
  // Action Id
  // ---
  public function setActionId( $id = null )
  {
    $this->_parent->Logger->setActionId( $id ) ;
  }
  public function clearActionId()
  {
    $this->_parent->Logger->clearActionId() ;
  }
  public function getActionId()
  {
    return $this->_parent->Logger->getActionId() ;
  }
}
?>
