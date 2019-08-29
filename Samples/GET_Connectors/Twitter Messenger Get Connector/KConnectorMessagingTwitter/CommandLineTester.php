<?php

namespace UserFiles\Messaging\Connector\KConnectorMessagingTwitter ;


// Kiamo Messaging Connector Utilities
// ---
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . "KConnectorMessagingTwitter.php" ;

use UserFiles\Messaging\Connector\KConnectorMessagingTwitter ;
use Kiamo\Bundle\AdminBundle\Utility\Messaging\ConnectorConfiguration    ;


// Messaging Connector Toolkit
// ---
require_once __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR . "core"  . DIRECTORY_SEPARATOR . "MessagingManager.php" ;

use KiamoConnectorSampleToolsTwitter\Datetimes ;
use KiamoConnectorSampleToolsTwitter\Logger    ;
use KiamoConnectorSampleToolsTwitter\Module    ;
use KiamoConnectorSampleToolsTwitter\Resources ;
use KiamoConnectorSampleToolsTwitter\Uuids     ;
use KiamoConnectorSampleToolsTwitter\Webs      ;


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

    $this->connector = new KConnectorMessagingTwitter( new ConnectorConfiguration ) ;
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
      'purpose'  => 'Authent curl',
      'function' => function()
      {
        /*
        POST /oauth2/token HTTP/1.1
        Host: api.twitter.com
        User-Agent: My Twitter App v1.0.23
        Authorization: Basic eHZ6MWV2RlM0d0VFUFRHRUZQSEJvZzpMOHFxOVBaeVJn
                             NmllS0dFS2hab2xHQzB2SldMdzhpRUo4OERSZHlPZw==
        Content-Type: application/x-www-form-urlencoded;charset=UTF-8
        Content-Length: 29
        Accept-Encoding: gzip

        grant_type=client_credentials
        */
        $url  = 'https://api.twitter.com/oauth2/token' ;
        $data = [ 'grant_type' => 'client_credentials' ] ;
        $head = [ 'Content-Type: application/x-www-form-urlencoded;charset=UTF-8' ] ;
        $auth = [
          'httpAuth' => CURLAUTH_BASIC,
          'username' => 'xxxxxxxxxxxxxxxxxxxx',
          'password' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        ] ;
        $res  = Webs::restRequest( $url, $data, $head, $auth ) ;

        echo "RES = " . json_encode( $res ) . "\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test02' ] = [
      'purpose'  => 'Test sendMessage',
      'function' => function()
      {
        $to  = 'xxxxxxxxxxxxxxxxxx' ;
        $msg = 'Hello World !' ;
        $this->connector->_msgManager->sendMessage( $to, $msg ) ;
      }
    ] ;


    $this->testFunctions[ 'test03' ] = [
      'purpose'  => 'Test readMessages',
      'function' => function()
      {
        $lastReadMessageId = null ;
        //$lastReadMessageId = "1143179204227469317" ;
        $res = $this->connector->_msgManager->readMessages( $lastReadMessageId ) ;
        echo "==> Nb new messages   = " . count( $res[ 'newMessages'       ] ) . "\n" ;
        echo "==> lastReadMessageId = " .        $res[ 'lastReadMessageId' ]   . "\n" ;
      }
    ] ;


    $this->testFunctions[ 'test04' ] = [
      'purpose'  => 'get User Data',
      'function' => function()
      {
        $verb       = 'userShow'            ;
        //$paramName  = 'user_id'             ;
        //$paramValue = 'xxxxxxxxxxxxxxxxxx'  ;
        $paramName  = 'screen_name'         ;
        $paramValue = 'xxxxxxxxxxxxx'       ;
        $params     = []                    ;
        $params[ $paramName ] = $paramValue ;
        $callRes = $this->connector->_msgManager->twitterRequest( $verb, $params, null ) ;
        $res                  = [] ;
        $res[ 'id'          ] = $callRes[ 'id'          ] ;
        $res[ 'name'        ] = $callRes[ 'name'        ] ;
        $res[ 'screen_name' ] = $callRes[ 'screen_name' ] ;
        echo "User data = " . json_encode( $res, JSON_PRETTY_PRINT ) . "\n" ; ;
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