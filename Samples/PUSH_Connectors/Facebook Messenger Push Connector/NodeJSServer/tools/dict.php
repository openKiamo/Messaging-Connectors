<?php

namespace KiamoConnectorSampleToolsFacebookPush ;

require_once __DIR__ . DIRECTORY_SEPARATOR . "files.php"      ;

use KiamoConnectorSampleToolsFacebookPush\Files ;

use \Exception ;


/***********************************************
  Dicts
  */
class Dict
{
  // INIT
  // ---
  public   function __construct( $arr = null, $name = null )
  {
    $this->name = $name ;
    $this->dict = $arr  ;
    if( $arr === null ) $this->dict = [] ;
  }
  

  // INSTANCE METHODS
  // ---
  public   function get( $key = null, $strict = false )
  {
    if( $key === null ) return $this->dict ;

    $_sk = Dict::splitKey( $key ) ;

    $cur = &$this->dict ;
    foreach( $_sk as $_k )
    {
      if( !is_array( $cur ) || !array_key_exists( $_k, $cur ) )
      {
        if( $strict === true )
        {
          $estr = "get : undefined key '" . Dict::joinKey( $key ) . "'" ;
          if( !empty( $this->name ) ) $estr .= " in dict '" . $this->name . "'" ;
          throw new Exception( $estr ) ;
        }
        return null ;
      }
      $cur = &$cur[ $_k ] ;
    }
    return $cur ;
  }
  
  public   function set( $key = null, $val = null, $strict = false )
  {
    if( $key === null )
    {
      $this->dict = $val ;
      return ;
    }
    
    $_sk = Dict::splitKey( $key ) ;

    $cur = &$this->dict ;
    foreach( $_sk as $_k )
    {
      if( !is_array( $cur ) )
      {
        if( $strict === true )
        {
          $estr = "set : forbidden override in key path '" . Dict::joinKey( $key ) . "'" ;
          if( !empty( $this->name ) ) $estr .= " in dict '" . $this->name . "'" ;
          throw new Exception( $estr ) ;
        }
        $cur = [] ;
      }
      if( !array_key_exists( $_k, $cur ) )
      {
        if( $strict === true )
        {
          $estr = "set : undefined key '" . Dict::joinKey( $key ) . "'" ;
          if( !empty( $this->name ) ) $estr .= " in dict '" . $this->name . "'" ;
          throw new Exception( $estr ) ;
        }
        $cur[ $_k ] = [] ;
      }
      $cur = &$cur[ $_k ] ;
    }
    $cur = $val ;
  }
  
  public   function del( $key = null )
  {
    if( $key === null )
    {
      $this->reset() ;
      return ;
    }
    
    $_sk = Dict::splitKey( $key ) ;

    $prev  = null ;
    $prevk = null ;
    $cur   = &$this->dict ;
    foreach( $_sk as $_k )
    {
      if( !is_array( $cur ) || !array_key_exists( $_k, $cur ) )
      {
        return ;
      }
      $prev  = &$cur        ;
      $prevk = $_k          ;
      $cur   = &$cur[ $_k ] ;
    }
    if( !empty( $prevk ) ) unset( $prev[ $prevk ] ) ;
  }

  public   function exists( $key )
  {
    try
    {
      $this->get( $key, true ) ;
    }
    catch( Exception $e )
    {
      return false ;
    }
    return true ;
  }

  public   function getAllOf( $keyItem = null, $keyStart = null )
  {
    if(  empty( $keyItem ) ) return [] ;
    $startPtr = $this->dict ;
    if( !empty( $keyStart ) )
    {
      $startPtr = $this->get( $keyStart ) ;
    }
    $res = [] ;
    Dict::_static_getAllOf( $startPtr, $keyItem, $res ) ;
    return $res ;
  }

  public   function reset()
  {
    $this->set( null, [], false ) ;
  }
  
  public   function fromFile( $filepath, $atKey = null )
  {
    if( !Files::existsFile( $filepath ) ) return ;
    $this->set( $atKey, include( $filepath ), false ) ;
  }

  public   function json( $pretty = true )
  {
    $_options = 0 ;
    if( $pretty === true ) $_options = JSON_PRETTY_PRINT ;
    return json_encode( $this->dict, $_options ) ;
  }

  public   function hprint( $prefix = '', $postfix = '' )
  {
    Strings::hprint( $this->dict, $prefix, $postfix ) ;
  }

  
  // CLASS METHODS
  // ---
  public   static function splitKey( $key )
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
  public   static function joinKey( $key )
  {
    if( empty( $key ) ) return $key ;

    $res = '' ;
    if( is_array( $key ) )
    {
      $res = implode( '.', $key ) ;
    }
    else
    {
      $res = &$key ;
    }
    
    return $res ;
  }

  // Searching all items of key $keyItem in the input $arr
  // The result will be pushed in the $res provided array (must be provided valid and empty : [])
  /* Example :
     $mydict  = [
       'key1'   => [
         'key11'   => 'forget it',
         'key12'   => [
           'yesme'    => 'Ok',
         ],
       ],
       'yesme'   => [
         'name'    => 'also me',
         'value'   => 'Ok again',
       ],
     ] ;
     $myres   = [] ;
     $ksearch = 'yesme' ;
     Dict::getAllOf( $mydict, $ksearch, $myres ) ;
     ==> myres = [ 'Ok', [ 'name' : 'also me', 'value' : 'Ok again' ]
  */
  public   static function _static_getAllOf( &$arr, $keyItem, &$res )
  {
    $ptrStart = $arr ;
    foreach( $ptrStart as $k => $v )
    {
      if( $k === $keyItem )
      {
        $res[] = $v ;
      }
      else
      {
        if( is_array( $v ) )
        {
          Dict::getAllOf( $v, $keyItem, $res ) ;
        }
      }
    }
  }
}
?>
