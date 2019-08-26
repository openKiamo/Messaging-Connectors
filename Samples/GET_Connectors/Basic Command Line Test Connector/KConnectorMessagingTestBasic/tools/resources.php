<?php

namespace KiamoConnectorSampleToolsBasic ;


require_once( __DIR__ . DIRECTORY_SEPARATOR . 'files.php' ) ;

use KiamoConnectorSampleToolsBasic\Files ;


/***********************************************
  Resources
  ---
  Resources capabilities for 'Modules' or 'SubModules' objects
  The purpose of this tool is to manage files in the ./data/<ModuleName>/ folder
  => for this reason, only pass file names ; the data folder path is automatically resolved
  */
class Resources
{
  const DATA_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR ;

  public static function existsFile( $filename )
  {
    return Files::existsFile( Resources::_getDataFolderPath() . $filename ) ;
  }

  public static function srm( $filename )
  {
    Files::srm( Resources::_getDataFolderPath() . $filename ) ;
  }

  public static function fileInfos( $filename, $path = true, $data = false )
  {
    return Files::fileInfos( Resources::_getDataFolderPath() . $filename, $path, $data ) ;
  }

  public static function zipFile( $filename, $deleteSource = true )
  {
    return Files::zipFile( Resources::_getDataFolderPath() . $filename, $deleteSource ) ;
  }

  public static function getDataFolderPath()
  {
    return Resources::_getDataFolderPath() ;
  }
  public static function _getDataFolderPath()
  {
    return Resources::DATA_PATH . Resources::_getModuleName() . DIRECTORY_SEPARATOR ;
  }

  public static function getDefaultFilePath()
  {
    return Resources::_getDataFolderPath() . Resources::_getDefaultFileName() ;
  }
  private static function _getDefaultFileName()
  {
    return Resources::_getModuleName() . '.json' ;
  }

  private static function _getModuleName( $shift = 0 )
  {
    $modName = '' ;
    try { $modName = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 4 - $shift )[ 3 - $shift ][ 'object' ]->getName() ; } catch( \Exception $e ) {}
    return $modName ;
  }

  public static function createDataFolderPath( $pFolderPath = null )
  {
    $folderPath = $pFolderPath ;
    if( $folderPath === null ) $folderPath = Resources::_getDataFolderPath() ;
    if( is_dir( $folderPath ) ) return ;
    mkdir( $folderPath ) ;
  }

  public static function existsDefaultDataFile()
  {
    $folderPath = Resources::_getDataFolderPath()  ;
    if( !is_dir( $folderPath    ) ) return false ;
    $filename   = Resources::_getDefaultFileName() ;
    $filepath   = $folderPath . $filename ;
    if( !file_exists( $filepath ) ) return false ;
    return true ;
  }
  public static function readDefaultDataFile()
  {
    $res = null ;
    $folderPath = Resources::_getDataFolderPath()  ;
    if( !is_dir( $folderPath    ) ) return $res ;
    $filename   = Resources::_getDefaultFileName() ;
    $filepath   = $folderPath . $filename ;
    if( !file_exists( $filepath ) ) return $res ;
    $fileContent = file_get_contents( $filepath ) ;
    $res = json_decode( $fileContent, true ) ;
    if( $res === null ) $res = $fileContent ;
    return $res ;
  }
  public static function writeDefaultDataFile( $content )
  {
    $folderPath = Resources::_getDataFolderPath()  ;
    Resources::createDataFolderPath( $folderPath ) ;
    $filename   = Resources::_getDefaultFileName() ;
    $filepath   = $folderPath . $filename ;
    $_content   = json_encode( $content, JSON_PRETTY_PRINT ) ;
    $res = file_put_contents( $filepath, $_content ) ;
    return $res ;
  }
  public static function deleteDefaultDataFile()
  {
    $folderPath = Resources::_getDataFolderPath()  ;
    if( !is_dir( $folderPath    ) ) return ;
    $filename   = Resources::_getDefaultFileName() ;
    $filepath   = $folderPath . $filename ;
    if( !file_exists( $filepath ) ) return ;
    return Files::srm( $filepath ) ;
  }
}
?>
