'use strict' ;


var jsLibToolsPath    = '../_JSLibTools' ;
var dataPath          = './data' ;

var   Basics          =        require(  jsLibToolsPath + '/' + 'Node/Bases/_basics.js' ) ;
var   LEVEL           = Basics.require( 'Bases/levels.js'              , jsLibToolsPath ) ;
var   Commons         = Basics.require( 'Bases/commons.js'             , jsLibToolsPath ) ;
var   XmlJsons        = Basics.require( 'Utils/xmlJsons.js'            , jsLibToolsPath ) ;


// ####################################################################################################################

// Message Manager
// ---

var SInjMessageManager = SInjMessageManager || {

  // Data Skeletons (const)
  // ---
  xSKTData : {
    
    // Input / Output messages
    // ---
    messages           : {
      inp                : {      // Input messages
        timeline           : {},
        bag                : {},
      },
      out                : {
        timeline           : {},
        bag                : {},
      },
      users              : {
        timeline           : {},
        profiles           : {},
        runtime            : {
          enabled            : false,
          expirationms       :     0,
          nextExpiration     :     0,
        },
      },
    },
    
    // Description pattern
    // ---
    descriptionPattern : {
      _id                : undefined,
      extid              : undefined,
      type               : undefined,
      _date              : undefined,
      extdate            : undefined,
      way                : undefined,
      data               : undefined,
    },

    // Recipient pattern
    // ---
    recipientPattern   : {
      _id                : undefined,
      extid              : undefined,
      name               : undefined,
      key                : undefined,
      data               : undefined,
    },

    // Attachment pattern
    // ---
    attachmentPattern  : {
      _id                : undefined,
      extid              : undefined,
      name               : undefined,
      key                : undefined,
      type               : undefined,
      encoding           : undefined,
      data               : undefined,
    },

    // Text pattern
    // ---
    textPattern        : {
      txt                : undefined,
      encoding           : undefined,
      data               : undefined,
    },

    // Message pattern
    // ---
    messagePattern     : {
      desc               : undefined,    // => descPattern
      emitter            : undefined,    // => recipientPattern
      recipients         : [],           // => recipientPattern array
      content            : {
        txt                : undefined,  // => textPattern
        attachments        : undefined,  // => attachmentPattern
      },
    },

  },


  // Data
  // ---
  core : null,
  data : null,


  // Init
  // ---
  init : function( coreBundleParent )
  {
    Commons.log( 'MessageManager init...', LEVEL.INFOP ) ;

    SInjMessageManager.core = coreBundleParent ;
    SInjMessageManager.data = Commons.clone( SInjMessageManager.xSKTData ) ;

    // Init user cache
    SInjMessageManager.data.messages.users.runtime.enabled        =                SInjMessageManager.core.data.setup.data.cache.users.enabled               ;
    SInjMessageManager.data.messages.users.runtime.expirationms   =                SInjMessageManager.core.data.setup.data.cache.users.expireInSecs * 1000   ;
    SInjMessageManager.data.messages.users.runtime.nextExpiration = Basics.nextts( SInjMessageManager.data.messages.users.runtime.expirationms             ) ;

    Commons.log(   'User cache enabled      => ' + SInjMessageManager.data.messages.users.runtime.enabled                    , LEVEL.INFOP ) ;
    if( SInjMessageManager.data.messages.users.runtime.enabled === true )
    {
      Commons.log( 'User cache expire every => ' + SInjMessageManager.core.data.setup.data.cache.users.expireInSecs + ' secs', LEVEL.INFOP ) ;
    }
  },


  // Message Patterns
  // ---
  
  // Message Pattern
  /*
     Example :
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
  getMessagePattern : function( desc, emitter, recipientNb, contentTxt )
  {
    desc        = Basics.defVal( desc       , false ) ;
    emitter     = Basics.defVal( emitter    , false ) ;
    recipientNb = Basics.defVal( recipientNb, 0     ) ;
    contentTxt  = Basics.defVal( contentTxt , false ) ;

    var res = Commons.clone( SInjMessageManager.xSKTData.messagePattern )       ;
    if( desc       == true ) res.desc        = SInjMessageManager.getDescriptionPattern() ;
    if( emitter    == true ) res.emitter     = SInjMessageManager.getRecipientPattern()   ;
    if( contentTxt == true ) res.content.txt = SInjMessageManager.getTextPattern()        ;
    for( let i = 0 ; i < recipientNb ; i++ )
    {
      let cur = SInjMessageManager.getRecipientPattern() ;
      res.recipients.push( cur ) ;
    }
    
    return res ;
  },


  // Description Pattern
  getDescriptionPattern : function()
  {
    return Commons.clone( SInjMessageManager.xSKTData.descriptionPattern ) ;
  },
  
  // Emiter / Recipient Pattern
  getRecipientPattern : function()
  {
    return Commons.clone( SInjMessageManager.xSKTData.recipientPattern ) ;
  },
  
  // Text Pattern
  getTextPattern : function()
  {
    return Commons.clone( SInjMessageManager.xSKTData.textPattern ) ;
  },

  // Attachment Pattern
  getAttachmentPattern : function()
  {
    return Commons.clone( SInjMessageManager.xSKTData.attachmentPattern ) ;
  },


  // Message Data
  // ---
  
  // Message Description
  setMessageDescription : function( msg, description )
  {
    msg.desc = description ;
  },

  // Message Emitter
  setMessageEmitter : function( msg, emitter )
  {
    msg.emitter = emitter ;
  },

  // Message Recipients
  addMessageRecipient : function( msg, recipient )
  {
    msg.recipients.push( recipient ) ;
  },
  setMessageRecipients : function( msg, recipientArr )
  {
    msg.recipients = recipientArr ;
  },

  // Message Text
  setMessageText : function( msg, txt )
  {
    msg.content.txt = txt ;
  },

  // Message Attachments
  addMessageAttachment : function( msg, attachment )
  {
    if( msg.content.attachments == null ) msg.content.attachments = [] ;
    msg.content.attachments.push( attachment ) ;
  },
  setMessageAttachments : function( msg, attachmentArr )
  {
    msg.content.attachments = attachmentArr ;
  },
  
  
  // Messages In/Out
  // ---

  addMessage : function( msg, way )
  {
    way = Basics.defVal( way, 'inp' ) ;

    if( msg.desc.way   == null ) msg.desc.way   = way            ;
    if( msg.desc._date == null ) msg.desc._date = Basics.nowms() ;
    if( msg.desc._id   == null ) msg.desc._id   = Basics.uuid()  ;

    SInjMessageManager.data.messages[ msg.desc.way ].bag[      msg.desc._id   ] = msg          ;
    SInjMessageManager.data.messages[ msg.desc.way ].timeline[ msg.desc._date ] = msg.desc._id ;
    
    Commons.log( "New '" + way + "' message added :\n" + XmlJsons.json2str( msg ), LEVEL.VERBOZE ) ;

    return msg.desc._id ;
  },
  
  getMessage : function( id, way, pop )
  {
    way = Basics.defVal( way, 'inp' ) ;
    pop = Basics.defVal( pop, true  ) ;
    
    var res = null ;
    try
    {
      res = SInjMessageManager.data.messages[ way ].bag[ id ] ;
      if( pop == true )
      {
        delete SInjMessageManager.data.messages[ way ].timeline[ res.desc._date ] ;
        delete SInjMessageManager.data.messages[ way ].bag[ id ] ;
      }
    }
    catch( e )
    {
      // No such id
    }
    return res ;
  },
  getMessages : function( way, sorted, pop )
  {
    way    = Basics.defVal( way   , 'inp' ) ;
    sorted = Basics.defVal( sorted, true  ) ;
    pop    = Basics.defVal( pop   , true  ) ;

    var res = [] ;
    if( sorted == false )
    {
      let keys = Object.keys( SInjMessageManager.data.messages[ way ].bag ) ;
      keys.forEach( function( key )
      {
        res.push( SInjMessageManager.data.messages[ way ].bag[ key ] ) ;
      } ) ;
      if( pop == true )
      {
        SInjMessageManager.data.messages[ way ].bag      = {} ;
        SInjMessageManager.data.messages[ way ].timeline = {} ;
      }
    }
    else  // sorted by date
    {
      let keys = Object.keys( SInjMessageManager.data.messages[ way ].timeline ).sort() ;
      keys.forEach( function( key )
      {
        let valId = SInjMessageManager.data.messages[ way ].timeline[ key   ]   ;
        res.push(   SInjMessageManager.data.messages[ way ].bag[      valId ] ) ;
        if( pop == true )
        {
          delete SInjMessageManager.data.messages[ way ].timeline[ key   ] ;
          delete SInjMessageManager.data.messages[ way ].bag[      valId ] ;
        }
      } ) ;
    }
    return res ;
  },
  
  
  // User Profiles
  // ---

  getUserProfiles : function()
  {
    if( SInjMessageManager.data.messages.users.runtime.enabled === false ) return {} ;
    return Commons.clone( SInjMessageManager.data.messages.users.profiles ) ;
  },

  getUserProfile : function( userId )
  {
    if( SInjMessageManager.data.messages.users.runtime.enabled === false ) return undefined ;
    if( ! ( userId in SInjMessageManager.data.messages.users.profiles ) )  return undefined ;

    let userProfile = Commons.clone( SInjMessageManager.data.messages.users.profiles[ userId ] ) ;

    // Renew expiration
    let oldexpirationts = userProfile.expirationts ;
    let newexpirationts = Basics.nextts( SInjMessageManager.data.messages.users.runtime.expirationms ) ;
    delete SInjMessageManager.data.messages.users.timeline[ oldexpirationts ] ;
    SInjMessageManager.data.messages.users.timeline[ newexpirationts ]              = userId          ;
    SInjMessageManager.data.messages.users.profiles[ userId          ].expirationts = newexpirationts ;

    delete userProfile[ 'expirationts' ] ;
    return userProfile ;
  },
  
  setUserProfile : function( userProfile )
  {
    if( SInjMessageManager.data.messages.users.runtime.enabled === false ) return ;

    let userId           = userProfile.id ;
    let userP            = SInjMessageManager.getUserProfile( userId ) ;  // Check if already in the cache, renew the expiration otherwise)
    if( !Commons.empty( userP ) )
    {
      SInjMessageManager.manageUserCacheExpiration() ;
      return ;
    }

    let nextexpirationts = Basics.nextts( SInjMessageManager.data.messages.users.runtime.expirationms ) ;

    userP                   = Commons.clone( userProfile ) ;
    userP[ 'expirationts' ] = nextexpirationts ;

    SInjMessageManager.data.messages.users.profiles[ userId ]           = userP  ;
    SInjMessageManager.data.messages.users.timeline[ nextexpirationts ] = userId ;

    Commons.log( "New user profile added to the cache, userId=" + userId + ", expire at " + nextexpirationts, LEVEL.DEBUG ) ;
    Commons.log( "\n" + XmlJsons.json2str( userP ), LEVEL.VERBOZE ) ;

    // Manage user cache expiration
    SInjMessageManager.manageUserCacheExpiration() ;
  },
  
  manageUserCacheExpiration : function()
  {
    if( SInjMessageManager.data.messages.users.runtime.enabled        === false ) return ;
    let nowts = Basics.nowts() ;
    if( SInjMessageManager.data.messages.users.runtime.nextExpiration >=  nowts ) return ;
    
    SInjMessageManager.data.messages.users.runtime.nextExpiration = nowts + SInjMessageManager.data.messages.users.runtime.expirationms ;

    Commons.log( 'nowts   =' + nowts, LEVEL.TRACE ) ;
    let kExpirationsTSArr = Object.keys( SInjMessageManager.data.messages.users.timeline ).sort() ;
    let i = 0 ;
    for( ; i < kExpirationsTSArr.length ; i++ )
    {
      let curExpirationTs = kExpirationsTSArr[ i ] ;
      let userId          = SInjMessageManager.data.messages.users.timeline[ curExpirationTs ] ;
      Commons.log( 'curExpTs=' + curExpirationTs + ' for userId=' + userId, LEVEL.TRACE ) ;
      if( curExpirationTs > nowts ) break ;
      Commons.log( '==> delete from cache userId=' + userId, LEVEL.TRACE ) ;
      delete SInjMessageManager.data.messages.users.profiles[ userId          ] ;
      delete SInjMessageManager.data.messages.users.timeline[ curExpirationTs ] ;
    }
    Commons.log( "Cache cleaned : " + i + ' user profiles wiped out, ' + Object.keys( SInjMessageManager.data.messages.users.profiles ).length + ' remaining', LEVEL.DEBUG ) ;
  },
} ;
module.exports = SInjMessageManager ;
