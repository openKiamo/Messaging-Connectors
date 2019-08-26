<?php
spl_autoload_register( function( $class )
{
  if( strpos( $class, 'KiamoConnectorSampleToolsPushFacebook' ) === false ) return ;
  $_classArr = explode( '\\', $class ) ;
  $_class    = end( $_classArr ) ;
  include __DIR__ . DIRECTORY_SEPARATOR . strtolower( $_class ) . '.php';
} ) ;
?>
