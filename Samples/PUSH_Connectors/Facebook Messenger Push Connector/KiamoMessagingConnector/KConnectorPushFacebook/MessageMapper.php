<?php

namespace UserFiles\Messaging\Connector\KConnectorPushFacebook ;


require_once __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "autoload.php" ;


use KiamoConnectorSampleToolsPushFacebook\Datetimes ;
use KiamoConnectorSampleToolsPushFacebook\Logger    ;
use KiamoConnectorSampleToolsPushFacebook\SubModule ;
use KiamoConnectorSampleToolsPushFacebook\Uuids     ;


class MessageMapper extends SubModule
{
  const WAY_INP = 'inp' ;
  const WAY_OUT = 'out' ;

  public    function __construct( &$_parent )
  {
    parent::__construct( $_parent, get_class( $_parent ) ) ;

    $this->selfName = $this->getConf( "identity.name" ) ;
    $this->selfId   = $this->getConf( "identity.id"   ) ;

    $this->log( "INIT : OK", Logger::LOG_INFO, __METHOD__ ) ;
  }

  /* Example : 201908 : v1.0
      {
        "desc": {
          "_id": null,
          "extid": null,
          "type": null,
          "_date": null,
          "extdate": null,
          "way": null,
          "data": null
        },
        "emitter": {
          "_id": null,
          "extid": null,
          "name": null,
          "key": null,
          "data": null
        },
        "recipients": [
          {
            "_id": null,
            "extid": null,
            "name": null,
            "key": null,
            "data": null
          }
        ],
        "content": {
          "txt": {
            "txt": null,
            "encoding": null,
            "data": null
          },
          "attachments": null
        }
      }
  */
  public   function mapMessage( $messageData, $way )
  {
    $res = [] ;
    if(      $way === self::WAY_INP )
    {
      $res[ 'id'         ] = $messageData[ "desc"    ][ "_id"     ]          ;
      $res[ 'createdAt'  ] = Datetimes::getRFC2822FromTimestamp( $messageData[ "desc" ][ "extdate" ] ) ;
      $res[ 'senderId'   ] = $messageData[ "emitter" ][ "extid"   ]          ;
      $res[ 'senderName' ] = $messageData[ "emitter" ][ "name"    ]          ;
      $res[ 'content'    ] = $messageData[ "content" ][ "txt"     ][ "txt" ] ;
    }
    else if( $way === self::WAY_OUT )
    {
      $res[ "desc"       ] = [
        "_id"                => Uuids::get(),
        "_date"              => Datetimes::now(),
        "way"                => 'out',
      ] ;
      $res[ "emitter"    ] = [
        'extid'              => $this->selfId,
        'name'               => $this->selfName,
      ] ;
      $res[ "recipients" ] = [
        [
          "extid"            => $messageData[ "to" ][ "id" ],
        ]
      ] ;
      $res[ "content"    ] = [
        "txt"                => [
          "txt"                 => $messageData[ "content" ],
          "encoding"            => 'utf8',
        ],
      ] ;
    }
    else
    {
      $this->log( "Invalid way '" . $way . "' ==> message skipped", Logger::LOG_WARN, __METHOD__ ) ;
    }
    $this->log( "'" . $way . "' message : " . json_encode( $res ), Logger::LOG_VERBOZE, __METHOD__ ) ;

    return $res ;
  }
}
?>
