<?php 

namespace KiamoConnectorSampleToolsBasic ;


class UserData
{
  // Could be Tools
  // ---

  // Could be : Id
  // ---
  public static function couldBeId( $val )
  {
    // At least one character
    if( empty( $val ) ) return false ;
    // Nothing but alphanums
    return empty( preg_replace( '/[0-9A-Za-z]/', '', $val ) ) ;
  }

  // Could be : Phone
  // ---
  public static function couldBePhone( $val )
  {
    // At least one digit
    if( !preg_match( '/\d/', $val ) ) return false ;
    // Nothing but digits and allowed characters
    return empty( preg_replace( '/[0-9()\[\]+-. ]/', '', $val ) ) ;
  }

  // Could be : Email
  // ---
  public static function couldBeEmail( $val )
  {
    // Note : filter_var is not powerful ; only ok for simple emails.
    return !empty( filter_var( $val, FILTER_VALIDATE_EMAIL ) ) ;
  }

  // Could be : Global
  // ---
  public static function couldBe( $type, $val )
  {
    $res = true ;
    switch( $type )
    {
    case 'id' :
      $res = self::couldBeId( $val ) ;
      break ;
    case 'phone'  :
    case 'mobile' :
      $res = self::couldBePhone( $val ) ;
      break ;
    case 'email' :
      $res = self::couldBeEmail( $val ) ;
      break ;
    default :
      break ;
    }
    return $res ;
  }

  // +33655443322 => 655443322
  //   0655443322 => 655443322
  public static function formatTelNumberForSearch( $numTel )
  {
    $numTel = trim( $numTel ) ;
    $numTel = preg_replace( '/[^\d]+/', '', $numTel ) ;  // ne garder que les chiffres
    $numTel = ltrim( $numTel, '0' )                   ;  // retirer les zeros à gauche
    return $numTel;
  }

  public static function  formatTelNumberForDisplay( $numTel )
  {
    $numTel = trim( $numTel ) ;
    $numTel = preg_replace( '/[^\d]+/', '', $numTel ) ;  // ne garder que les chiffres
    if( strlen( $numTel ) == 9 )
    {
      $numTel = '0' . $numTel ;
    }
    return $numTel ;
  }
}
?>