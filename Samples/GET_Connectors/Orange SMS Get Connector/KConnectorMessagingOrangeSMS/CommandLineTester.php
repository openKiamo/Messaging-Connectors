<?php

namespace UserFiles\Messaging\Connector\KConnectorMessagingOrangeSMS ;


// Kiamo Messaging Connector Utilities
// ---
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . "KConnectorMessagingOrangeSMS.php" ;

use UserFiles\Messaging\Connector\KConnectorMessagingOrangeSMS ;
use Kiamo\Bundle\AdminBundle\Utility\Messaging\ConnectorConfiguration    ;


// Messaging Connector Toolkit
// ---
require_once __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;
require_once __DIR__ . DIRECTORY_SEPARATOR . "core"  . DIRECTORY_SEPARATOR . "MessagingManager.php" ;

use KiamoConnectorSampleToolsOrangeSMS\Datetimes ;
use KiamoConnectorSampleToolsOrangeSMS\Logger    ;
use KiamoConnectorSampleToolsOrangeSMS\Module    ;
use KiamoConnectorSampleToolsOrangeSMS\Resources ;
use KiamoConnectorSampleToolsOrangeSMS\Uuids     ;
use KiamoConnectorSampleToolsOrangeSMS\Webs      ;


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

    $this->connector = new KConnectorMessagingOrangeSMS( new ConnectorConfiguration ) ;
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
      'purpose'  => 'OAuth2 authentication',
      'function' => function()
      {
        $docUrl         = 'https://contact-everyone.orange-business.com/api/docs/guides/index.html?php#0-prise-en-main' ;

        $baseUrl        = 'https://contact-everyone.orange-business.com' ;
        $authentPostfix = '/api/v1.2/oauth/token' ;
        $login          = 'xxxxxxxxxxxxxxxx' ;
        $password       = 'xxxxxxxxxxxx' ;
        $apiLightKey    = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' ;
        
        $authUrl        = $baseUrl . $authentPostfix ;
        $header         = [
          'Content-Type'  => 'application/x-www-form-urlencoded',
          'Accept'        => 'application/json',
        ] ;
        $body           = [
          'username'      => $login,
          'password'      => $password,
        ] ;
        $sendRes = Webs::restRequest( $authUrl, $body, $header ) ;
        echo "RES = \n" . json_encode( $sendRes, JSON_PRETTY_PRINT ) . "\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test02' ] = [
      'purpose'  => 'Orange SMS+ : Data + Read',
      'function' => function()
      {
        $phoneNb  = '33xxxxxxxxx' ;
        $keyword  = 'xxxxxx' ;
        $manager  = 'xxxxxxxxxxxxxxxxx' ;
        $admin    = 'xxxxxxxxxxxxxx' ;
        $group    = 'xxxxxxxxxxx' ;
        $campaign = 'xxxxxx' ;
        
        $docSMSPlus  = 'https://contact-everyone.orange-business.com/api/docs/guides/index.html?php#10-sms' ;
        $docResponse = 'https://contact-everyone.orange-business.com/api/docs/guides/index.html?php#reponse-sms' ;
        
        // Read the received SMS : GET /api/v1.2/smsplus (header 'Authorization: Bearer [Access-Token]')
        $baseUrl        = 'https://contact-everyone.orange-business.com' ;
        $readUrlPostfix = '/api/v1.2/smsplus' ;
        $paramsPostfix  = '' ;
        $paramsPostfix  = '?dateMin=2019-07-04T17:15:44.000+02:00' ;
        $paramsPostfix  = '?dateMin=2019-07-04T15:15:44.000Z' ;        // 2017-04-18T22:00:00.000Z     !!!! Il faut convertir le type 17:15:44.000+02:00 (ISO8601 with timezone) en 15:15:44.000Z (heure zero : RFC3339 Extended UTC)
        $getUrl         = $baseUrl . $readUrlPostfix . $paramsPostfix ;

        $accessToken    = "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" ;
        $header         = [
          'Content-Type'  => 'application/json',
          'Authorization' => 'Bearer ' . $accessToken,
        ] ;
        $header         = 'Authorization: Bearer ' . $accessToken ;
        echo "URL  = " . $getUrl . "\n" ;
        echo "HEAD = \n" . json_encode( $header, JSON_PRETTY_PRINT ) . "\n" ;
        $sendRes = Webs::restRequest( $getUrl, null, $header ) ;
        echo "RES = \n" . json_encode( $sendRes, JSON_PRETTY_PRINT ) . "\n" ;
        
        /*
          + Pagination : Vous pouvez utiliser les paramètres pageNumber et pageSize en query params dans vos requêtes HTTP.
          + get the messages with dateMin parameter
          curl -X GET "https://[SERVER_URL]/api/v1.2/smsplus?body=soldes&dateMax=2017-04-26T23:59:59.999Z&dateMin=2017-04-18T22:00:00.000Z
        */
      } 
    ] ;


    $this->testFunctions[ 'test03' ] = [
      'purpose'  => 'Messaging Mgr orange requester',
      'function' => function()
      {
        $verb   = 'getAccessToken' ;
        $verb   = 'readMessages'   ;
        //$verb   = 'sendMessage'    ;
        $params = [
          'getAccessToken' => null,
          'readMessages'   => [
            'dateMin'         => '2019-07-04T15:15:44.000Z',
            'pageSize'        => $this->connector->_msgManager->messagesLimit,
            'pageNumber'      => '3',
          ],
          'sendMessage'    => [
            'conversationId'  => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
            //'message'         => "20190705 : Réponse 03 :-)",
            'message'         => "20190705 : 04 : Ceci est un essai d'encoding avec des accents : àéèçïôù, dépassant les 78 caractères du coup ça marche pas mais ça teste mon code...",
            'encoding'        => $this->connector->_msgManager->outEncoding,
          ],
        ] ;
        $res  = $this->connector->_msgManager->orangeSMSRequest( $verb, $params[ $verb ] ) ;
        echo "RES : \n" . json_encode( $res, JSON_PRETTY_PRINT ) . "\n" ;
      } 
    ] ;


    $this->testFunctions[ 'test04' ] = [
      'purpose'  => 'Messaging Mgr orange readMessages',
      'function' => function()
      {
        $dateMin = '2019-07-03T13:43:57.000Z' ;
        $res     = $this->connector->_msgManager->readMessages( $dateMin ) ;
        echo "RES : \n" . json_encode( $res, JSON_PRETTY_PRINT ) . "\n" ;
      }
    ] ;


    $this->testFunctions[ 'test05' ] = [
      'purpose'  => 'Messaging Mgr orange sendMessage',
      'function' => function()
      {
        $to      = '+33xxxxxxxxx' ;
        $msg     = "20190709 : 05_01 : Post #1 avec des accents : àéèçïôù" ;
        $res     = $this->connector->_msgManager->sendMessage( $to, $msg ) ;
        echo "RES : \n" . json_encode( $res, JSON_PRETTY_PRINT ) . "\n" ;
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
