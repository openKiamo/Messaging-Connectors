<?php

namespace KiamoConnectorSampleToolsFacebook ;


/***********************************************
  Strings
  */
class Strings
{
  public static function strStartsWith( $str, $searchStr )
  {
    return $searchStr === "" || strrpos( $str, $searchStr, -strlen( $str ) ) !== false ;
  }
  function strEndsWith( $str, $searchStr )
  {
    return $searchStr === "" || ( ( $temp = strlen( $str ) - strlen( $searchStr ) ) >= 0 && strpos( $str, $searchStr, $temp ) !== false ) ;
  }

  const BORDER_CENTER = 0 ;
  const BORDER_LEFT   = 1 ;
  const BORDER_RIGHT  = 2 ;
  public static function adjust( $str, $size = -1, $border = Strings::BORDER_RIGHT, $fill = ' ' )
  {
    if( ( $size < 0 ) || ( $str === null ) ) return $str ;
    $_len = strlen( $str ) ;
    if( $_len === $size )
    {
      return $str ;
    }
    else if( $_len > $size )
    {
      return substr( $str, 0, $size ) ;
    }
    else
    {
      $_delta = $size - $_len ;
      $_pre   = '' ;
      $_post  = '' ;
      if(      $border === Strings::BORDER_RIGHT )
      {
        $_pre  = str_repeat( $fill, $_delta ) ;
      }
      else if( $border === Strings::BORDER_LEFT  )
      {
        $_post = str_repeat( $fill, $_delta ) ;
      }
      else              // Strings::BORDER_CENTER
      {
        $_pre  = str_repeat( $fill, $_delta / 2 ) ;
        $_post = str_repeat( $fill, $_delta / 2 ) ;
        if( $_delta % 2 > 0 ) $_pre .= $fill ;
      }
      return $_pre . $str . $_post ;
    }
  }

  public static function getJson( $data, $pretty = false )
  {
    $_options = 0 ;
    if( $pretty === true ) $_options = JSON_PRETTY_PRINT ;
    return json_encode( $data, $_options ) ;
  }


  /*
     DISPLAY
  */
  public static function hprint( $data, $prefix = '', $postfix = '' )
  {
    $_str = $data ;
    if( !is_string( $data ) ) $_str = self::getJson( $data ) ;
    //echo '<small>' . $prefix . $_str . $postfix . '</small>' . '<br/>' ;
    echo $prefix . $_str . $postfix . "\r\n" ;
  }

  public static function sprint( $data, $_size = 0 )
  {
    $res = '<pre>' . Strings::json_get( $data ) . '</pre>' ;
    $res = self::bigger( $res, $_size ) ;
    echo $res ;
  }

  public static function bprint( $str )
  {
    echo $str . '<br/>' ;
  }

  // Bigger : min = 0, max = 3
  public static $Bigger_Huge     = 3 ;
  public static $Bigger_Big      = 2 ;
  public static $Bigger_Bold     = 1 ;
  public static $Bigger_Standard = 0 ;
  public static function bigger( $_str, $_size )
  {
    if( ( $_size <= self::$Bigger_Standard ) || ( $_size > self::$Bigger_Huge ) )
      return $_str ;
    $i = self::$Bigger_Huge - $_size + 1 ;
    $b = '<h' .  $i . '>' ;
    $a = '</h' . $i . '>' ;
    return $b . $_str . $a ;
  }

  public static function varDump( $variable )
  {
    ob_start();
    var_dump( variable ) ;
    $string = ob_get_clean() ;
    return $string ;
  }
}
?>
