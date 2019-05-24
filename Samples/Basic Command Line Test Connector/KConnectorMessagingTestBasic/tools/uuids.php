<?php

namespace KiamoConnectorSampleTools ;


/***********************************************
  UUIDs
  */
class Uuids
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
?>
