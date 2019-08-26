<?php

namespace UserFiles\Messaging\Connector\KConnectorPushFacebook ;


// Kiamo Messaging Connector Utilities
// ---
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . "KConnectorPushFacebook.php" ;

use UserFiles\Messaging\Connector\KConnectorPushFacebook ;
use Kiamo\Bundle\AdminBundle\Utility\Messaging\ConnectorConfiguration    ;


// Messaging Connector Toolkit
// ---
require_once __DIR__ . DIRECTORY_SEPARATOR . "MessagingManager.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;

use KiamoConnectorSampleToolsPushFacebook\Datetimes ;
use KiamoConnectorSampleToolsPushFacebook\Logger    ;
use KiamoConnectorSampleToolsPushFacebook\Module    ;
use KiamoConnectorSampleToolsPushFacebook\Resources ;
use KiamoConnectorSampleToolsPushFacebook\Uuids     ;
use KiamoConnectorSampleToolsPushFacebook\Webs      ;


// Command Line Tester
// ---
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

    $this->connector = new KConnectorPushFacebook( new ConnectorConfiguration ) ;
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


    $this->testFunctions[ 'test01' ] = [
      'purpose'  => 'get Messages',
      'function' => function()
      {
        $res = $this->connector->_msgManager->readMessages() ;
        echo "RES = " . json_encode( $res ) . "\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test02' ] = [
      'purpose'  => 'send Message',
      'function' => function()
      {
        $messageData = [
          "to"         => [
            "id"          => '<RECIPIENT ID>',
          ],
          "content"       => '20190826 : CTM 01',
        ] ;
        $this->connector->send( $messageData ) ;
      } 
    ] ;


    /*
    $this->testFunctions[ 'testXX' ] = [
      'purpose'  => 'xxxxxxxxxxxxx',
      'function' => function()
      {
        $ad = $this->connector->getConf( 'accessData' ) ;
        echo "AD = " . json_encode( $ad ) . "\n" ; ;
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
    echo "\nTest #" . $this->testId . " : '" . $this->testFunctions[ $this->testFunctionName ][ 'purpose' ] . "'\n---\n" ;

    call_user_func( $this->testFunctions[ $this->testFunctionName ][ 'function' ] ) ;
  }
}

// Usage example :
// > php CommandLineTester.php -f --test=10
new CommandLineTester( true ) ;
?>
