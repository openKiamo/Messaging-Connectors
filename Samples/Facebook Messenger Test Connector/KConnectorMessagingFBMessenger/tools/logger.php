<?php

namespace KiamoConnectorSampleToolsFB ;

require_once __DIR__ . DIRECTORY_SEPARATOR . "datetimes.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR .     "files.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR .   "strings.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR .     "uuids.php" ;

use \DateTime, \DateTimeZone ;

use KiamoConnectorSampleToolsFB\Datetimes ;
use KiamoConnectorSampleToolsFB\Files     ;
use KiamoConnectorSampleToolsFB\Strings   ;
use KiamoConnectorSampleToolsFB\Uuids     ;


/***********************************************
  Logger
  */
class Logger
{
  const LOG_VERBOSE                =  1 ;
  const LOG_VERBOZE                =  1 ;
  const LOG_TRACE                  =  2 ;
  const LOG_DEBUG                  =  3 ;
  const LOG_INFOP                  =  4 ;
  const LOG_INFO                   =  5 ;
  const LOG_WARN                   =  6 ;
  const LOG_WARNING                =  6 ;
  const LOG_ERR                    =  7 ;
  const LOG_ERROR                  =  7 ;
  const LOG_CRITICAL               =  8 ;
  
  const ROOT_CONF                  = [
    'path'                            =>     'tools.logger',
    'items'                           => [
      'timezone'                         => [ 'commons.date.timezone'                             ,         'Europe/Paris' ],
      'globalLevel'                      => [ 'tools.logger.behavior.globalLogLevel'              ,              LOG_DEBUG ],
      'dateFormat'                       => [ 'tools.logger.behavior.dateFormat'                  ,            '[Ymd_His]' ],
      'smartMethodName'                  => [ 'tools.logger.behavior.smartMethodName.enabled'     ,                   true ],
      'smartMethodStrict'                => [ 'tools.logger.behavior.smartMethodName.strictLength',                     16 ],
      'smartMethodBorder'                => [ 'tools.logger.behavior.smartMethodName.border'      ,   Strings::BORDER_LEFT ],
      'confFolder'                       => [ 'tools.logger.conf.folder'                          ,   __DIR__ . '/../conf' ],
      'confFile'                         => [ 'tools.logger.conf.file'                            ,             't_logger' ],
      'logFolder'                        => [ 'tools.logger.files.folder'                         ,   __DIR__ . '/../logs' ],
      'obsoleteZip'                      => [ 'tools.logger.files.obsolete.zipOlderThan'          ,                     -1 ],
      'obsoleteDelete'                   => [ 'tools.logger.files.obsolete.deleteOlderThan'       ,                     -1 ],
    ],
  ] ;
  
  const FILE_EXT                   = '.log' ;
  const FILE_DATE_FORMAT           =  'Ymd' ;


  // INIT
  // ---
  public  function __construct( $confManager = null, $_rootPath = null, $appName = null )
  {
    $this->confManager = $confManager ;
    $this->appName     = $appName ;

    // Prepare configuration
    if( !empty( $this->confManager ) && $this->confManager->isDeclared( self::ROOT_CONF[ 'path' ] ) )
    {
      $this->confManager->loadConf( self::ROOT_CONF[ 'path' ] ) ;
      foreach( self::ROOT_CONF[ 'items' ] as $key => $val )
      {
        if( $this->confManager->isInConfig( $val[0] ) )
        {
          $this->{ $key } = $this->confManager->getConf( $val[0] ) ;  // Get in conf
        }
        else
        {
          $this->{ $key } = $val[1] ;  // Default value
        }
      }
    }
    else
    {
      foreach( self::ROOT_CONF[ 'items' ] as $key => $val )
      {
        $this->{ $key } = $val[1] ;  // Default value
      }
    }
    if( !empty( $_rootPath     ) ) $this->logFolder  = $_rootPath ;
    if( !empty( $this->appName ) ) $this->logFolder .= '/' . $this->appName ;
    
    if( !is_dir( $this->logFolder ) )
    {
      $resDir = mkdir( $this->logFolder ) ;
      if( !$resDir )
      {
        $_err = "ERROR : " . __CLASS__ . " : Unable to create folder '" . $this->logFolder . "'." ;
        throw new \Exception( $_err ) ;
      }
    }

    // Complete conf
    $this->dateTimeNow = new DateTime( 'now', new DateTimeZone( $this->timezone ) ) ;
    $this->_actionId   = null ;

    // Set the logger in the conf manager
    if( !empty( $this->confManager ) )
    {
      $this->confManager->setLogger( $this ) ;
    }

    $this->_lastLogDate = null ;

    // Log the configuration
    $this->setActionId() ;
    $_name = '' ;
    if( !empty( $this->appName ) ) $_name = "'" . $this->appName . "'" ;
    $this->log( "INIT " . $_name . " ...", $level = self::LOG_TRACE, __METHOD__ ) ;
    foreach( self::ROOT_CONF[ 'items' ] as $key => $val )
    {
      $this->log( "INIT : key '" . $key . "' = '" . $this->{ $key } . "'", $level = self::LOG_VERBOSE, __METHOD__ ) ;
    }
    $this->clearActionId() ;

    $this->log( 'INIT : OK', $level = self::LOG_TRACE, __METHOD__ ) ;
  }
  
  public  function __destruct()
  {
  }

  protected function _setLogFile()
  {
    $_logFileDate = self::getDateNow( self::FILE_DATE_FORMAT, $this ) ;
    if( ( $this->_lastLogDate === null ) || ( $this->_lastLogDate !== $_logFileDate ) )
    {
      $this->logFile      = implode( [ $this->logFolder, '/', $_logFileDate, self::FILE_EXT ] ) ;
      $this->_lastLogDate = $_logFileDate ;
      if( !file_exists( $this->logFile ) )
      {
        self::manageObsoleteLogFiles( $this ) ;
      }
    }
  }


  // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  
  // CORE FUNCTIONS
  // ---
  
  // log function : both available static and instance, thanks to the magic methods __call and __callStatic (see below)
  // ---
  
  // Instance log
  private        function _instanceLog( $str, $level = self::LOG_DEBUG, $method = '', $actionId = null, $indentLevel = 0 )
  {
    if( $level < self::_getFromConf( 'globalLevel', $this ) ) return ;

    // Prepare the log line
    $methodStr = self::getMethodStr( $method, $this ) ;
    $indentStr = '' ;
    $indentStr = str_pad( $indentStr, $indentLevel * 2 + 1 ) ;
    $now       = self::getDateNow( null, $this ) ;
    $actId     = self::formatActionId( $actionId, $this ) ;
    $resStr    = $now . self::getLogLevelStr( $level ) . $actId . $methodStr . $indentStr . $str . "\r\n" ;

    // Write log (with lock mechanism)
    $this->_setLogFile() ;
    $fp = fopen( $this->logFile, 'a+' ) ;
    if( flock( $fp, LOCK_EX | LOCK_NB ) )
    {
      fseek( $fp, SEEK_END ) ;
      fputs( $fp, $resStr  ) ;
      flock( $fp, LOCK_UN  ) ;
    }
    fclose( $fp ) ;
  }

  // Class log
  private static function _classLog( $str, $level = self::LOG_DEBUG, $method = '', $actionId = null, $indentLevel = 0 )
  {
    $conf = self::_static_getConf( null ) ;
    if( $level < self::_getFromConf( 'globalLevel', null, $conf ) ) return ;

    // Prepare the log line
    $methodStr = self::getMethodStr( $method, null, $conf ) ;
    $indentStr = '' ;
    $indentStr = str_pad( $indentStr, $indentLevel * 2 + 1 ) ;
    $now       = self::getDateNow( null, null, $conf ) ;
    $actId     = self::formatActionId( $actionId, null, $conf ) ;
    $resStr    = $now . self::getLogLevelStr( $level ) . $actId . $methodStr . $indentStr . $str . "\r\n" ;

    // Write log (with lock mechanism)
    $logFile = self::_static_getLogFile( $conf ) ;
    $fp = fopen( $logFile, 'a+' ) ;
    if( flock( $fp, LOCK_EX | LOCK_NB ) )
    {
      fseek( $fp, SEEK_END ) ;
      fputs( $fp, $resStr  ) ;
      flock( $fp, LOCK_UN  ) ;
    }
    fclose( $fp ) ;
  }
  
  // Magic methods __call and __callStatic
  public function __call( $name, $arguments )
  {
    if( $name === 'log' )
    {
      call_user_func_array( array( $this, '_instanceLog' ), $arguments ) ;
    }
    else
    {
      return call_user_func_array( array( $this, $name ), $arguments ) ;
    }
  }
  public static function __callStatic( $name, $arguments )
  {
    if( $name === 'log' )
    {
      call_user_func_array( array( 'self', '_classLog' ), $arguments ) ;
    }
    else
    {
      return call_user_func_array( array( 'self', $name ), $arguments ) ;
    }
  }

  
  // Action Id
  public function getActionId()
  {
    return $this->actionId ;
  }
  public function setActionId( $id = null )
  {
    $_id = $id ;
    if( $id === null ) $_id = Uuids::get() ;
    $this->actionId = $_id ;
  }
  public function clearActionId()
  {
    $this->actionId = null ;
  }


  // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  
  // CORE : STATIC
  // ---
  protected static function manageObsoleteLogFiles( $_this = null, $conf = null )
  {
    $obsoleteZip = self::_getFromConf( 'obsoleteZip'   , $_this, $conf ) ;
    $obsoleteDel = self::_getFromConf( 'obsoleteDelete', $_this, $conf ) ;

    if( ( $obsoleteZip === -1 ) && ( $obsoleteDel === -1 ) ) return ;

    $todayDate = self::getDateNow( self::FILE_DATE_FORMAT, $_this, $conf ) ;

    $logFolder = self::_getFromConf( 'logFolder', $_this, $conf ) ;
    $farr = self::_getLogFiles( $logFolder ) ;
    foreach( $farr as $fpath )
    {
      $filedate = self::_getLogFileDateName( $fpath ) ;
      if( substr( $filedate, 0, 2 ) !== '20' ) continue ;  // Robustness : ignore the files not starting with '20' (the correct date format being like '20181022' )
      $sdelta   = Datetimes::compare( $filedate, $todayDate, self::FILE_DATE_FORMAT ) ;
      if( $sdelta <= 0 ) continue ;
      $ddelta = Datetimes::tsToDays( $sdelta ) ;
      $treated = false ;
      if( ( $obsoleteZip > 0 ) && ( $ddelta > $obsoleteZip ) )
      {
        $treated = true ;
        Files::zipFile( $fpath, true ) ;
        if( empty( $_this ) )
        {
          self::log( "Zipped  obsolete log file '" . $fpath . "'", $level = self::LOG_DEBUG, __METHOD__ ) ;
        }
        else
        {
          $_this->log( "Zipped  obsolete log file '" . $fpath . "'", $level = self::LOG_DEBUG, __METHOD__ ) ;
        }
      }
      if( ( $treated !== true ) && ( $obsoleteDel > 0 ) && ( $ddelta > $obsoleteDel ) )
      {
        $treated = true ;
        Files::srm( $fpath ) ;
        if( empty( $_this ) )
        {
          self::log( "Deleted obsolete log file '" . $fpath . "'", $level = self::LOG_DEBUG, __METHOD__ ) ;
        }
        else
        {
          $_this->log( "Deleted obsolete log file '" . $fpath . "'", $level = self::LOG_DEBUG, __METHOD__ ) ;
        }
      }
    }
  }

  protected static function getDateNow( $format = null, $_this = null, $conf = null )
  {
    $_ft = $format ;
    if( empty( $_ft ) ) $_ft = self::_getFromConf( 'dateFormat', $_this, $conf ) ;
    $_ts = time() ;
    $res = null ;
    if( empty( $_this ) )
    {
      $timezone = self::_getFromConf( 'timezone', $_this, $conf ) ;
      $res      = Datetimes::now( $_ft,  $timezone ) ;
    }
    else
    {
      $_this->dateTimeNow->setTimestamp(  $_ts ) ;
      $res = $_this->dateTimeNow->format( $_ft ) ;
    }
    return $res ;
  }

  protected static function getMethodStr( $method, $_this = null, $conf = null )
  {
    $smartMethodName   = self::_getFromConf( 'smartMethodName'  , $_this, $conf ) ;
    $smartMethodStrict = self::_getFromConf( 'smartMethodStrict', $_this, $conf ) ;
    $smartMethodBorder = self::_getFromConf( 'smartMethodBorder', $_this, $conf ) ;

    if( !$smartMethodName ) return '[' . $method . ']' ;

    $tmpSlashArr = explode( '\\', $method ) ;
    $method      = end( $tmpSlashArr ) ;
    $method      = Strings::adjust( $method, $smartMethodStrict, $smartMethodBorder, ' ' ) ;
    return self::bracket( $method ) ;
  }

  protected static function _getFromConf( $rootkey, $_this = null, $conf = null )
  {
    if( !array_key_exists( $rootkey, self::ROOT_CONF[ 'items' ] ) ) return null ;
    $res = null ;
    $key = self::ROOT_CONF[ 'items' ][ $rootkey ][0] ;
    if( empty( $_this ) )
    {
      $_conf = self::_static_getConf( $conf ) ;
      $key = implode( '.', array_slice( explode( '.', $key ), 2 ) ) ;
      $res = $_conf->get( $key ) ;
    }
    else if( isset( $_this->{ $rootkey } ) )
    {
      $res = $_this->{ $rootkey } ;
    }
    else
    {
      $res = $_this->confManager->getConf( $key ) ;
    }
    return $res ;
  }

  protected static function formatActionId( $actionId, $_this = null, $conf = null )
  {
    $_str = '             ' ;
    if( !empty( $actionId ) )
    {
      $_str = $actionId ;
    }
    else if( !empty( $_this ) && !empty( $_this->actionId ) )
    {
      $_str = $_this->actionId ;
    }
    return self::bracket( $_str ) ;
  }
  

  // TOOLS : STATIC
  // ---
  protected static function _static_getConf( $conf = null )
  {
    if( !empty( $conf ) ) return $conf ;
    $confFile    = self::ROOT_CONF[ 'items' ][ 'confFolder' ][ 1 ] . DIRECTORY_SEPARATOR . self::ROOT_CONF[ 'items' ][ 'confFile' ][ 1 ] . '.php' ;
    $commonsFile = self::ROOT_CONF[ 'items' ][ 'confFolder' ][ 1 ] . DIRECTORY_SEPARATOR . 'commons.php' ;
    $_conf       = new Dict() ;
    if( Files::existsFile( $confFile ) )
    {
      $_conf->fromFile(    $confFile    ) ;
    }
    else  // Fill from default ROOT_CONF
    {
      foreach( self::ROOT_CONF[ 'items' ] as $key => $val )
      {
        if( $key === 'timezone' ) continue ;
        $_key = implode( '.', array_slice( explode( '.', $val[ 0 ] ), 2 ) ) ;
        $_val = $val[ 1 ] ;
        $_conf->set( $_key, $_val ) ;
      }
    }
    
    // Timezone in commons conf
    $commons     = new Dict() ;
    $commons->fromFile( $commonsFile ) ;
    $tz          = $commons->get( 'date.timezone' ) ;
    if( empty( $tz ) ) $tz = self::ROOT_CONF[ 'items' ][ 'timezone' ][ 1 ] ;
    $_conf->set( 'timezone', $tz ) ;
    return $_conf ;
  }

  public   static function _static_getLogFile( $conf = null )
  {
    $_conf        = self::_static_getConf( $conf ) ;
    $_logFileDate = self::getDateNow( self::FILE_DATE_FORMAT, null, $_conf ) ;
    $_logFolder   = self::_getFromConf( 'logFolder', null, $_conf ) ;
    $_logFile     = implode( [ $_logFolder, DIRECTORY_SEPARATOR, $_logFileDate, self::FILE_EXT ] ) ;
    if( !file_exists( $_logFile ) )
    {
      self::manageObsoleteLogFiles( null, $conf ) ;
    }
    return $_logFile ;
  }


  protected static function _getLogFiles( $logFolder )
  {
    return Files::folderFiles( $logFolder . '/' . '*' . self::FILE_EXT ) ;
  }
  protected static function _getLogFileDateName( $filepath )
  {
    return explode( '.', substr( $filepath, -12 ) )[0] ;
  }


  protected static function bracket( $sstr )
  {
    return '[' . $sstr . ']' ;
  }

  protected static function getLogLevelStr( $level )
  {
    switch( $level )
    {
    case self::LOG_VERBOSE :
      return "[VERBZ]" ;
    case self::LOG_TRACE :
      return "[TRACE]" ;
    case self::LOG_DEBUG :
      return "[DEBUG]" ;
    case self::LOG_INFO :
      return "[INFO ]" ;
    case self::LOG_INFOP :
      return "[INFOP]" ;
    case self::LOG_WARN :
    case self::LOG_WARNING :
      return "[WARNG]" ;
    case self::LOG_ERR :
    case self::LOG_ERROR :
      return "[ERROR]" ;
    case self::LOG_CRITICAL :
      return "[CRITK]" ;
    default :
      return "[     ]" ;
    }
  }

  protected static function writeres( $filename, $str )
  {
    $fp = fopen( $filename, 'a+' ) ;
    fputs( $fp, $str ) ;
    fclose( $fp ) ;
  }
}
?>
