<?php

namespace UserFiles\Messaging\Connector\KConnectorMessagingFacebook ;

require_once __DIR__ . DIRECTORY_SEPARATOR . "MessagingManager.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;


use KiamoConnectorSampleToolsFacebook\Datetimes ;
use KiamoConnectorSampleToolsFacebook\Logger    ;
use KiamoConnectorSampleToolsFacebook\Module    ;
use KiamoConnectorSampleToolsFacebook\Resources ;
use KiamoConnectorSampleToolsFacebook\Uuids     ;
use KiamoConnectorSampleToolsFacebook\Webs      ;


class ConnectorStub extends Module
{
  public    function __construct( $name )
  {
    parent::__construct( null, null, null, $name ) ;
    $this->_msgManager = new MessagingManager( $this ) ;
  }
}

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

    $this->connector = new ConnectorStub( "KConnectorMessagingFacebook" ) ;
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
      'purpose'  => 'Build FB Request Conversations',
      'function' => function()
      {
        $paramUrl = [
          'fields'  => 'id,updated_time',
          'limit'   => $this->connector->_msgManager->conversationsLimit,
          //'after'   => "QVFIUnJpdDVXRU1nUkFFSUhuR1NISjhrMnF5eC1iM29ST083TVRFTFVTYUdmdE83X05pREtJQXdzd21sLVRKTDRJdXB2Vm9rSmZAwaVc1ZA2laSUcyZAG45STNKTGxrYlJSbVMxbWNtb2h3Nk5ZATThJ",
        ] ;
        $callUrl = $this->connector->_msgManager->buildUrl( $this->connector->_msgManager->pageId . '/conversations', $paramUrl ) ;
        echo 'URL = ' . $callUrl . "\n" ;
        $callRes = Webs::restRequest( $callUrl ) ;
        echo "RES = \n" . json_encode( $callRes, JSON_PRETTY_PRINT ) . "\n" ;
      }
    ] ;


    $this->testFunctions[ 'test02' ] = [
      'purpose'  => 'Build FB Request Messages',
      'function' => function()
      {
        $convId   = 't_xxxxxxxxxxxxxxxxxxx' ;
        $paramUrl = [
          'fields'  => 'id,from,message,created_time',
          'limit'   => $this->connector->_msgManager->messagesLimit,
          //'after'   => "QVFIUnJpdDVXRU1nUkFFSUhuR1NISjhrMnF5eC1iM29ST083TVRFTFVTYUdmdE83X05pREtJQXdzd21sLVRKTDRJdXB2Vm9rSmZAwaVc1ZA2laSUcyZAG45STNKTGxrYlJSbVMxbWNtb2h3Nk5ZATThJ",
        ] ;
        $callUrl = $this->connector->_msgManager->buildUrl( $convId . '/messages', $paramUrl ) ;
        echo 'URL = ' . $callUrl . "\n" ;
        $callRes = Webs::restRequest( $callUrl ) ;
        echo "RES = \n" . json_encode( $callRes, JSON_PRETTY_PRINT ) . "\n" ;
      }
    ] ;


    $this->testFunctions[ 'test03' ] = [
      'purpose'  => 'FB Request Conversations',
      'function' => function()
      {
        $callRes = $this->connector->_msgManager->facebookRequest( 'getConversations' ) ;
        echo "RES = \n" . json_encode( $callRes, JSON_PRETTY_PRINT ) . "\n" ;
      }
    ] ;


    $this->testFunctions[ 'test04' ] = [
      'purpose'  => 'FB Request Messages',
      'function' => function()
      {
        $convId  = 't_xxxxxxxxxxxxxxxxxxx' ;
        $callRes = $this->connector->_msgManager->facebookRequest( 'getMessages', $convId ) ;
        echo "RES = \n" . json_encode( $callRes, JSON_PRETTY_PRINT ) . "\n" ;
      }
    ] ;


    $this->testFunctions[ 'test05' ] = [
      'purpose'  => 'FB Send message',
      'function' => function()
      {
        $to    = 'xxxxxxxxxxxxxxxxxxx' ;
        $msg   = '20190701 : Support Test 01' ;
        $fbMsg = [] ;
        $fbMsg[ "messaging_type" ] = "RESPONSE" ;
        $fbMsg[ "recipient" ] = [] ;
        $fbMsg[ "recipient" ][ "id"   ] = $to ;
        $fbMsg[ "message"   ] = [] ;
        $fbMsg[ "message"   ][ "text" ] = $msg ;
        $sendUrl = $this->connector->_msgManager->buildUrl( $this->connector->_msgManager->pageId . '/messages', null ) ;
        $sendRes = Webs::restRequest( $sendUrl, $fbMsg, [ "Content-Type" => "application/json" ] ) ;
        echo "RES = \n" . json_encode( $sendRes, JSON_PRETTY_PRINT ) . "\n" ;
      }
    ] ;


    $this->testFunctions[ 'test06' ] = [
      'purpose'  => 'FB Read messages',
      'function' => function()
      {
        //$lastReadMessageTs = '1562049055' ;
        $lastReadMessageTs = null ;
        $res = $this->connector->_msgManager->readMessages( $lastReadMessageTs ) ;
        echo "RES = \n" . json_encode( $res, JSON_PRETTY_PRINT ) . "\n" ;
        echo "new = " . count( $res[ 'newMessages'       ] ) . " message(s)\n" ;
        echo "LRM = " .        $res[ 'lastReadMessageTs' ]   . "\n" ;
      }
    ] ;


    $this->testFunctions[ 'test07' ] = [
      'purpose'  => 'FB Send message',
      'function' => function()
      {
        $params = [
          'to'      => 'xxxxxxxxxxxxxxxxxx',
          'message' => '20190701 : Support Test 02',
        ] ;
        $callRes = $this->connector->_msgManager->facebookRequest( 'sendMessage', null, $params ) ;
        echo "RES = \n" . json_encode( $callRes, JSON_PRETTY_PRINT ) . "\n" ;
      }
    ] ;


    $this->testFunctions[ 'test08' ] = [
      'purpose'  => 'Send message',
      'function' => function()
      {
        
        $to      = 'xxxxxxxxxxxxxxxxxxx' ;
        $message = '20190701 : Support Test 03' ;
        $callRes = $this->connector->_msgManager->sendMessage( $to, $message ) ;
        echo "RES = " . $callRes . "\n" ;
      }
    ] ;


    $this->testFunctions[ 'test09' ] = [
      'purpose'  => 'Build FB Request Conversation',
      'function' => function()
      {
        $convId   = 't_xxxxxxxxxxxxxxxxxx' ;
        $paramUrl = [
          'fields'  => 'id,updated_time,message_count,unread_count,participants',
        ] ;
        $callUrl = $this->connector->_msgManager->buildUrl( $convId, $paramUrl ) ;
        echo 'URL = ' . $callUrl . "\n" ;
        $callRes = Webs::restRequest( $callUrl ) ;
        echo "RES = \n" . json_encode( $callRes, JSON_PRETTY_PRINT ) . "\n" ;
      }
    ] ;


    $this->testFunctions[ 'test10' ] = [
      'purpose'  => 'Build FB Request Conversation',
      'function' => function()
      {
        $convId   = 't_xxxxxxxxxxxxxxxxxx' ;
        $callRes = $this->connector->_msgManager->getFacebookConversationRecipientRecord( $convId ) ;
        echo "RES = \n" . json_encode( $callRes, JSON_PRETTY_PRINT ) . "\n" ;
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
// > php CommandLineTester.php -f --test=01
new CommandLineTester( true ) ;
?>
