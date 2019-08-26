'use strict' ;


const request         =        require( 'request'                                       ) ;


var   jsLibToolsPath  = '../_JSLibTools'                                                  ;

var   Basics          =        require(  jsLibToolsPath + '/' + 'Node/Bases/_basics.js' ) ;
var   LEVEL           = Basics.require( 'Bases/levels.js'              , jsLibToolsPath ) ;
var   Commons         = Basics.require( 'Bases/commons.js'             , jsLibToolsPath ) ;
var   XmlJsons        = Basics.require( 'Utils/xmlJsons.js'            , jsLibToolsPath ) ;

var   MsgManager      =        require( './messageManager.js'                           ) ;


// ####################################################################################################################


/*
   Messenger Platform Quick Start Tutorial
   https://developers.facebook.com/docs/messenger-platform/getting-started/quick-start/
 */


/*
   Register process :
   ---
   https://developers.facebook.com/docs/messenger-platform/getting-started/app-setup
  
   In the 'Webhooks' section of the Messenger settings console, click the 'Setup Webhooks' button.
   In the 'Callback URL' field, enter the public URL for your webhook.
   In the 'Verify Token' field, enter the verify token for your webhook.
   !!!!!!!  Under 'Subscription Fields', select the webhook events you want delivered to you webhook. At a minimum, we recommend you choose messages and messaging_postbacks to get started.
   Click the 'Verify and Save' button.
*/

var FacebookAppListener = FacebookAppListener || {

  // Data
  // ###
  core : null,


  // Init
  // ###
  init : function( coreBundleParent )
  {
    FacebookAppListener.core = coreBundleParent ;

    FacebookAppListener.initSelf()           ;
    FacebookAppListener.initMessageManager() ;
    FacebookAppListener.initApp()            ;
  },

  
  // Init
  // ---

  initSelf : function()
  {
    // Build Facebook API URL prefix
    FacebookAppListener.core.data.setup.facebook.api[ 'prefixUrl' ] = FacebookAppListener.core.data.setup.facebook.api.uri + '/v' + FacebookAppListener.core.data.setup.facebook.api.version + '/' ;
    Commons.log( 'Facebook API Url Prefix : ' + FacebookAppListener.core.data.setup.facebook.api.prefixUrl, LEVEL.INFOP ) ;
  },

  initMessageManager : function()
  {
    Commons.log( 'MessageManager init...', LEVEL.INFOP ) ;
    
    MsgManager.init( FacebookAppListener.core ) ;
  },


  // App Init
  // ---
  // Purpose : definition of all the HTTP GET / POST endpoints and verbs
  initApp : function()
  {
    Commons.log( 'Facebook App Listener init...', LEVEL.INFOP ) ;


    // HTTP GETs
    // ---

    // GET Ping
    // ---
    // Purpose : verify server accessibility
    // Test    : > curl --insecure -X GET "https://<domain>:<port>/ping"  (--insecure if no cert)
    FacebookAppListener.core.data.app.get( '/ping', ( req, res ) =>
    {
      res.status( 200 ).send( 'Pong !' ) ;

      Commons.log( 'Received PING'      , LEVEL.INFO ) ;
      Commons.log( '=> Responded Pong !', LEVEL.INFO ) ;
    } ) ;


    // GET Webhook : Verification
    // ---
    // Purpose : Facebook challenge webhook
    // Note    : this endpoint is used by Facebook to register your https server on your Facebook Messaging App
    // Test    : > curl -X GET "https://<domain>:<port>/webhook?hub.verify_token=<YOUR VERIFY TOKEN>&hub.challenge=CHALLENGE_ACCEPTED&hub.mode=subscribe"
    FacebookAppListener.core.data.app.get( '/webhook', ( req, res ) =>
    {
      // Parse the query params
      let mode      = req.query[ 'hub.mode'         ] ;
      let token     = req.query[ 'hub.verify_token' ] ;
      let challenge = req.query[ 'hub.challenge'    ] ;
        
      // Checks if a token and mode is in the query string of the request
      if( mode && token )
      {
        // Checks the mode and token sent is correct
        if( mode === 'subscribe' && token === FacebookAppListener.core.data.setup.selfServer.security.verifyToken )
        {
          // Responds with the challenge token from the request
          res.status( 200 ).send( challenge ) ;

          Commons.log( 'Received GET...'            , LEVEL.DEBUG ) ;
          Commons.log( '=> Mode      = ' + mode     , LEVEL.DEBUG ) ;
          Commons.log( '=> Token     = ' + token    , LEVEL.DEBUG ) ;
          Commons.log( '=> Challenge = ' + challenge, LEVEL.DEBUG ) ;
          Commons.log( '==> Webhook : VERIFIED'     , LEVEL.DEBUG ) ;
        }
        else
        {
          // Responds with '403 Forbidden' if verify tokens do not match
          res.sendStatus( 403 ) ;

          Commons.log( 'Received GET...'            , LEVEL.WARN  ) ;
          Commons.log( '=> Mode      = ' + mode     , LEVEL.DEBUG ) ;
          Commons.log( '=> Token     = ' + token    , LEVEL.DEBUG ) ;
          Commons.log( '=> Challenge = ' + challenge, LEVEL.DEBUG ) ;
          Commons.log( '==> Webhook : FORBIDDEN'    , LEVEL.WARN  ) ;
        }
      }
    } ) ;


    // GET Messages (unread)
    // ---
    // Purpose : returns all pending unread messages (all the messages that have been pushed by Facebook to the server, and not read yet by Kiamo)
    // Note    : all returned messages are considered read, and immediately wiped out from the server memory
    // Note    : the token is require to avoid unauthorized accesses ; it's the Facebook verification token
    // Test    : > curl -X GET "https://<domain>:<port>/messages?way=inp&pop=true&sort=true&token=<YOUR VERIFY TOKEN>"
    FacebookAppListener.core.data.app.get( '/messages', ( req, res ) =>
    {
      Commons.log( 'Received GET Messages...', LEVEL.DEBUG ) ;
      let token = req.query[ 'token' ] ? req.query[ 'token' ] : 'NONE' ;
      if( token != FacebookAppListener.core.data.setup.selfServer.security.verifyToken )
      {
        res.sendStatus( 403 ) ;

        Commons.log( '==> GET Messages : FORBIDDEN', LEVEL.WARN ) ;
        return ;
      }
      let way   = req.query[ 'way'   ] ?                  req.query[ 'way'   ]   : 'inp'  ;
      let sort  = req.query[ 'sort'  ] ? Commons.boolVal( req.query[ 'sort'  ] ) : true   ;
      let pop   = req.query[ 'pop'   ] ? Commons.boolVal( req.query[ 'pop'   ] ) : true   ;
      let msgArr = MsgManager.getMessages( way, sort, pop ) ;

      res.status( 200 ).send( msgArr ) ;

      Commons.log( '==> GET Messages count : ' + msgArr.length, LEVEL.DEBUG ) ;
    } ) ;


    // GET User
    // ---
    // Purpose : get the user profile (mainly, the user name) though the userId received in the pushed message
    // Note    : the user profile can be cached in the server memory (the cache management is already implemented and automated in the MessageManager)
    // Test    : > curl -X GET "https://<domain>:<port>/user?id=<userId>&token=<YOUR VERIFY TOKEN>"
    FacebookAppListener.core.data.app.get( '/user', ( req, res ) =>
    {
      let token = req.query[ 'token' ] ? req.query[ 'token' ] : 'NONE' ;
      if( token != FacebookAppListener.core.data.setup.selfServer.security.verifyToken )
      {
        res.sendStatus( 403 ) ;

        Commons.log( '==> GET User Profile : FORBIDDEN', LEVEL.WARN ) ;
        return ;
      }
      let userId = req.query[ 'id' ] ;
      if( !userId )
      {
        res.sendStatus( 400 ) ;

        Commons.log( '==> GET User Profile : No Id Provided', LEVEL.WARN ) ;
        return ;
      }
      Commons.log( 'Received Get User Profile, id=' + userId + '...', LEVEL.DEBUG ) ;
      let callback = function( data, err )
      {
        if( err === false )
        {
          res.status( 200 ).send( data ) ;

          Commons.log( '==> GET User Profile, id=' + userId + " FOUND", LEVEL.DEBUG ) ;
        }
        else
        {
          res.sendStatus( 404 ) ;

          Commons.log( '==> KO : GET User Profile, id=' + userId + " NOT FOUND", LEVEL.DEBUG ) ;
        }

      } ;
      FacebookAppListener.getUserProfile( userId, callback ) ;
    } ) ;


    // HTTP POST
    // ---

    // POST Send
    // ---
    // Purpose : send a message to a Facebook user
    /*
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
    // Test    : > curl -X POST "https://<domain>:<port>/send" -d "{\"recipients\": [ { \"extid\": <userId> } ], \"content\": { \"txt\": { \"txt\": <message> } } }" -H "Content-Type: application/json"
    FacebookAppListener.core.data.app.post( '/send', ( req, res ) =>
    {
      // Parse the query params
      let recipientId  = req.body.recipients[0].extid ;
      let message      = req.body.content.txt.txt     ;
      let messageData  = { "text" : message }         ;
      let token        = req.body.token               ;
      if( token != FacebookAppListener.core.data.setup.selfServer.security.verifyToken )
      {
        res.sendStatus( 403 ) ;

        Commons.log( '==> POST Message : FORBIDDEN', LEVEL.WARN ) ;
        return ;
      }

      res.sendStatus( 200 ) ;

      Commons.log( 'Received POST Send...'          , LEVEL.DEBUG ) ;
      Commons.log( '=> RecipientId = ' + recipientId, LEVEL.DEBUG ) ;
      Commons.log( '=> Message     = ' + message    , LEVEL.DEBUG ) ;

      FacebookAppListener.sendMessage( recipientId, messageData ) ;
    } ) ;


    // POST Webhook : receive a Facebook user message
    // ---
    // Purpose : Endpoint to the Facebook Webhook Push mechanism
    // Note    : A received message is temporarily stored in the server memory, until the unread message are recovered by Kiamo
    // Note    : The user profile is requested (in order to recover the user's name) each time a message is stored.
    //         : For this reason, it's recommanded to use users profile cache capability in order to avoid useless requests to Facebook
    // Test    : > curl -H "Content-Type: application/json" -X POST "https://<domain>:<port>/webhook" -d "{\"object\": \"page\", \"entry\": [{\"messaging\": [{\"message\": \"TEST_MESSAGE\"}]}]}"
    FacebookAppListener.core.data.app.post( '/webhook', ( req, res ) =>
    {  
      let body = req.body ;

      Commons.log( 'Received POST...'               , LEVEL.DEBUG ) ;

      // Checks this is an event from a page subscription
      if( body.object === 'page' )
      {
        body.entry.forEach( function( entry )  // Iterates over each entry - there may be multiple if batched
        {
          let webhookEvent = entry.messaging[ 0 ]   ;  // entry.messaging is an array, but will only ever contain one message, so we get index 0
          let senderPsid   = webhookEvent.sender.id ;

          Commons.log( '=> Sender PSID : ' + webhookEvent.sender.id, LEVEL.DEBUG ) ;
          Commons.log( '=> Event       : ' + XmlJsons.json2str( webhookEvent ), LEVEL.DEBUG ) ;
          
          // Check if the event is a message or postback and pass the event to the appropriate handler function
          if( webhookEvent.message )
          {
            Commons.log( '=> Message     : ' + webhookEvent.message.text, LEVEL.DEBUG ) ;
            FacebookAppListener.handleMessage(  webhookEvent ) ;
          }
          else if( webhookEvent.postback )
          {
            Commons.log( '=> Postback    : ' + webhookEvent.postback    , LEVEL.DEBUG ) ;
            FacebookAppListener.handlePostback( webhookEvent ) ;
          }
        } ) ;

        // Returns a '200 OK' response to all requests
        res.status( 200 ).send( 'EVENT_RECEIVED' ) ;

        Commons.log( '==> Webhook : EVENT_RECEIVED'   , LEVEL.DEBUG ) ;
      }
      else
      {
        // Returns a '404 Not Found' if event is not from a page subscription
        res.sendStatus( 404 ) ;

        Commons.log( '==> Webhook : 404 Not Found', LEVEL.WARN  ) ;
      }
    } ) ;

  },
  

  // Inner Tools
  // ---

  // Message Events
  // ---

  // Handles messages events
  /*
     Example :
      {
        "message": {
          "mid": "dDS9KXPCL3pTMsufVgKr0YYUea2xFhgPl2taIpzw0bGVnTqxkc0wkhy_1SqZ-4U4tjgZvXPjy5yO2RDMd2Qtpz",
          "text": "SIn Test Message"
        },
        "sender": {
          "id": "<SENDER ID>"
        },
        "recipient": {
          "id": "<RECIPIENT ID>"
        },
        "timestamp": 1565616845583
      }
  */
  handleMessage : function( webhookEvent )
  {
    let msg = undefined ;
    try
    {
      let senderPsid  = webhookEvent.sender.id    ;
      let receivedMsg = webhookEvent.message.text ;

      Commons.log( 'handleMessage : Sender PSID = ' + senderPsid + ', message = ' + receivedMsg, LEVEL.DEBUG ) ;

      msg = MsgManager.getMessagePattern( true, true, 1, true ) ;
      
      // Map incoming message
      msg.desc.extid           = webhookEvent.message.mid  ;
      msg.desc.extdate         = webhookEvent.timestamp    ;
      msg.desc.type            = 'text'                    ;
      msg.desc.way             = 'inp'                     ;
      msg.emitter.extid        = webhookEvent.sender.id    ;
      msg.recipients[0].extid  = webhookEvent.recipient.id ;
      msg.content.txt.txt      = webhookEvent.message.text ;
      msg.content.txt.encoding = 'utf8' ;
      
      // Get the sender name, either on the cache or requesting Facebook
      let senderProfile        = MsgManager.getUserProfile( msg.emitter.extid ) ;
      if( !Commons.empty( senderProfile ) )
      {
        msg.emitter.name = senderProfile.name ;
        Commons.log( 'Cached sender name, id=' + msg.emitter.extid + ', name=' + msg.emitter.name, LEVEL.DEBUG ) ;

        MsgManager.addMessage( msg ) ;
        Commons.log( 'Mapped Message : \n' + XmlJsons.json2str( msg ), LEVEL.VERBOZE ) ;
      }
      else
      {
        let callback = function( data, err )
        {
          // If an error occurs, simply ignore the sender name
          if( err === false )
          {
            if(      'name' in data )
            {
              msg.emitter.name = data.name ;
              MsgManager.setUserProfile( data ) ;
            }
            else if( 'id'   in data )
            {
              msg.emitter.name = data.id   ;
            }
            else
            {
              Commons.log( 'Requested sender name, id=' + msg.emitter.extid + ', no name, invalid body returned...', LEVEL.DEBUG ) ;
            }
            Commons.log(      'Requested sender name, id=' + msg.emitter.extid + ', name=' + msg.emitter.name         , LEVEL.DEBUG ) ;
          }

          MsgManager.addMessage( msg ) ;
          Commons.log( 'Mapped Message : \n' + XmlJsons.json2str( msg ), LEVEL.VERBOZE ) ;
        } ;
        FacebookAppListener.getUserProfile( msg.emitter.extid, callback ) ;
      }
    }
    catch( e )
    {
      // Malformed message
      Commons.log( 'ERROR : Malformed Message : ' + e + '\n' + XmlJsons.json2str( webhookEvent ), LEVEL.WARN ) ;
    }
    // Careful : as the getUserProfile can be a request to FB, the sender name can be empty (async request) when the 'return msg' is done.
    //           But it's not a problem as the mesagge is added to the MessageManager only when properly filled (callback).
    //           This return line is only here for debug purposes.
    return msg ;
  },


  // Handles messaging_postbacks events
  /*
     Example :
      {
        "sender":{
          "id":"<PSID>"
        },
        "recipient":{
          "id":"<PAGE_ID>"
        },
        "timestamp":1458692752478,
        "postback":{
          "title": "<TITLE_FOR_THE_CTA>",  
          "payload": "<USER_DEFINED_PAYLOAD>",
          "referral": {
            "ref": "<USER_DEFINED_REFERRAL_PARAM>",
            "source": "<SHORTLINK>",
            "type": "OPEN_THREAD",
          }
        }
      }  
  */
  handlePostback : function( webhookEvent )
  {
    let msg = undefined ;
    try
    {
      let senderPsid  = webhookEvent.sender.id      ;
      let receivedPb  = webhookEvent.postback.title ;

      Commons.log( 'handlePostback : Sender PSID = ' + senderPsid + ', postback = ' + receivedPb, LEVEL.DEBUG ) ;

      msg = MsgManager.getMessagePattern( true, true, 1, true ) ;
      
      // Map incoming postback
      msg.desc.extid           = webhookEvent.postback.referral.ref ;
      msg.desc.type            = 'postback'                         ;
      msg.desc.way             = 'inp'                              ;
      msg.emitter.extid        = webhookEvent.sender.id             ;
      msg.recipients[0].extid  = webhookEvent.recipient.id          ;
      msg.content.txt.txt      = webhookEvent.postback.title        ;
      msg.content.txt.data     = webhookEvent.postback.payload      ;
      msg.content.txt.encoding = 'utf8' ;

      MsgManager.addMessage( msg ) ;
      
      //Commons.log( 'Mapped Postback : \n' + XmlJsons.json2str( MsgManager.getMessage( id, 'inp', false ) ), LEVEL.DEBUG ) ;
      Commons.log( 'Mapped Postback : \n' + XmlJsons.json2str( msg ), LEVEL.DEBUG ) ;
    }
    catch( e )
    {
      // Malformed postback
      Commons.log( 'ERROR : Malformed Postback :\n' + XmlJsons.json2str( webhookEvent ), LEVEL.DEBUG ) ;
    }
    return msg ;
  },


  // Get User Id
  // ---
  // Purpose : get user profile (mainly the user name)
  // Note    : as the request is asynchronous, the callback is used to apply a treatment once the request is complete and the result has been returned
  getUserProfile : function( userId, callback )
  {
    // Send the HTTP request to the Messenger Platform
    request(
      {
        "uri"    : FacebookAppListener.core.data.setup.facebook.api.prefixUrl + userId,
        "qs"     : { "access_token" : FacebookAppListener.core.data.setup.selfServer.security.pageAccessToken },
        "method" : "GET",
      },
      ( err, res, body ) =>   // Where res is the full request response, and body the minimal set of relevant data
      {
        Commons.log( 'Get User Profile, id=' + userId, LEVEL.DEBUG ) ;
        let _body = undefined ;
        if( !Commons.empty( body ) )
        {
          _body = XmlJsons.json2dict( body ) ;
          if( 'error' in _body ) _body = undefined ;
          if( !Commons.empty( _body ) && !( 'name' in _body ) )  // Fill the name in any cases
          {
            _body[ 'name' ] = '' ;
            [ 'first_name', 'last_name', 'id' ].forEach( function( k )
            {
              if( k in _body )
              {
                if( k == 'id' && !Commons.empty( _body[ 'name' ] ) ) return ;
                if( !Commons.empty( _body[ 'name' ] ) ) _body[ 'name' ] += ' ' ;
                _body[ 'name' ] += _body[ k ] ;
              }
            } ) ;
          }
        }
        if( Commons.empty( err ) && !Commons.empty( _body ) )
        {
          Commons.log( '==> RES = ' + XmlJsons.json2str( res  ), LEVEL.VERBOZE ) ;
          Commons.log( '==> Get User Profile = \n' + XmlJsons.json2str( _body ), LEVEL.DEBUG ) ;
          return callback( _body, false ) ;
        }
        else
        {
          let log = '==> KO : Get User Profile, id=' + userId + ' : '  ;
          if( !err ) log += 'No such user' ;
          else       log += err            ;
          Commons.log( log, LEVEL.WARN ) ;
          return callback( null, err ) ;
        }
    } ) ; 
  },


  // Sends response messages via the Send API
  sendMessage : function( recipientId, message )
  {
    // Construct the message body
    let requestBody = {
      "recipient" :
      {
        "id" : recipientId
      },
      "message" : message
    }

    // Send the HTTP request to the Messenger Platform
    request(
      {
        "uri"    : FacebookAppListener.core.data.setup.facebook.api.prefixUrl + 'me/messages',
        "qs"     : { "access_token" : FacebookAppListener.core.data.setup.selfServer.security.pageAccessToken },
        "method" : "POST",
        "json"   : requestBody
      },
      ( err, res, body ) =>
      {
        Commons.log( 'Recipient PSID = ' + recipientId + ', message = ' + message.text, LEVEL.DEBUG ) ;
        if( !err )
        {
          Commons.log( 'message sent!', LEVEL.DEBUG ) ;
        }
        else
        {
          Commons.log( "Unable to send message : " + err, LEVEL.ERROR ) ;
        }
    } ) ; 
  }
} ;
module.exports = FacebookAppListener ;
