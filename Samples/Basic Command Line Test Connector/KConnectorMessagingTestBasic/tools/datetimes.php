<?php

namespace KiamoConnectorSampleTools ;


use \DateTime, \DateTimeZone ;


/***********************************************
  Datetimes
  */
class Datetimes
{
  // Consts
  const DEFAULT_TIMEZONE        =        'Europe/Paris' ;
  const DEFAULT_FORMAT_DATE     =                 'Ymd' ;
  const DEFAULT_FORMAT_DATETIME =             'Ymd_His' ;

  const MN_IN_SECS              =                    60 ;
  const HH_IN_SECS              = self::MN_IN_SECS * 60 ;
  const DD_IN_SECS              = self::HH_IN_SECS * 24 ;
  

  // Current timestamp
  public static function nowTs()
  {
    return time() ;
  }
  public static function nowMs()
  {
    return microtime() ;
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
}
?>
