<?php

namespace UserFiles\Messaging\Connector\KConnectorMessagingFacebookPush ;


// Messaging Connector Toolkit
// ---
require_once __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;

use KiamoConnectorSampleToolsFacebookPush\Datetimes ;
use KiamoConnectorSampleToolsFacebookPush\Logger    ;
use KiamoConnectorSampleToolsFacebookPush\Module    ;
use KiamoConnectorSampleToolsFacebookPush\Resources ;
use KiamoConnectorSampleToolsFacebookPush\Uuids     ;
use KiamoConnectorSampleToolsFacebookPush\Webs      ;


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
      'purpose'  => 'Curl Ping',
      'function' => function()
      {
        $url    = '127.0.0.1:14476/ping' ;
        $data   = null ;
        $header = null ;
        $res = Webs::restRequest( $url, $data, $header ) ;
        echo json_encode( $res, JSON_PRETTY_PRINT ) ;
      } 
    ] ;


    $this->testFunctions[ 'test02' ] = [
      'purpose'  => 'Curl verify webhook',
      'function' => function()
      {
        $url  = 'https://<YOUR URL>/webhook?hub.verify_token=<YOUR VERIFY TOKEN>&hub.challenge=CHALLENGE_ACCEPTED&hub.mode=subscribe' ;
        $header = [ "Content-Type" => "application/json" ] ;
        $res = Webs::restRequest( $url, null, $header ) ;
        echo json_encode( $res, JSON_PRETTY_PRINT ) ;
      }
    ] ;


    /* Example :
    {
      "object": "page",
      "entry": [
        {
          "id": "<ENTRY ID>",
          "time": 1565616845810,
          "messaging": [
            {
              "sender": {
                "id": "<SENDER ID>"
              },
              "recipient": {
                "id": "<RECIPIENT ID>"
              },
              "timestamp": 1565616845583,
              "message": {
                "mid": "<MESSAGE ID>",
                "text": "Some text..."
              }
            }
          ]
        }
      ]
    }
    */
    $this->testFunctions[ 'test03' ] = [
      'purpose'  => 'Curl Json Incoming Webhook Message',
      'function' => function()
      {
        $url  = '127.0.0.1:14476/webhook' ;
        $data = [
          "object"     => "page",
          "entry"      => [
            [
              "id"        => "<AN ID>",
              "time"      => 1565616845810,
              "messaging" => [
                [
                  "message"   => [
                    "mid"        => "<ANOTHER ID>",
                    "text"       => "Your message...",
                  ],
                  "sender"    => [
                    "id"         => "<SENDER ID>"
                  ],
                  "recipient" => [
                    "id"         => "<RECIPIENT ID>"
                  ],
                  "timestamp" => 1565616845583,
                ],
              ],
            ],
          ],
        ] ;
        $header = [ 
          "Content-Type" => "application/json"
        ] ;
        $res  = Webs::restRequest( $url, json_encode( $data ), $header ) ;
        echo json_encode( $res, JSON_PRETTY_PRINT ) ;
      }
    ] ;


    $this->testFunctions[ 'test04' ] = [
      'purpose'  => 'Curl Json getMessages',
      'function' => function()
      {
        $url  = '127.0.0.1:14476/messages?way=inp&sort=false&token=<YOUR VERIFY TOKEN>' ;
        $header = [ "Content-Type" => "application/json" ] ;
        $res  = Webs::restRequest( $url, null, $header ) ;
        echo json_encode( $res, JSON_PRETTY_PRINT ) ;
      }
    ] ;


    $this->testFunctions[ 'test05' ] = [
      'purpose'  => 'Curl Json Send',
      'function' => function()
      {
        $url  = '127.0.0.1:14476/send' ;
        $header = [ 
          "Content-Type" => "application/json"
        ] ;
        $data = [
          "desc" => [
            "_id"   => "5d63d5de168e8",
            "_date" => "20190826_145142",
            "way"   => "out"
          ],
          "emitter" => [
            "extid" => "<YOUR FB ID>",
            "name"  => "<YOUR FB PAGE NAME>"
          ],
          "recipients" => [
            [
              "extid"     => "<YOUR RECIPIENT ID>"
            ]
          ],
          "content" => [
            "txt"      => [
              "txt"       => "20190826 : CTM 01",
              "encoding"  => "utf8"
            ]
          ],
          "token" => "<YOUR VERIFY TOKEN>"
        ] ;

        $res  = Webs::restRequest( $url, json_encode( $data ), $header ) ;
        echo json_encode( $res, JSON_PRETTY_PRINT ) ;
      }
    ] ;

    $this->testFunctions[ 'test06' ] = [
      'purpose'  => 'Curl Json getUserProfile',
      'function' => function()
      {
        $uid  = '<VALID USER ID>' ;
        $url  = '127.0.0.1:14476/user?id=' . $uid . '&token=<YOUR VERIFY TOKEN>' ;
        $header = [ "Content-Type" => "application/json" ] ;
        $res = Webs::restRequest( $url, null, $header ) ;
        echo json_encode( $res, JSON_PRETTY_PRINT ) ;
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
