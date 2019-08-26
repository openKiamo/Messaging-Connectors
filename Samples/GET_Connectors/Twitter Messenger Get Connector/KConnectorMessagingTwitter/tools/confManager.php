<?php

namespace KiamoConnectorSampleToolsTwitter ;

require_once __DIR__ . DIRECTORY_SEPARATOR .   "dict.php" ;

use KiamoConnectorSampleToolsTwitter\Dict ;


/***********************************************
  Configuration manager
  */
class ConfManager
{
  const MainFileBasename    = '_config' ;
  const DefaultFolderName   = 'conf' ;
  const DefaultRootPathPath = __DIR__ . '/../' ;
  const TagKey              = 'key' ;
  const TagItems            = 'items' ;
  const UserConfType        = 'user' ;


  // INIT
  // ---
  public    function __construct( $_confPath = null )
  {
    $this->rootPath   = $_confPath ;
    if( empty( $this->rootPath ) )
    {
      $this->rootPath = ConfManager::DefaultRootPathPath . '/' . ConfManager::DefaultFolderName ;
    }
    $confBaseFile = self::_getConfFile( ConfManager::MainFileBasename ) ;

    $this->Logger = null    ;
    $this->config = new Dict() ;

    // Check if root conf file exists
    // ---
    if( !file_exists( $confBaseFile ) )
    {
      echo "<h4>ERROR : " . __CLASS__ . " : Root config file '" . $confBaseFile . "' NOT FOUND.</h4>" ;
      return ;
    }
    $this->rootConfig = include( $confBaseFile ) ;
  }
  
  public    function __destruct()
  {
  }


  // LOGGER
  // ---
  public    function setLogger( $logger )
  {
    $this->Logger = $logger ;
  }

  private   function isSetLogger()
  {
    return !empty( $this->Logger ) ;
  }


  // CONF FILES MANAGEMENT
  // ---

  // Check if a module (as 'tools.logger') has been declared in the root configuration file (==> rootConfig[ 'tool' ][ 'items' ][ 'logger' ])
  public    function isDeclared( $itemsArr )
  {
    if( empty( $this->rootConfig ) ) return false ;
    $_itemsArr = Dict::splitKey( $itemsArr ) ;
    if( sizeof( $_itemsArr ) < 2 )   return false ;
    $_type = $_itemsArr[0] ;
    $_name = $_itemsArr[1] ;
    if( !array_key_exists( $_type, $this->rootConfig ) ) return false ;
    if( !in_array( $_name, $this->rootConfig[ $_type ][ self::TagItems ] ) ) return false ;
    return true ;
  }

  // Load any kind of conf file (entry point)
  protected function _initConf( $itemsArr )
  {
    $_itemsArr = Dict::splitKey( $itemsArr ) ;
    $_one      = array_values( $_itemsArr )[0] ;

    switch( $_one )
    {
    // Load config : 'commons'
    // ---
    case 'commons' :
      $confFile     = self::_getConfFile( $_one ) ;
      $this->config->fromFile( $confFile, $_one ) ;
      break ;

    // Load config : all the others
    // ---
    default :
      $this->loadConf( $_itemsArr ) ;
      break ;
    }
  }

  // Load a classic (declared) configuration file and add it to the config
  public function loadConf( $itemsArr )
  {
    // The first key is the type, the second is the name
    // Ex. 'tools.logger.behavior.dateFormat' => tools and logger
    $_itemsArr = Dict::splitKey( $itemsArr ) ;
    if( sizeof( $_itemsArr ) < 2 ) return ;
    $_type = $_itemsArr[0] ;
    $_name = $_itemsArr[1] ;
    if( !$this->isDeclared( $_itemsArr ) )
    {
      $_err = "ERROR : " . __CLASS__ . " : loadConf : undeclared '" . $_type . '.' . $_name . "'" ;
      throw new \Exception( $_err ) ;
    }

    $curFilename = self::_getConfFilename( $this->rootConfig[ $_type ][ self::TagKey ], $_name ) ;
    $curFile     = self::_getConfFile( $curFilename ) ;
    $this->config->set( $_type . '.' . $_name, include( $curFile ) ) ;
  }

  protected function _getConfFile( $filename )
  {
    $_file = self::_getConfFileAbsPath( $this->rootPath, $filename ) ;
    if( !file_exists( $_file ) )
    {
      $_err = "ERROR : " . __CLASS__ . " : '" . $filename . "' config file '" . $_file . "' NOT FOUND." ;
      throw new \Exception( $_err ) ;
    }
    return $_file ;
  }


  // CONFIGURATIONS ACCESS
  // ---
  public  function isInConfig( $itemsArr )
  {
    return $this->config->exists( $itemsArr ) ;
  }

  // Get an item in the conf
  // If needed, the related configuration file will be loaded
  public function getConf( $arr = null, $verbose = false )
  {
    if( !$this->config->exists( $arr ) ) $this->_initConf( $arr ) ;
    return $this->config->get( $arr ) ;
  }

  // Manually add an item to the config
  public  function addToConf( $confPathArr, $key, $value )
  {
    // Filter inappropriate values
    if( empty( $key ) || $value === null )           return ;
    if( !( is_string( $key ) || is_array( $key ) ) ) return ;
    $this->config->set( Dict::joinKey( $confPathArr ) . '.' . $key, $value ) ;
  }
  

  // GLOBAL TOOLS
  // ---
  public  static function getConfPath( ... $pathParams )
  {
    $res = "" ;
    if( empty( $pathParams ) ) return $res ;
    $res .= $pathParams[0] ;
    for( $i = 1 ; $i < sizeof( $pathParams ) ; $i++ )
    {
      $res .= "." . $pathParams[$i] ;
    }
    return $res ;
  }
  
  private static function _getConfFileAbsPath( $confFilesPath, $filename )
  {
    return $confFilesPath . DIRECTORY_SEPARATOR . $filename . '.php' ;
  }
  private static function _getConfFilename( $key, $item )
  {
    return $key . '_' . $item ;
  }
}
?>
