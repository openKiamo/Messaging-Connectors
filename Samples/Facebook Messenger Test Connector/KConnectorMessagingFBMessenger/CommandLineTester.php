<?php

namespace UserFiles\Messaging\Connector\KConnectorMessagingFBMessenger ;


require_once __DIR__ . DIRECTORY_SEPARATOR . "MessagingManager.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;


use KiamoConnectorSampleToolsFB\Datetimes ;
use KiamoConnectorSampleToolsFB\Logger    ;
use KiamoConnectorSampleToolsFB\Module    ;
use KiamoConnectorSampleToolsFB\Resources ;
use KiamoConnectorSampleToolsFB\Uuids     ;


class CommandLineTester extends Module
{
  const VerbTest = 'test' ;
  const VerbMsg  = 'msg'  ;
  const VerbOrig = 'orig' ;
  const VerbName = 'name' ;
  const VerbType = 'type' ;
  const VerbMark = 'mark' ;


  public    function __construct( $strictMode = false )
  {
    parent::__construct() ;

    //$this->msgMgr = new MessagingManager() ;    
    $this->defineTestFunctions() ;
    if( $this->setTestId( $strictMode ) ) $this->run() ;
  }

  private  function getLineParam( $name, $default = null )
  {
    $args = getopt( null, [ $name . ":" ] ) ;
    if( !array_key_exists( $name, $args ) ) return $default ;
    return $args[ $name ] ;
  }
  
  private  function defineTestFunctions()
  {
    $this->testFunctions = [] ;

    $this->testFunctions[ 'test00' ] = [
      'purpose'  => 'Void execution',
      'function' => function()
      {
        echo "Do nothing...\n" ;
      } 
    ] ;


    /* Test function pattern
    $this->testFunctions[ 'testXX' ] = [
      'purpose'  => '',
      'function' => function()
      {
      }
    ] ;
    */
  }

  private  function usage()
  {
    echo "\n" ;
    echo "Usage\n" ;
    echo "-----\n" ;
    echo '> php CommandLineTester.php -f --test="<testId>"' . "\n" ;
    echo '  ==> execution du test <testId>.' . "\n" ;
  }

  private  function setTestId( $strict = true )
  {
    $this->testId = -1 ;
    $args   = getopt( null, [ self::VerbTest . ":" ] ) ;
    if( !array_key_exists( self::VerbTest, $args ) )
    {
      if( $strict === true ) $this->usage() ;
      return false ;
    }
    $this->testId           = $args[ self::VerbTest ] ;
    if( strlen( $this->testId ) == 1 ) $this->testId = '0' . $this->testId ;
    $this->testFunctionName = self::VerbTest . $this->testId ;
    if( !array_key_exists( $this->testFunctionName, $this->testFunctions ) )
    {
      if( $strict === true )
      {
        echo "\n" ;
        echo "ERROR : no such test '" . $this->testFunctionName . "'...\n" ;
        echo "==> Exit." ;
        echo "\n" ;
      }
      return false ;
    }
    return true ;
  }

  private  function run()
  {
    echo 'Test #' . $this->testId . " : '" . $this->testFunctions[ $this->testFunctionName ][ 'purpose' ] . "'\n---\n" ;

    call_user_func( $this->testFunctions[ $this->testFunctionName ][ 'function' ] ) ;
  }
}

// Usage example :
// > php CommandLineTester.php -f --test=10
new CommandLineTester( true ) ;
?>
