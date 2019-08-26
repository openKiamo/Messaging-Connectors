<?php

namespace KiamoConnectorSampleToolsPushFacebook ;


use \DateTime, \DateTimeZone ;


/***********************************************
  Datetimes
  */
class Datetimes
{
  // Consts
  const DEFAULT_TIMEZONE           =        'Europe/Paris' ;
  const DEFAULT_FORMAT_DATE        =                 'Ymd' ;
  const DEFAULT_FORMAT_DATETIME    =             'Ymd_His' ;
  
  const DEFAULT_RFC2822_DATEFORMAT = 'Y-m-d\TH:i:s\+0000' ;

  const MN_IN_SECS                 =                    60 ;
  const HH_IN_SECS                 = self::MN_IN_SECS * 60 ;
  const DD_IN_SECS                 = self::HH_IN_SECS * 24 ;
  

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

  // Today date, returned as a string
  public static function today( $timezone = self::DEFAULT_TIMEZONE )
  {
    return self::now( self::DEFAULT_FORMAT_DATE, $timezone ) ;
  }

  // 'YYYYMMDD_hhmmss' => 'YYYYMMDD'
  public static function datetimeToDate( $datetime )
  {
    return substr( $datetime, 0, 8 ) ;
  }
  // 'YYYYMMDD' => 'YYYYMMDD_hhmmss'
  public static function dateToDatetime( $date )
  {
    return $date . '_000000' ;
  }

  // Timestamp to date as string
  public static function tsToDate( $ts, $format = self::DEFAULT_FORMAT_DATETIME, $timezone = self::DEFAULT_TIMEZONE )
  {
    $res = new DateTime( 'now', new DateTimeZone( $timezone ) ) ;
    $res->setTimestamp( $ts ) ;
    return $res->format( $format ) ;
  }
  // Date as string to timestamp
  public static function datetimeToTs( $datetime, $format = self::DEFAULT_FORMAT_DATETIME, $timezone = self::DEFAULT_TIMEZONE )
  {
    return DateTime::createFromFormat( $format, $datetime, new DateTimeZone( $timezone ) )-> getTimestamp() ;
  }
  public static function dateToTs(     $date    , $format = self::DEFAULT_FORMAT_DATE    , $timezone = self::DEFAULT_TIMEZONE )
  {
    return DateTime::createFromFormat( $format, $date    , new DateTimeZone( $timezone ) )-> getTimestamp() ;
  }

  // Millis to date as string
  public static function msToDate( $ms, $format = self::DEFAULT_FORMAT_DATETIME, $timezone = self::DEFAULT_TIMEZONE )
  {
    return self::tsToDate( self::msToTs( $ms ), $format, $timezone ) ;
  }


  // <  0 : $date1 >  $date2
  // == 0 : $date1 == $date2
  // >  0 : $date1 <  $date2
  // Returns the delta seconds
  public static function compare( $date1, $date2, $format = self::DEFAULT_FORMAT_DATETIME )
  {
    $ts1 = self::dateToTs( $date1, $format ) ;
    $ts2 = self::dateToTs( $date2, $format ) ;
    return $ts2 - $ts1 ;
  }
  
  // Timestamp to/from Millis
  public static function msToTs( $ms )
  {
    return intval( $ms / 1000 ) ;
  }
  public static function tsToMs( $ts )
  {
    return $ts * 1000 ;
  }
  
  public static function tsToDays( $ts )
  {
    return intval( $ts / self::DD_IN_SECS ) ;
  }
  
  public static function datetimeToSecs( $dateStr, $format = self::DEFAULT_FORMAT_DATETIME )
  {
    return \DateTime::createFromFormat( $format, $dateStr )->format( 'U' ) ;
  }

  public static function deltaDatesInSecs( $dateStr1, $dateStr2, $format = self::DEFAULT_FORMAT_DATETIME )
  {
    return self::datetimeToSecs( $dateStr2, $format ) - self::datetimeToSecs( $dateStr1, $format ) ;
  }
  
  public static function getRFC2822FromTimestamp( $ts )
  {
    $_ts = $ts ;
    if( is_integer( $_ts )                        ) $_ts = strval( $_ts        ) ;
    if( is_string(  $_ts ) && strlen( $_ts ) > 10 ) $_ts = substr( $_ts, 0, 10 ) ;
    $sdate = new \DateTime() ;
    $sdate->setTimestamp( $_ts ) ;
    return $sdate->format( \DateTime::RFC2822 ) ;
  }

  public static function getRFC2822Date( $dateStr, $dateFormat = Datetimes::DEFAULT_RFC2822_DATEFORMAT )
  {
    return \DateTime::createFromFormat( $dateFormat, $dateStr )->format( \DateTime::RFC2822 ) ;
  }

  public static function getRFC2822Timezone()
  {
    return explode( ' ', ( new \DateTime( 'NOW' ) )->format( \DateTime::RFC2822 ) )[5] ;
  }

  // mode in [ null|raw|timezoned ], where null => gmdate, raw => without timezone, timezoned => current timezone
  public static function getRFC2822Now( $mode = null )
  {
    $res = gmdate( \DateTime::RFC2822 ) ;
    switch( $mode )
    {
    case "raw" :
      $res = implode( ' ', explode( ' ', $res, -1 ) ) ;
      break ;
    case "timezoned" :
      $res  = implode( ' ', explode( ' ', $res, -1 ) ) ;
      $ctz  = self::getRFC2822Timezone() ;
      $res .= ' ' . $ctz ;
      break ;
    default :
      break ;
    }
    return $res ;
  }
}
?>
