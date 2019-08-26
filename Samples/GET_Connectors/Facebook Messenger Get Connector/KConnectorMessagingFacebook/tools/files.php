<?php

namespace KiamoConnectorSampleToolsFacebook ;


/***********************************************
  Files
  */
class Files
{
  const SystemFeeMs = 100 ;

  public static function existsFile( $filepath )
  {
    return file_exists( $filepath ) ;
  }

  public static function srm( $filepath )
  {
    if( file_exists( $filepath ) !== true ) return ;
    unlink( $filepath ) ;
  }

  public static function fileInfos( $filepath, $path = true, $data = false )
  {
    return pathinfo( $filepath ) ;
  }

  public static function folderFiles( $pattern, $flags = 0 )
  {
    return glob( $pattern, $flags ) ;
  }

  public static function zipFile( $filepath, $deleteSource = true )
  {
    if( !file_exists( $filepath ) ) return false ;
    $_fileInfos = pathinfo( $filepath ) ;
    if( $_fileInfos[ 'extension' ] === 'zip' ) return true ;
    $zfpath    = $_fileInfos[ 'dirname' ] . '/' . $_fileInfos[ 'filename' ] . '.zip' ;
    $zip       = new \ZipArchive() ;
    if( $zip->open( $zfpath, \ZipArchive::CREATE ) !== true ) return false ;
    $res = $zip->addFile( $filepath, $_fileInfos[ 'basename' ] ) ;
    $zip->close() ;
    if( $deleteSource === true )
    {
      unlink( $filepath ) ;  // Note : this line can generate a PHP Warning if the file is locked by another system process, as glogg for instance
    }
    return true ;
  }
}
?>
