<?php

namespace UserFiles\Messaging\Connector\KConnectorMessagingTwitter ;


require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . "KConnectorMessagingTwitter.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR . "MessagingManager.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;


use KiamoConnectorSampleToolsTwitter\Datetimes ;
use KiamoConnectorSampleToolsTwitter\Logger    ;
use KiamoConnectorSampleToolsTwitter\Module    ;
use KiamoConnectorSampleToolsTwitter\Resources ;
use KiamoConnectorSampleToolsTwitter\Uuids     ;
use KiamoConnectorSampleToolsTwitter\Webs      ;

use UserFiles\Messaging\Connector\KConnectorMessagingTwitter ;


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

    $this->connector = new KConnectorMessagingTwitter( null ) ;
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
      'purpose'  => 'build request context',
      'function' => function()
      {
        $verb = 'messageList' ;
        $params = [
          'messageList'  => [
            //'url'           => null,
            'url'          => [
              'cursor'        => 'MTEzOTE5NDgyNTY5MjExMDg1Mg',
            ],
            'body'          => null,
          ],
          'messageShow' => [
            'url'          => [
              'id'         => '1138084226111197192',
            ],
            'body'         => null,
          ],
          'messageNew'   => [
            'url'           => null,
            'body'          =>   '{"event": {"type": "message_create", "message_create": {"target": {"recipient_id": "'
                               . 'xxxxxxxxxxxxxxxxxx'
                               . '"}, "message_data": {"text": "' 
                               . 'Hello SIn 21 !'
                               . '"}}}}',
          ],
          'userShow' => [
            'url'          => [
              //'user_id'       => '844229943160725504',
              'user_id'       => 'xxxxxxxxxxxxxxxxxx',
            ],
            'body'         => null,
          ],
        ] ;
        $callRes = $this->connector->_msgManager->twitterRequest( $verb, $params[ $verb ][ 'url' ], $params[ $verb ][ 'body' ] ) ;
        echo "CallRes = " . json_encode( $callRes, JSON_PRETTY_PRINT ) . "\n" ; ;
      }
    ] ;


    $this->testFunctions[ 'test03' ] = [
      'purpose'  => 'Test sendMessage',
      'function' => function()
      {
        $to  = 'xxxxxxxxxxxxxxxxxx' ;
        $msg = 'Hello world !' ;
        $this->connector->_msgManager->sendMessage( $to, $msg ) ;
      }
    ] ;


    $this->testFunctions[ 'test04' ] = [
      'purpose'  => 'Test readMessages',
      'function' => function()
      {
        $this->connector->_msgManager->readMessages() ;
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
    echo 'Test #' . $this->testId . " : '" . $this->testFunctions[ $this->testFunctionName ][ 'purpose' ] . "'\n---\n" ;

    call_user_func( $this->testFunctions[ $this->testFunctionName ][ 'function' ] ) ;
  }
}

// Usage example :
// > php CommandLineTester.php -f --test=10
new CommandLineTester( true ) ;
?>
