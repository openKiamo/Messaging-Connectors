'use strict' ;


const http            =        require( 'http'                                          ) ;
const https           =        require( 'https'                                         ) ;
const express         =        require( 'express'                                       ) ;
const fs              =        require( 'fs'                                            ) ;
const bodyParser      =        require( 'body-parser'                                   ) ;
const process         =        require( 'process'                                       ) ;
const readline        =        require( 'readline'                                      ) ;

var   jsLibToolsPath  = '../_JSLibTools'                                                  ;
var   confPath        = './conf'                                                          ;
var   dataPath        = './data'                                                          ;

var   Basics          =        require(  jsLibToolsPath + '/' + 'Node/Bases/_basics.js' ) ;
var   LEVEL           = Basics.require( 'Bases/levels.js'              , jsLibToolsPath ) ;
var   Commons         = Basics.require( 'Bases/commons.js'             , jsLibToolsPath ) ;
var   XmlJsons        = Basics.require( 'Utils/xmlJsons.js'            , jsLibToolsPath ) ;

var   ServerSetup     =        require( confPath + '/' + 'setup.js'                     ) ;
var   AppListener     =        require( './facebookAppListener.js'                      ) ;


// ####################################################################################################################

var SInjCoreBundle = SInjCoreBundle || {

  // Data
  // ###
  data    : {
    setup   : null,
    app     : null,
    hserver : null,
    sserver : null,
  },

  // Init
  // ###
  init : function()
  {
    // Init Server Setup
    ServerSetup.init() ;
    SInjCoreBundle.data.setup = ServerSetup.setup ;

    // Init Commons Logs
    SInjCoreBundle.initCommonLogs() ;

    // Init App
    SInjCoreBundle.initApp() ;
  },

  
  // Init
  // ---

  initCommonLogs : function()
  {
    Commons.set( 'Logs.setup.level'      , SInjCoreBundle.data.setup.logs.level         ) ;
    Commons.set( 'Logs.setup.displayDate', SInjCoreBundle.data.setup.logs.displayDate   ) ;
    Commons.set( 'Logs.enabled.console'  , SInjCoreBundle.data.setup.logs.enableConsole ) ;
    Commons.set( 'Logs.enabled.file'     , SInjCoreBundle.data.setup.logs.enableFile    ) ;
  },


  initApp : function()
  {
    Commons.log( 'App init...', LEVEL.INFOP ) ;

    SInjCoreBundle.data.app = express().use( bodyParser.json() ) ; // creates express http server

    // Specific App Listener : init
    AppListener.init( SInjCoreBundle ) ;
  },
  

  // Core
  // ###
  run : function()
  {
    // Capture CTRL-C and exit properly (close socket connection)
    // ###
    if( process.platform === "win32" )
    {
      var rl = readline.createInterface( {
        input  : process.stdin,
        output : process.stdout,
      } ) ;
      rl.on( "SIGINT", function ()
      {
        process.emit( "SIGINT" ) ;
      } ) ;
    }

    process.on( "SIGINT", function ()
    {
      Commons.log( 'EXIT : user CTRL-C', LEVEL.INFOP ) ;
      
      try
      {
        // Proper clean process
      }
      catch( e ){}

      process.exit() ;
    } ) ;


    // SERVER Start
    // ###

    Commons.log( 'Starting server...'                                               , LEVEL.INFOP ) ;
    Commons.log( '=> protocol(s) : ' + SInjCoreBundle.data.setup.selfServer.protocol, LEVEL.INFOP ) ;

    // http
    if( SInjCoreBundle.data.setup.selfServer.protocol == 'all' || SInjCoreBundle.data.setup.selfServer.protocol == 'http' )
    {
      SInjCoreBundle.data.hserver = http.createServer( SInjCoreBundle.data.app ).listen( SInjCoreBundle.data.setup.selfServer.port.http , () => Commons.log( '==> http  webhook is listening on port ' + SInjCoreBundle.data.setup.selfServer.port.http  + ' ...', LEVEL.INFOP ) ) ;
    }

    // https
    if( SInjCoreBundle.data.setup.selfServer.protocol == 'all' || SInjCoreBundle.data.setup.selfServer.protocol == 'https' )
    {
      var options = {
        key  : fs.readFileSync( SInjCoreBundle.data.setup.selfServer.security.keyFile ),
        cert : fs.readFileSync( SInjCoreBundle.data.setup.selfServer.security.crtFile ),
        ca   : fs.readFileSync( SInjCoreBundle.data.setup.selfServer.security.caFile  )
      } ;
      SInjCoreBundle.data.sserver = https.createServer( options, SInjCoreBundle.data.app ).listen( SInjCoreBundle.data.setup.selfServer.port.https, () => Commons.log( '==> https webhook is listening on port ' + SInjCoreBundle.data.setup.selfServer.port.https + ' ...', LEVEL.INFOP ) ) ;
    }
  }
}
SInjCoreBundle.init() ;
SInjCoreBundle.run()  ;
