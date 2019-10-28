<?php
namespace UserFiles\Messaging\Connector ;

define( "CONNECTOR", "KMessagingSampleCommandLine" ) ;


/**/
// Kiamo v6.x : Messaging Utilities
// -----

const COMMANDLINE_SAMPLE_KIAMO_ROOT              = __DIR__ . "/../../../../../" ;
const COMMANDLINE_SAMPLE_KIAMO_MESSAGING_UTILITY = COMMANDLINE_SAMPLE_KIAMO_ROOT . "www/Symfony/src/Kiamo/Bundle/AdminBundle/Utility/Messaging/" ;

require_once COMMANDLINE_SAMPLE_KIAMO_MESSAGING_UTILITY . "ParameterBag.php"              ;
require_once COMMANDLINE_SAMPLE_KIAMO_MESSAGING_UTILITY . "ConnectorConfiguration.php"    ;
require_once COMMANDLINE_SAMPLE_KIAMO_MESSAGING_UTILITY . "GenericConnectorInterface.php" ;

use Kiamo\Bundle\AdminBundle\Utility\Messaging\ParameterBag              ;
use Kiamo\Bundle\AdminBundle\Utility\Messaging\ConnectorConfiguration    ;
use Kiamo\Bundle\AdminBundle\Utility\Messaging\GenericConnectorInterface ;
/**/


/*
// Kiamo v7.x : Messaging Utilities
// -----

const COMMANDLINE_SAMPLE_KIAMO_ROOT              = __DIR__ . "/../../../../../" ;
const COMMANDLINE_SAMPLE_KIAMO_MESSAGING_UTILITY = COMMANDLINE_SAMPLE_KIAMO_ROOT . "www/Symfony/src/Kiamo/Admin/Utility/Messaging/" ;

require_once COMMANDLINE_SAMPLE_KIAMO_MESSAGING_UTILITY . "ParameterBag.php"              ;
require_once COMMANDLINE_SAMPLE_KIAMO_MESSAGING_UTILITY . "ConnectorConfiguration.php"    ;
require_once COMMANDLINE_SAMPLE_KIAMO_MESSAGING_UTILITY . "GenericConnectorInterface.php" ;

use Kiamo\Admin\Utility\Messaging\ParameterBag              ;
use Kiamo\Admin\Utility\Messaging\ConnectorConfiguration    ;
use Kiamo\Admin\Utility\Messaging\GenericConnectorInterface ;
*/


use \DateTime, \DateTimeZone ;


// Kiamo Messaging Connector
// ---
class KMessagingSampleCommandLine implements GenericConnectorInterface
{
  const RootPath = __DIR__ ;

  public function __construct( ConnectorConfiguration $configuration )
  {
    $this->initConfig( $configuration ) ;
    $this->logger = new ClLogger( $this ) ;

    $this->log( "------------------------------------------------------------------------------", ClLogger::LOG_INFOP, __METHOD__ ) ;
    $this->_msgManager = new ClMessagingManager( $this ) ;
    $this->log( "INIT : OK", ClLogger::LOG_INFOP, __METHOD__ ) ;
  }

  /* **************************************************************************
     Connector Configuration
  */
  private function initConfig( $configuration )
  {
    $this->_parameters = $configuration ;

    // Connector's configuration
    // ---
    $this->selfConf    = [
      'service' => 'Command Line Basic Connector',
      'version' => 'sample',
    ] ;

    // Runtime configuration
    // ---
    $this->runtimeConf = [
      'logLevel' => ClLogger::LOG_DEBUG,
      'agent'    => [
        'username'  => 'Agent Smith',
        'uuid'      => '-425364758697'
      ],
    ] ;
  }

  public   function getConf( $confKey, $key = null )
  {
    $conf = null ;
    switch( $confKey )
    {
    case "self" :
      $conf = &$this->selfConf ;
      break ;
    case "runtime" :
      $conf = &$this->runtimeConf ;
      break ;
    }
    return $conf == null ? null : $this->getInDict( $conf, $key ) ;
  }


  /* **************************************************************************
     GLOBAL Connector
  */

  public function getClassName()
  {
    if( !empty( $this->_classname ) ) return $this->_classname ;
    $_array = explode( '\\', get_class( $this ) ) ;
    $this->_classname = end( $_array ) ;
    return $this->_classname ;
  }
  
  public function getName()
  {
    return $this->getConf( "self", "service" ) ;
  }

  public function fetch( $parameterBag )
  {
    $this->log( "Fetching message(s)", ClLogger::LOG_INFO, __METHOD__ ) ;

    $params              = $parameterBag->getParameters() ;
    $lastReadMessageKey  = CONNECTOR . '.lastReadMessageDate' ;
    $lastReadMessageDate = '' ;
    if( array_key_exists( $lastReadMessageKey, $params ) ) $lastReadMessageDate = $params[ $lastReadMessageKey ] ;
    if( !empty( $lastReadMessageDate ) ) $this->log( "==> lastMessageDate=" . $lastReadMessageDate, ClLogger::LOG_DEBUG, __METHOD__ ) ;

    $msgRes             = $this->_msgManager->readMessages( $lastReadMessageDate ) ;  // read all unread user messages from the messaging address
    $msgArr             = $msgRes[ 'newMessages' ] ;
    $this->log( "Fetched " . count( $msgArr ) . " message(s)", ClLogger::LOG_INFO, __METHOD__ ) ;
    if( $lastReadMessageDate !== $msgRes[ 'lastReadMessageDate' ] )
    {
      $this->log( "==> new lastMessageDate=" . $msgRes[ 'lastReadMessageDate' ], ClLogger::LOG_DEBUG, __METHOD__ ) ;
      $parameterBag->setParameter( $lastReadMessageKey, $msgRes[ 'lastReadMessageDate' ] ) ;
    }

    foreach( $msgArr as $msg )
    {
      $this->log( "==> New message : " . json_encode( $msg ), ClLogger::LOG_TRACE, __METHOD__ ) ;
      $inputMsg = [
        'id'         => $msg[ "id"       ],
        //'createdAt'  => $msg[ "date"     ],
        'senderId'   => $msg[ "uuid"     ],
        'senderName' => $msg[ "username" ],
        'content'    => $msg[ "message"  ],
      ] ;

      $this->log( "=> adding message : " . json_encode( $inputMsg ), ClLogger::LOG_INFO, __METHOD__ ) ;
      $parameterBag->addMessage( $inputMsg ) ;
    }
    
    return $parameterBag;
  }

  public function send( array $messageTask )
  {
    $this->log( "Sending message : " . json_encode( $messageTask ), ClLogger::LOG_INFO, __METHOD__ ) ;

    $msg = $messageTask[ "content" ] ;
    $to  = $messageTask[ "to" ][ "id" ] ;

    $this->log( "Sending message to user id '" . $to . "' : '" . $msg . "'", ClLogger::LOG_INFO, __METHOD__ ) ;
    $this->_msgManager->sendMessage( $to, $msg ) ;
  }


  /* **************************************************************************
     Inner Tools
  */

  public   function log( $str, $level = ClLogger::LOG_DEBUG, $method = '', $indentLevel = 0 )
  {
    $this->logger->log( $str, $level, $method, $indentLevel ) ;
  }

  public   function getInDict( $dict, $key = null )
  {
    if( $key === null ) return $dict ;

    $_sk = $this->_splitKey( $key ) ;

    $cur = &$dict ;
    foreach( $_sk as $_k )
    {
      if( !is_array( $cur ) || !array_key_exists( $_k, $cur ) ) return null ;
      $cur = &$cur[ $_k ] ;
    }
    return $cur ;
  }

  private function _splitKey( $key )
  {
    if( empty( $key ) ) return $key ;

    $res = null ;
    if( is_string( $key ) )
    {
      $res = explode( '.', $key ) ;
    }
    else
    {
      $res = &$key ;
    }
    return $res ;
  }
}


/* ****************************************************************************
   Messaging Management
   ---
   Purpose : externalization from the connector of the implementation of the Web Service API authentication, requests, and all the related mechanisms.
*/
class ClMessagingManager
{
  public    function __construct( $_parent )
  {
    $this->_parent = $_parent ;

    $this->log( "Service : " . $this->getConf( 'self', 'service' ), ClLogger::LOG_DEBUG, __METHOD__ ) ;
    $this->log( "Version : " . $this->getConf( 'self', 'version' ), ClLogger::LOG_DEBUG, __METHOD__ ) ;
    
    $this->initRuntimeData() ;
    $this->initResourceFiles() ;
  }

  public   function initRuntimeData()
  {
    $this->agent = $this->getConf( 'runtime', 'agent' ) ;
  }
  
  public   function initResourceFiles()
  {
    if( ClResources::existsDefaultDataFile() ) return ;

    // Main Data
    $this->agent[ 'date' ] = ClDatetimes::now() ;
    $mainPattern = [
      'users'    => [
        'entries'   => [
          $this->agent[ 'username' ] => $this->agent,
        ],
        'idmap'  => [
          $this->agent[ 'uuid' ] => $this->agent[ 'username' ],
        ],
      ],
      'messages' => [],
    ] ;
    ClResources::writeDefaultDataFile( $mainPattern ) ;
  }


  /* -------------------
     Entities management
  */
  public   function buildMessageRecord( $username, $message, $uuid = null )
  {
    $res = [
      'id'       => ClUuids::get(),
      'username' => $username,
      'message'  => $message,
      'uuid'     => $uuid,
      'date'     => ClDatetimes::now(),
    ] ;
    return $res ;
  }

  public   function buildUserRecord( $username )
  {
    $res = [
      'username' => $username,
      'uuid'     => ClUuids::get(),
      'date'     => ClDatetimes::now(),
    ] ;
    return $res ;
  }
  
  public   function getUserName( $userId )
  {
    $data = ClResources::readDefaultDataFile() ;
    if( !array_key_exists( $userId, $data[ 'users' ][ 'idmap' ] ) ) return null ;
    return $data[ 'users' ][ 'idmap' ][ $userId ] ;
  }

  public   function getUserRecord( $username )  // also works with uuid
  {
    $data = ClResources::readDefaultDataFile() ;
    if( array_key_exists( $username, $data[ 'users' ][ 'entries' ] ) ) return $data[ 'users' ][ 'entries' ][ $username ] ;                                 // By name
    if( array_key_exists( $username, $data[ 'users' ][ 'idmap'   ] ) ) return $data[ 'users' ][ 'entries' ][ $data[ 'users' ][ 'idmap' ][ $username ] ] ;  // By Id
    return null ;
  }

  public   function newUserRecord( $username )
  {
    $userRecord = $this->getUserRecord( $username ) ;
    if( $userRecord !== null ) return $userRecord ;
    $userRecord = $this->buildUserRecord( $username ) ;
    $data = ClResources::readDefaultDataFile() ;
    $data[ 'users' ][ 'entries' ][ $username ] = $userRecord ;
    $data[ 'users' ][ 'idmap'   ][ $userRecord[ 'uuid' ] ] = $username ;
    ClResources::writeDefaultDataFile( $data ) ;
    return $userRecord ;
  }

  private  function saveMessage( $username, $messageRecord )
  {
    $userMessages = $this->getUserMessages( $username ) ;
    $userMessages[ $messageRecord[ 'date' ] ] = $messageRecord ;
    $data = ClResources::readDefaultDataFile() ;
    $data[ 'messages' ][ $username ] = $userMessages ;
    ClResources::writeDefaultDataFile( $data ) ;
  }


  /* ---------------------------
     Request methods
  */
  public   function getData()
  {
    return ClResources::readDefaultDataFile() ;
  }
  
  public   function getUserMessages( $username )
  {
    $data = ClResources::readDefaultDataFile() ;
    if( array_key_exists( $username, $data[ 'messages' ] ) ) return $data[ 'messages' ][ $username ] ;
    $data[ 'messages' ][ $username ] = [] ;
    ClResources::writeDefaultDataFile( $data ) ;
    return $data[ 'messages' ][ $username ] ;
  }

  public   function getMessages( $afterDate = null, $ignoreAgent = false )
  {
    $afterTs  = null ;
    $this->log( "==> afterDate=" . $afterDate, ClLogger::LOG_DEBUG, __METHOD__ ) ;
    if( !empty( $afterDate ) ) $afterTs = ClDatetimes::dateToTs( $afterDate ) ;
    $data     = ClResources::readDefaultDataFile() ;
    $messages = $data[ 'messages' ] ;
    $res      = [] ;
    foreach( $messages as $username => $userMessages )
    {
      foreach( $userMessages as $dateKey => $message )
      {
        $this->log( "==> username=" . $username . ", dateKey=" . $dateKey . ", message=" . json_encode( $message ), ClLogger::LOG_DEBUG, __METHOD__ ) ;
        $dateTs = ClDatetimes::dateToTs( $message[ 'date' ] ) ;
        if( ( $afterDate   !== null ) && ( $dateTs <= $afterTs ) ) continue ;
        if( ( $ignoreAgent === true ) && ( $message[ 'username' ] === $this->agent[ 'username' ] ) ) continue ;
        $messageKey = $message[ 'date' ] . '.' . $message[ 'id' ] ;
        $res[ $messageKey ] = $message ;
      }
    }
    ksort( $res ) ;
    return $res ;
  }

  public   function getUsers()
  {
    $data = ClResources::readDefaultDataFile() ;
    return $data[ 'users' ][ 'entries' ] ;
  }
  
  public   function newUserMessage( $username, $message )
  {
    $userRecord   = $this->newUserRecord(      $username ) ;
    $userMessage  = $this->buildMessageRecord( $username, $message, $userRecord[ 'uuid' ] ) ;
    $this->saveMessage( $username, $userMessage ) ;
    return $userMessage ;
  }
  
  public   function newAgentMessage( $username, $message )
  {
    $messageRecord = $this->buildMessageRecord( $this->agent[ 'username' ], $message, $uuid = $this->agent[ 'uuid' ] ) ;
    $this->saveMessage( $username, $messageRecord ) ;
    return $messageRecord ;
  }

  public   function purgeUsers()
  {
    $this->agent[ 'date' ] = ClDatetimes::now() ;
    $users = [
      $this->agent[ 'username' ] => $this->agent,
    ] ;
    $data = ClResources::readDefaultDataFile() ;
    $data[ 'users' ][ 'entries' ]                           = $users ;
    $data[ 'users' ][ 'idmap'   ]                           = [] ;
    $data[ 'users' ][ 'idmap'   ][ $this->agent[ 'uuid' ] ] = $this->agent[ 'username' ] ;
    ClResources::writeDefaultDataFile( $data ) ;
  }
  
  public   function purgeMessages()
  {
    $data = ClResources::readDefaultDataFile() ;
    $data[ 'messages' ] = [] ;
    ClResources::writeDefaultDataFile( $data ) ;
  }
  
  public   function purge()
  {
    $this->purgeUsers()    ;
    $this->purgeMessages() ;
  }


  /* ----------------------
     Connector entry points
  */

  public   function readMessages( $lastReadMessageDate )
  {
    $messages             = $this->getMessages( $lastReadMessageDate, true ) ;
    $_lastReadMessageDate = $lastReadMessageDate ;
    if( count( $messages ) > 0 ) $_lastReadMessageDate = array_values( $messages )[ count( $messages ) - 1][ 'date' ] ;
    $res      = [
      'newMessages'         => $messages,
      'lastReadMessageDate' => $_lastReadMessageDate,
    ] ;
    $this->log( "==> received " . count( $messages ) . " messages after date " . $lastReadMessageDate . ', last message date is ' . $_lastReadMessageDate, ClLogger::LOG_DEBUG, __METHOD__ ) ;
    return $res ;
  }
  
  public   function sendMessage( $to, $message )
  {
    $username = $this->getUserName( $to ) ;
    if( $username === null )
    {
      $this->log( "KO : Unknown userId=" . $to, ClLogger::LOG_DEBUG, __METHOD__ ) ;
      return false ;
    }
    $messageRecord = $this->newAgentMessage( $username, $message ) ;
    $this->log( "==> message sent : " . json_encode( $messageRecord ), ClLogger::LOG_DEBUG, __METHOD__ ) ;

    return true ;
  }


  /* **************************************************************************
     Inner Tools
  */

  public function getClassName()
  {
    return $this->_parent->getClassName() ;
  }
  private  function log( $str, $level = ClLogger::LOG_DEBUG, $method = '', $indentLevel = 0 )
  {
    $this->_parent->logger->log( $str, $level, $method, $indentLevel ) ;
  }
  private  function getConf( $confKey, $key = null )
  {
    return $this->_parent->getConf( $confKey, $key ) ;
  }

  public static function strStartsWith( &$str, &$searchStr, $caseInsensitive = false )
  {
    $_str       = $str ;
    $_searchStr = $searchStr ;
    if( $caseInsensitive === true )
    {
      $_str       = strtolower( $str       ) ;
      $_searchStr = strtolower( $searchStr ) ;
    }
    return $_searchStr === "" || strrpos( $_str, $_searchStr, -strlen( $_str ) ) !== false ;
  }
}


/* ****************************************************************************
   Logger Helper
*/
class ClLogger
{
  const LOG_ALL                    =  0 ;
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
  const LOG_NONE                   =  9 ;

  const LOGS_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR ;
  
  public    function __construct( $_parent )
  {
    $this->_parent = $_parent ;
    $this->initConf() ;
    $this->log( "INIT : OK", self::LOG_TRACE, __METHOD__ ) ;
  }
  
  public    function initConf()
  {
    $this->timezone        = 'Europe/Paris' ;
    $this->maxLogLevel     = $this->_parent->getConf( 'runtime', 'logLevel' ) ;
    $this->adjustMethodLen = 50 ;
    $this->parentClassName = $this->_parent->getClassName() ;
    if( !file_exists( self::LOGS_PATH ) ) mkdir( self::LOGS_PATH ) ;
    $this->logsPath        = self::LOGS_PATH . DIRECTORY_SEPARATOR . $this->parentClassName . DIRECTORY_SEPARATOR ;
    if( !file_exists( $this->logsPath ) ) mkdir( $this->logsPath ) ;
  }

  public   function log( $str, $level = self::LOG_DEBUG, $method = '', $indentLevel = 0 )
  {
    if( $level < $this->maxLogLevel ) return ;

    // Prepare the log line
    $methodStr = $this->_getMethodStr( $method ) ;
    $indentStr = '' ;
    $indentStr = str_pad( $indentStr, $indentLevel * 2 + 1 ) ;
    $now       = $this->getTimeNow() ;
    $resStr    = self::bracket( $now ) . self::getLogLevelStr( $level ) . $methodStr . $indentStr . $str . "\r\n" ;

    // Write log (with lock mechanism)
    $this->_setLogFile() ;
    $fp = fopen( $this->logfile, 'a+' ) ;
    if( flock( $fp, LOCK_EX | LOCK_NB ) )
    {
      fseek( $fp, SEEK_END ) ;
      fputs( $fp, $resStr  ) ;
      flock( $fp, LOCK_UN  ) ;
    }
    fclose( $fp ) ;
  }

  protected function getDateNow()
  {
    return ( new DateTime( 'now', new DateTimeZone( $this->timezone ) ) )->format( 'Ymd' ) ; 
  }
  protected function getTimeNow()
  {
    return ( new DateTime( 'now', new DateTimeZone( $this->timezone ) ) )->format( 'Ymd_His' ) ; 
  }

  private  function _setLogFile()
  {
    $this->logfile = $this->logsPath . $this->getDateNow() . ".log" ;
  }

  protected function _getMethodStr( $method )
  {
    $tmpSlashArr = explode( '\\', $method ) ;
    $method      = end( $tmpSlashArr ) ;
    $method      = $this->_adjustMethod( $method ) ;
    return self::bracket( $method ) ;
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

  protected static function bracket( $sstr )
  {
    return '[' . $sstr . ']' ;
  }

  private  function _adjustMethod( $methodName )
  {
    $_len = strlen( $methodName ) ;
    if( $_len === $this->adjustMethodLen )
    {
      return $methodName ;
    }
    else if( $_len > $this->adjustMethodLen )
    {
      return substr( $methodName, 0, $this->adjustMethodLen ) ;
    }
    else
    {
      $_delta = $this->adjustMethodLen - $_len ;
      $_post  = str_repeat( ' ', $_delta ) ;
      return $methodName . $_post ;
    }
  }
}


/* ****************************************************************************
   Datetimes Helper
*/
class ClDatetimes
{
  // Consts
  const DEFAULT_TIMEZONE        = 'Europe/Paris' ;
  const DEFAULT_FORMAT_DATE     =          'Ymd' ;
  const DEFAULT_FORMAT_DATETIME =      'Ymd_His' ;

  // Current timestamp
  public static function nowTs()
  {
    return time() ;
  }
  public static function nowMs()
  {
    return round( microtime( true ) * 1000 ) ;
  }
  // Now datetime, returned as a string of given format
  public static function now( $format = self::DEFAULT_FORMAT_DATETIME, $timezone = self::DEFAULT_TIMEZONE )
  {
    return ( new DateTime( 'now', new DateTimeZone( $timezone ) ) )->format( $format ) ;
  }

  public static function dateToTs( $date, $format = self::DEFAULT_FORMAT_DATETIME, $timezone = self::DEFAULT_TIMEZONE )
  {
    return DateTime::createFromFormat( $format, $date, new DateTimeZone( $timezone ) )-> getTimestamp() ;
  }
}


/***********************************************
  Resources
  ---
  Resources capabilities for 'Modules' or 'SubModules' objects
  The purpose of this tool is to manage files in the ./data/<ModuleName>/ folder
  => for this reason, only pass file names ; the data folder path is automatically resolved
  */
class ClResources
{
  const DATA_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR ;

  public static function getDataFolderPath( $shift = 0 )
  {
    return self::_getDataFolderPath( self::_getModuleName( $shift ) ) ;
  }
  public static function _getDataFolderPath( $moduleName )
  {
    return self::DATA_PATH . $moduleName . DIRECTORY_SEPARATOR ;
  }

  private static function _getFileName( $name )
  {
    return $name . '.json' ;
  }

  public  static function  getModuleName()
  {
    return self::_getModuleName() ;
  }
  private static function _getModuleName( $shift = 0 )
  {
    $modName = '' ;
    try { $modName = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 3 + $shift )[ 2 + $shift ][ 'object' ]->getClassName() ; } catch( \Exception $e ) {}
    return $modName ;
  }

  public static function createDataFolderPath( $pFolderPath = null )
  {
    $folderPath = $pFolderPath ;
    if( $folderPath === null ) $folderPath = self::getDataFolderPath( 1 ) ;
    if( is_dir( $folderPath ) ) return ;
    mkdir( $folderPath ) ;
  }

  public static function existsDataFile( $name, $_moduleName = null )
  {
    $moduleName = empty( $_moduleName ) ? self::_getModuleName() : $_moduleName ;
    $folderPath = self::_getDataFolderPath( $moduleName )  ;
    if( !is_dir( $folderPath    ) ) return false ;
    $filename   = self::_getFileName( $name ) ;
    $filepath   = $folderPath . $filename ;
    if( !file_exists( $filepath ) ) return false ;
    return true ;
  }
  public static function existsDefaultDataFile()
  {
    $moduleName = self::_getModuleName() ;
    return self::existsDataFile( $moduleName, $moduleName ) ;
  }

  public static function readDataFile( $name, $_moduleName = null )
  {
    $moduleName = empty( $_moduleName ) ? self::_getModuleName() : $_moduleName ;
    $res = null ;
    $folderPath = self::_getDataFolderPath( $moduleName )  ;
    if( !is_dir( $folderPath    ) ) return $res ;
    $filename   = self::_getFileName( $name ) ;
    $filepath   = $folderPath . $filename ;
    if( !file_exists( $filepath ) ) return $res ;
    $fileContent = file_get_contents( $filepath ) ;
    $res = json_decode( $fileContent, true ) ;
    if( $res === null ) $res = $fileContent ;
    return $res ;
  }
  public static function readDefaultDataFile()
  {
    $moduleName = self::_getModuleName() ;
    return self::readDataFile( $moduleName, $moduleName ) ;
  }

  public static function writeDataFile( $name, $content, $_moduleName = null )
  {
    $moduleName = empty( $_moduleName ) ? self::_getModuleName() : $_moduleName ;
    $folderPath = self::_getDataFolderPath( $moduleName )  ;
    self::createDataFolderPath( $folderPath ) ;
    $filename   = self::_getFileName( $name ) ;
    $filepath   = $folderPath . $filename ;
    $_content   = json_encode( $content, JSON_PRETTY_PRINT ) ;
    $res = file_put_contents( $filepath, $_content ) ;
    return $res ;
  }
  public static function writeDefaultDataFile( $content )
  {
    $moduleName = self::_getModuleName() ;
    return self::writeDataFile( $moduleName, $content, $moduleName ) ;
  }
}


/* ****************************************************************************
   UUID Generator Helper
*/
class ClUuids
{
  const DEFAULT_SIZE = 32 ;
  const ALPHANUMS    = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789" ;

  public static function get( $strong = false, $length = self::DEFAULT_SIZE )
  {
    if( $strong !== true ) return uniqid() ;

    $res = '' ;
    $max = strlen( self::ALPHANUMS ) ;

    for( $i = 0 ; $i < $length ; $i++ )
    {
      $res .= self::ALPHANUMS[ self::realrand( 0, $max - 1 ) ] ;
    }

    return $res;
  }

  public static function realrand( $min, $max )
  {
    $range = $max - $min ;
    if( $range < 1 ) return $min ;
    $log    = ceil( log( $range, 2 ) ) ;
    $bytes  = (int)( $log / 8 ) + 1 ;
    $bits   = (int)$log + 1 ;
    $filter = (int)( 1 << $bits ) - 1 ;
    do
    {
      $rnd = hexdec( bin2hex( openssl_random_pseudo_bytes( $bytes ) ) ) ;
      $rnd = $rnd & $filter ;
    }
    while( $rnd > $range ) ;
    return $min + $rnd ;
  }
}


/* ****************************************************************************
   Command Line Tester
*/
class ClCommandLineTester
{
  const VerbTest          = 'test'    ;

  const VerbGetMessages   = 'get'     ;
  const VerbPurgeMessages = 'purge'   ;
  const OptLimitDate      = 'date'    ;

  const VerbAgentMessage  = 'agent'   ;
  const VerbUserMessage   = 'user'    ;
  const OptUsername       = 'name'    ;
  const OptUserId         = 'id'      ;
  const OptMessage        = 'message' ;



  public    function __construct()
  {
    $connectorClass = "UserFiles\\Messaging\\Connector\\" . CONNECTOR ;
    $this->connector = new $connectorClass( new ConnectorConfiguration ) ;

    // Run
    $this->defineRunFunctions() ;
    if( $this->checkRunVerbs() )
    {
      $this->run() ;
      return ;
    }

    // Tests
    $this->defineTestFunctions() ;
    if( $this->setTestId() )
    {
      $this->runTest() ;
    }
  }

  public function getClassName()
  {
    return $this->connector->getClassName() ;
  }


  // RUN Area
  // ---
  
  private  function checkRunVerbs()
  {
    $runVerbsArr    = [ self::VerbGetMessages, self::VerbPurgeMessages, self::VerbAgentMessage, self::VerbUserMessage ] ;
    $runFuncArgsArr = [ self::OptLimitDate, self::OptUsername, self::OptUserId, self::OptMessage ] ;
    $runAllArgsArr  = [] ;
    foreach( $runVerbsArr    as $runVerb ) $runAllArgsArr[] = $runVerb      ;
    foreach( $runFuncArgsArr as $runOpt  ) $runAllArgsArr[] = $runOpt . ':' ;

    $args = getopt( null, $runAllArgsArr ) ;

    foreach( $runVerbsArr as $runVerb )
    {
      if( array_key_exists( $runVerb, $args ) )
      {
        // Set run Verb
        $this->runFunctionName = $runVerb ;
        
        // Set run Args
        $this->runFunctionArgs = [] ;
        foreach( $runFuncArgsArr as $key )
        {
          $this->runFunctionArgs[ $key ] = null ;
          if( array_key_exists( $key, $args ) ) $this->runFunctionArgs[ $key ] = $args[ $key ] ;
        }
        return true ;
      }
    }
    return false ;
  }
    

  // RUN Functions
  // ---
  private  function defineRunFunctions()
  {
    $this->runFunctions = [] ;

    $this->runFunctions[ 'get' ] = [
      'purpose'  => 'Get Messages',
      'function' => function()
      {
        // limitDate format : YYYYMMDD_hhmmss ; Ex. : 20191025_163424
        $limitDate = $this->runFunctionArgs[ self::OptLimitDate ] ;
        echo "Limit date : '" . $limitDate . "'\n" ;
        $pb = new ParameterBag( CONNECTOR ) ;
        $pb->setParameter( CONNECTOR . '.lastReadMessageDate', $limitDate ) ;
        $pbMessages  = $this->connector->fetch( $pb ) ;
        echo "Messages :\n" . json_encode( $pbMessages->getMessages(), JSON_PRETTY_PRINT ) . "\n" ;
      } 
    ] ;
    

    $this->runFunctions[ 'user' ] = [
      'purpose'  => 'User sends a message',
      'function' => function()
      {
        $username     = $this->runFunctionArgs[ self::OptUsername ] ;
        $message      = $this->runFunctionArgs[ self::OptMessage ] ;
        if( empty( $username ) || empty( $message ) )
        {
          echo "KO : empty user '" . $username . "' or messages '" . $message . "'  ==> do nothing" . "\n" ;
          return ;
        }
        $this->connector->_msgManager->newUserMessage( $username, $message ) ;
        echo "Customer message : from user '" . $username . "', message '" . $message . "'" . "\n" ;
      } 
    ] ;


    $this->runFunctions[ 'agent' ] = [
      'purpose'  => 'Agent replies to a customer',
      'function' => function()
      {
        $_userid = $this->runFunctionArgs[ self::OptUsername ] ;
        if( empty( $_userid ) ) $_userid = $this->runFunctionArgs[ self::OptUserId ] ;
        $userRecord = $this->connector->_msgManager->getUserRecord( $_userid ) ;
        if( empty( $userRecord ) )
        {
          echo "KO : no target customer provided ==> do nothing" . "\n" ;
          return ;
        }
        $username     = $userRecord[ 'username' ] ;
        $userId       = $userRecord[ 'uuid'     ] ;
        $message      = $this->runFunctionArgs[ self::OptMessage ] ;
        if( empty( $message ) )
        {
          echo "KO : no message to send ==> do nothing" . "\n" ;
          return ;
        }
        $messageTask  = [
          'content'     => $message,
          'to'          => [
            'name'         => $username,
            'id'           => $userId,
          ],
        ] ;

        $this->connector->send( $messageTask ) ;
        echo "Agent message : to user '" . $username . "', id='"  . $userId . "' => message '" . $message . "'\n" ;
      }
    ] ;


    $this->runFunctions[ 'purge' ] = [
      'purpose'  => 'Purge messages',
      'function' => function()
      {
        $this->connector->_msgManager->purge() ;
        echo "Purge : DONE" . "\n" ;
      } 
    ] ;
  } 


  private  function run()
  {
    echo "\nRUN '" . $this->runFunctionName . "'\n---\n" ;
    call_user_func( $this->runFunctions[ $this->runFunctionName ][ 'function' ] ) ;
  }


  // TESTS Area
  // ---

  private  function setTestId()
  {
    $this->testId = -1 ;
    
    // Tests
    $args   = getopt( null, [ self::VerbTest . ":" ] ) ;
    if( !array_key_exists( self::VerbTest, $args ) )
    {
      $this->usage() ;
      return false ;
    }
    $this->testId           = $args[ self::VerbTest ] ;
    if( strlen( $this->testId ) == 1 ) $this->testId = '0' . $this->testId ;
    $this->testFunctionName = self::VerbTest . $this->testId ;
    if( !array_key_exists( $this->testFunctionName, $this->testFunctions ) )
    {
      echo "\n" ;
      echo "ERROR : no such test '" . $this->testFunctionName . "'...\n" ;
      echo "==> Exit." ;
      echo "\n" ;
      return false ;
    }
    return true ;
  }


  private  function usage()
  {
    echo "\n" ;
    echo "Usage\n" ;
    echo "-----\n" ;
    echo '> php <ConnectorName>.php --<verb> [--option1=xxx --option2=yyy ...]' . "\n" ;
    echo '  where verbs are :' . "\n" ;
    echo '  --get [--date=YYYYMMDD_hhmmss]' . "\n" ;
    echo '    ==> get all messages [not older than given date],' . "\n" ;
    echo '  --agent --name=<username> --message="<The agent message>"' . "\n" ;
    echo '    ==> simulates an agent reply to a given user (the customer must start the conversation first),' . "\n" ;
    echo '  --user --name=<username> --message="<The customer message>"' . "\n" ;
    echo '    ==> simulates a customer message to the agent (the page),' . "\n" ;
    echo '  --purge' . "\n" ;
    echo '    ==> purge all messages and the related customers (local file purge, not in Kiamo),' . "\n" ;
    echo '  --test=<ID>' . "\n" ;
    echo '    ==> execute the test <ID>, where <ID> is between 00 and 99.' . "\n" ;
  }

  private  function runTest()
  {
    echo "\nTest #" . $this->testId . " : '" . $this->testFunctions[ $this->testFunctionName ][ 'purpose' ] . "'\n---\n" ;
    call_user_func( $this->testFunctions[ $this->testFunctionName ][ 'function' ] ) ;
  }


  // Test Functions
  // ---
  private  function defineTestFunctions()
  {
    $this->testFunctions = [] ;

    $this->testFunctions[ 'test00' ] = [
      'purpose'  => 'Void execution',
      'function' => function()
      {
        echo "Do nothing...\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test01' ] = [
      'purpose'  => 'getUserMessages',
      'function' => function()
      {
        $username     = 'Jean' ;
        $userMessages = $this->connector->_msgManager->getUserMessages( $username ) ;
        echo "User '" . $username . "'Messages :\n" . json_encode( $userMessages, JSON_PRETTY_PRINT ) . "\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test11' ] = [
      'purpose'  => 'Jean sends a message',
      'function' => function()
      {
        $username     = 'Jean' ;
        $message      = 'Hello world !' ;
        $this->connector->_msgManager->newUserMessage( $username, $message ) ;
        $userMessages = $this->connector->_msgManager->getUserMessages( $username ) ;
        echo "User '" . $username . "'Messages :\n" . json_encode( $userMessages, JSON_PRETTY_PRINT ) . "\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test21' ] = [
      'purpose'  => 'Agent replies a Jean',
      'function' => function()
      {
        $username     = 'Jean' ;
        $message      = 'Hello ' . $username . ' !' ;
        $this->connector->_msgManager->newAgentMessage( $username, $message ) ;
        $userMessages = $this->connector->_msgManager->getUserMessages( $username ) ;
        echo "User '" . $username . "'Messages :\n" . json_encode( $userMessages, JSON_PRETTY_PRINT ) . "\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test22' ] = [
      'purpose'  => 'Agent replies a Jean (connector entry point)',
      'function' => function()
      {
        $username     = 'Jean' ;
        $message      = 'Hello ' . $username . ' !' ;
        $this->connector->_msgManager->sendMessage( $username, $message ) ;
        echo "User '" . $username . "' => message '" . $message . "'\n" ;
      }
    ] ;


    $this->testFunctions[ 'test23' ] = [
      'purpose'  => 'Agent replies a Jean (connector)',
      'function' => function()
      {
        $username     = 'Jean' ;
        $userId       = '5db307f0e2dfd' ;
        $message      = 'Hello ' . $username . ' !' ;
        $messageTask  = [
          'content'     => $message,
          'to'          => [
            'name'         => $username,
            'id'           => $userId,
          ],
        ] ;

        $this->connector->send( $messageTask ) ;
        echo "User '" . $username . "' => message '" . $message . "'\n" ;
      }
    ] ;


    $this->testFunctions[ 'test31' ] = [
      'purpose'  => 'Get Messages',
      'function' => function()
      {
        $afterDate = null ;
        $afterDate = '20191025_163424' ;
        $messages  = $this->connector->_msgManager->getMessages( $afterDate ) ;
        echo "Messages :\n" . json_encode( $messages, JSON_PRETTY_PRINT ) . "\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test32' ] = [
      'purpose'  => 'Get Messages (connector entry point)',
      'function' => function()
      {
        $afterDate = null ;
        $afterDate = '20191025_163424' ;
        $messages  = $this->connector->_msgManager->readMessages( $afterDate ) ;
        echo "Messages :\n" . json_encode( $messages, JSON_PRETTY_PRINT ) . "\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test33' ] = [
      'purpose'  => 'Get Messages (connector)',
      'function' => function()
      {
        $afterDate = null ;
        $afterDate = '20191025_163424' ;
        $pb = new ParameterBag( CONNECTOR ) ;
        $pb->setParameter( CONNECTOR . '.lastReadMessageDate', $afterDate ) ;
        $pbMessages  = $this->connector->fetch( $pb ) ;
        echo "Messages :\n" . json_encode( $pbMessages->getMessages(), JSON_PRETTY_PRINT ) . "\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test41' ] = [
      'purpose'  => 'Purge',
      'function' => function()
      {
        $this->connector->_msgManager->purge() ;
        echo "Purge : DONE" . "\n" ;
      } 
    ] ;


    /*
    $this->testFunctions[ 'testXX' ] = [
      'purpose'  => 'xxxxxxxxxxxxx',
      'function' => function()
      {
        echo "Test XX" . "\n" ; ;
      } 
    ] ;
    */
  }
}


// Enable command line test if ran by a command shell
if( php_sapi_name() == 'cli' )
{
  // Usage example :
  // > php <ConnectorName>.php -f --test=00
  new ClCommandLineTester() ;
}
?>