<?php 

namespace KiamoConnectorSampleToolsFacebookPush ;


require_once( __DIR__ . DIRECTORY_SEPARATOR . 'dict.php'        ) ;
require_once( __DIR__ . DIRECTORY_SEPARATOR . 'files.php'       ) ;
require_once( __DIR__ . DIRECTORY_SEPARATOR . 'confManager.php' ) ;
require_once( __DIR__ . DIRECTORY_SEPARATOR . 'logger.php'      ) ;

use KiamoConnectorSampleToolsFacebookPush\Dict        ;
use KiamoConnectorSampleToolsFacebookPush\Files       ;
use KiamoConnectorSampleToolsFacebookPush\ConfManager ;
use KiamoConnectorSampleToolsFacebookPush\Logger      ;


/**
  Run Bundle
  */
class Module
{
  const ConfManagerTool          = 'ConfManager' ;
  const ConfManagerDefaultFolder = 'conf'        ;
  const LoggerTool               = 'Logger'      ;
  const LoggerDefaultFolder      = 'logs'        ;
  const ResourcesDefaultFolder   = 'data'        ;

  const Instance                 = 'Instance'    ;
  const Folder                   = 'Folder'      ;


  public   function __construct( $_rootPath = null, $_logsPath = null, $_confPath = null, $name = null, $confType = ConfManager::UserConfType )
  {
    $this->_rootPath          = $_rootPath ;
    if( empty( $this->_rootPath ) ) $this->_rootPath = __DIR__ . DIRECTORY_SEPARATOR . '..' ;
    $this->_logsPath          = $_logsPath ;
    if( empty( $this->_logsPath ) ) $this->_logsPath = $this->_rootPath . DIRECTORY_SEPARATOR . self::LoggerDefaultFolder ;
    if( !Files::existsFile( $this->_logsPath ) ) mkdir( $this->_logsPath ) ;
    $this->_dataPath          = $this->_rootPath . DIRECTORY_SEPARATOR . self::ResourcesDefaultFolder ;
    if( !Files::existsFile( $this->_dataPath ) ) mkdir( $this->_dataPath ) ;
    $this->_confPath          = $_confPath ;
    if( empty( $this->_confPath ) ) $this->_confPath = $this->_rootPath . DIRECTORY_SEPARATOR . self::ConfManagerDefaultFolder ;
    $this->elements           =         [] ;
    $this->name               =      $name ;
    if( empty( $this->name ) ) $this->name = get_class( $this ) ;
    $this->name               =  array_slice( explode( '\\', $this->name ), -1 )[0] ;
    $this->confType           =  $confType ;

    $logMethod = $this->name . '::__construct' ;
    $this->log( "------------------------------------------------------------------------------", Logger::LOG_INFO, $logMethod ) ;
    $this->log( "INIT : OK", Logger::LOG_INFO, $logMethod ) ;
  }

  public   function __destruct()
  {
  }

  public   function getName()
  {
    return $this->name ;
  }
  

  // Elements
  public   function add( $key, &$element, $_elementFolderPath = null )
  {
    $_elementFP = $_elementFolderPath ;
    if( empty( $_elementFP ) )
      $_elementFP = '' ;
    $this->elements[ $key ] = [ self::Instance => $element, self::Folder => $_elementFP ] ;
  }

  public   function setElementFolderPath( $key, $_elementFolderPath )
  {
    if( !empty( $_elementFolderPath ) && array_key_exists( $key, $this->elements ) )
      $this->elements[ $key ][self::Folder] = $_elementFolderPath ;
  }

  public   function __isset( $key )
  {
    return array_key_exists( $key, $this->elements ) ;
  }

  public   function __get( $key )
  {
    if( $this->__isset( $key ) )
    {
      return $this->elements[ $key ][ self::Instance ] ;
    }
    else
    {
      return $this->_init( $key ) ;
    }
    return null ;
  }
  
  private  function _init( $key )
  {
    $res = null ;
    switch( $key )
    {
    case self::ConfManagerTool :
      $path = $this->getRootPath( $key ) ;
      $res  = new ConfManager( $path ) ;
      $this->elements[ $key ] = [ self::Instance => &$res, self::Folder => $path ] ;
      break ;
    case self::LoggerTool :
      $path = $this->getRootPath( $key ) ;
      // Don't setup a logs folder name if the logs path has been provided manually
      $name = null ;
      if( $path === $this->_logsPath ) $name = $this->name ;
      $res = new Logger( $this->ConfManager, $path, $name ) ;
      $this->elements[ $key ] = [ self::Instance => &$res, self::Folder => $path ] ;
      break ;
    default :
      break ;
    }
    return $res ;
  }

  // Element Root Path
  private  function getRootPath( $element )
  {
    $res = $this->_rootPath ;
    switch( $element )
    {
    case self::ConfManagerTool :
      $res = $this->_confPath ;
      break ;
    case self::LoggerTool :
      $res = $this->_logsPath ;
      break ;
    default :
      break ;
    }
    if( array_key_exists( $element, $this->elements ) )
      $res = $this->elementFolderPaths[ $element ][self::Folder] ;
    return $res ;
  }

  
  // Helpers
  // ###
  
  // Configuration
  public   function getGlobalConf( $arr = null )
  {
    return $this->ConfManager->getConf( $arr ) ;
  }
  public   function getConf( $arr = null )
  {
    if( empty( $this->name ) ) return null ;
    $tgtConf = $this->confType . '.' . $this->name ;
    if( !empty( $arr ) ) $tgtConf .= '.' . Dict::joinKey( $arr ) ;
    return $this->ConfManager->getConf( $tgtConf ) ;
  }

  // Logs
  public   function log( $str, $level = Logger::LOG_DEBUG, $method = '', $actionId = null, $indentLevel = 0 )
  {
    return $this->Logger->log( $str, $level, $method, $actionId, $indentLevel ) ;
  }

  
  // Action Id
  // ---
  public function setActionId( $id = null )
  {
    $this->Logger->setActionId( $id ) ;
  }
  public function clearActionId()
  {
    $this->Logger->clearActionId() ;
  }
  public function getActionId()
  {
    return $this->Logger->getActionId() ;
  }
}
?>
