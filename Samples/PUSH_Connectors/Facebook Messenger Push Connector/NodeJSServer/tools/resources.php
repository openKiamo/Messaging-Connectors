<?php

namespace KiamoConnectorSampleToolsFacebookPush ;


require_once( __DIR__ . DIRECTORY_SEPARATOR . 'files.php' ) ;

use KiamoConnectorSampleToolsFacebookPush\Files ;


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

  public static function existsFile( $name )
  {
    return Files::existsFile( Resources::getDataFolderPath( 1 ) . Resources::_getFileName( $name ) ) ;
  }

  public static function srm( $name )
  {
    Files::srm( Resources::getDataFolderPath( 1 ) . Resources::_getFileName( $name ) ) ;
  }

  public static function fileInfos( $name, $path = true, $data = false )
  {
    return Files::fileInfos( Resources::getDataFolderPath( 1 ) . Resources::_getFileName( $name ), $path, $data ) ;
  }

  public static function zipFile( $name, $deleteSource = true )
  {
    return Files::zipFile( Resources::getDataFolderPath( 1 ) . Resources::_getFileName( $name ), $deleteSource ) ;
  }

  public static function getDataFolderPath( $shift = 0 )
  {
    return Resources::_getDataFolderPath( Resources::_getModuleName( $shift ) ) ;
  }
  public static function _getDataFolderPath( $moduleName )
  {
    return Resources::DATA_PATH . $moduleName . DIRECTORY_SEPARATOR ;
  }

  private static function _getFileName( $name )
  {
    return $name . '.json' ;
  }

  public  static function  getModuleName()
  {
    return Resources::_getModuleName() ;
  }
  private static function _getModuleName( $shift = 0 )
  {
    $modName = '' ;
    try { $modName = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 3 + $shift )[ 2 + $shift ][ 'object' ]->getName() ; } catch( \Exception $e ) {}
    return $modName ;
  }

  public static function createDataFolderPath( $pFolderPath = null )
  {
    $folderPath = $pFolderPath ;
    if( $folderPath === null ) $folderPath = Resources::getDataFolderPath( 1 ) ;
    if( is_dir( $folderPath ) ) return ;
    mkdir( $folderPath ) ;
  }

  public static function existsDataFile( $name, $_moduleName = null )
  {
    $moduleName = empty( $_moduleName ) ? Resources::_getModuleName() : $_moduleName ;
    $folderPath = Resources::_getDataFolderPath( $moduleName )  ;
    if( !is_dir( $folderPath    ) ) return false ;
    $filename   = Resources::_getFileName( $name ) ;
    $filepath   = $folderPath . $filename ;
    if( !file_exists( $filepath ) ) return false ;
    return true ;
  }
  public static function existsDefaultDataFile()
  {
    $moduleName = Resources::_getModuleName() ;
    return Resources::existsDataFile( $moduleName, $moduleName ) ;
  }

  public static function readDataFile( $name, $_moduleName = null )
  {
    $moduleName = empty( $_moduleName ) ? Resources::_getModuleName() : $_moduleName ;
    $res = null ;
    $folderPath = Resources::_getDataFolderPath( $moduleName )  ;
    if( !is_dir( $folderPath    ) ) return $res ;
    $filename   = Resources::_getFileName( $name ) ;
    $filepath   = $folderPath . $filename ;
    if( !file_exists( $filepath ) ) return $res ;
    $fileContent = file_get_contents( $filepath ) ;
    $res = json_decode( $fileContent, true ) ;
    if( $res === null ) $res = $fileContent ;
    return $res ;
  }
  public static function readDefaultDataFile()
  {
    $moduleName = Resources::_getModuleName() ;
    return Resources::readDataFile( $moduleName, $moduleName ) ;
  }

  public static function writeDataFile( $name, $content, $_moduleName = null )
  {
    $moduleName = empty( $_moduleName ) ? Resources::_getModuleName() : $_moduleName ;
    $folderPath = Resources::_getDataFolderPath( $moduleName )  ;
    Resources::createDataFolderPath( $folderPath ) ;
    $filename   = Resources::_getFileName( $name ) ;
    $filepath   = $folderPath . $filename ;
    $_content   = json_encode( $content, JSON_PRETTY_PRINT ) ;
    $res = file_put_contents( $filepath, $_content ) ;
    return $res ;
  }
  public static function writeDefaultDataFile( $content )
  {
    $moduleName = Resources::_getModuleName() ;
    return Resources::writeDataFile( $moduleName, $content, $moduleName ) ;
  }
  
  public static function deleteDataFile( $name, $_moduleName = null )
  {
    $moduleName = empty( $_moduleName ) ? Resources::_getModuleName() : $_moduleName ;
    $folderPath = Resources::_getDataFolderPath( $moduleName )  ;
    if( !is_dir( $folderPath    ) ) return ;
    $filename   = Resources::_getFileName( $name ) ;
    $filepath   = $folderPath . $filename ;
    if( !file_exists( $filepath ) ) return ;
    return Files::srm( $filepath ) ;
  }
  public static function deleteDefaultDataFile()
  {
    $moduleName = Resources::_getModuleName() ;
    return Resources::deleteDataFile( $moduleName, $moduleName ) ;
  }
}
?>
