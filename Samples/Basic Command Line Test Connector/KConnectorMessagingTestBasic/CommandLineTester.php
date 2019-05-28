<?php

namespace UserFiles\Messaging\Connector\KConnectorMessagingTestBasic ;


require_once __DIR__ . DIRECTORY_SEPARATOR . "MessagingManager.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;


use KiamoConnectorSampleToolsBasic\Datetimes ;
use KiamoConnectorSampleToolsBasic\Logger    ;
use KiamoConnectorSampleToolsBasic\Module    ;
use KiamoConnectorSampleToolsBasic\Resources ;
use KiamoConnectorSampleToolsBasic\Uuids     ;


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

    $this->msgMgr = new MessagingManager() ;    
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
      'purpose'  => 'Init contact@openKiamo',
      'function' => function()
      {
        $this->msgMgr->initAddress( 'contact@openKiamo' ) ;
      } 
    ] ;


    $this->testFunctions[ 'test02' ] = [
      'purpose'  => 'Create message',
      'function' => function()
      {
        $msgItem = $this->msgMgr->createMessage( "This is a test", "sin@seb.fr", 'contact@openKiamo', 'SIn Seb' ) ;
        $msgStr  = $this->msgMgr->messageDesc( $msgItem ) ;
        echo $msgStr . "\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test03' ] = [
      'purpose'  => 'Send message : user to address',
      'function' => function()
      {
        $msgItem = $this->msgMgr->createMessage( "This is a test", "sin@seb.fr", 'contact@openKiamo', 'SIn Seb' ) ;
        $sendRes = $this->msgMgr->sendMessage( $msgItem ) ;
      } 
    ] ;


    $this->testFunctions[ 'test04' ] = [
      'purpose'  => 'Send message : address owner to user',
      'function' => function()
      {
        $msgItem = $this->msgMgr->createMessage( "Replying to the test", 'contact@openKiamo', "sin@seb.fr", 'Contact Open Kiamo', true ) ;
        $sendRes = $this->msgMgr->sendMessage( $msgItem ) ;
      } 
    ] ;


    $this->testFunctions[ 'test05' ] = [
      'purpose'  => 'Send message : address owner to unknown user',
      'function' => function()
      {
        $msgItem = $this->msgMgr->createMessage( "Writting test", 'contact@openKiamo', "santana@seb.fr", 'Contact Open Kiamo', true ) ;
        $sendRes = $this->msgMgr->sendMessage( $msgItem ) ;
      } 
    ] ;


    $this->testFunctions[ 'test06' ] = [
      'purpose'  => "Read messages : all, all, don't mark them as read",
      'function' => function()
      {
        $messages = $msgItem = $this->msgMgr->readMessages( 'contact@openKiamo', "all", null, false ) ;
        echo json_encode( $messages, JSON_PRETTY_PRINT ) ;
      } 
    ] ;


    $this->testFunctions[ 'test07' ] = [
      'purpose'  => "Read messages : all, unread, don't mark them as read",
      'function' => function()
      {
        $messages = $msgItem = $this->msgMgr->readMessages( 'contact@openKiamo', "unread", null, false ) ;
        echo json_encode( $messages, JSON_PRETTY_PRINT ) ;
      } 
    ] ;


    $this->testFunctions[ 'test08' ] = [
      'purpose'  => "Read messages : all, unread, mark them as read",
      'function' => function()
      {
        $messages = $msgItem = $this->msgMgr->readMessages( 'contact@openKiamo', "unread", null, true ) ;
        echo json_encode( $messages, JSON_PRETTY_PRINT ) ;
      } 
    ] ;


    $this->testFunctions[ 'test09' ] = [
      'purpose'  => "Read messages : santana, all, don't mark them as read",
      'function' => function()
      {
        $messages = $msgItem = $this->msgMgr->readMessages( 'contact@openKiamo', "all", "santana@seb.fr", false ) ;
        echo json_encode( $messages, JSON_PRETTY_PRINT ) ;
      } 
    ] ;


    $this->testFunctions[ 'test10' ] = [
      'purpose'  => "Read messages : sin, unread, mark them as read",
      'function' => function()
      {
        $messages = $msgItem = $this->msgMgr->readMessages( 'contact@openKiamo', "unread", "sin@seb.fr", true ) ;
        echo json_encode( $messages, JSON_PRETTY_PRINT ) ;
      } 
    ] ;



    $this->testFunctions[ 'test11' ] = [
      'purpose'  => "Clean address messages : 'contact@openKiamo', read, all users",
      'function' => function()
      {
        $messages = $msgItem = $this->msgMgr->cleanAddressMessages( "read", 'contact@openKiamo', null ) ;
        echo json_encode( $messages, JSON_PRETTY_PRINT ) ;
      } 
    ] ;


    $this->testFunctions[ 'test12' ] = [
      'purpose'  => "Clean address messages : 'contact@openKiamo', all, santana",
      'function' => function()
      {
        $messages = $msgItem = $this->msgMgr->cleanAddressMessages( "all", 'contact@openKiamo', "santana@seb.fr" ) ;
        echo json_encode( $messages, JSON_PRETTY_PRINT ) ;
      } 
    ] ;


    $this->testFunctions[ 'test13' ] = [
      'purpose'  => "Clean address messages : 'contact@openKiamo', read, sin",
      'function' => function()
      {
        $messages = $msgItem = $this->msgMgr->cleanAddressMessages( "read", 'contact@openKiamo', "sin@seb.fr" ) ;
        echo json_encode( $messages, JSON_PRETTY_PRINT ) ;
      } 
    ] ;


    $this->testFunctions[ 'test14' ] = [
      'purpose'  => "Clean messages : all, read, all users",
      'function' => function()
      {
        $messages = $msgItem = $this->msgMgr->cleanMessages( "read", null, null ) ;
        echo json_encode( $messages, JSON_PRETTY_PRINT ) ;
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
