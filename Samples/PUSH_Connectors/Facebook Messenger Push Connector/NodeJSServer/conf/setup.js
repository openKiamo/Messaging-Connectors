'use strict' ;


var jsLibToolsPath      = '../_JSLibTools' ;
var dataPath            = './data' ;

var Basics              =        require( '../' + jsLibToolsPath + '/' + 'Node/Bases/_basics.js' ) ;
var LEVEL               = Basics.require( 'Bases/levels.js', jsLibToolsPath                      ) ;


// ####################################################################################################################

// Setup
// ###

var FBSInjSetup = FBSInjSetup || {

  setup : null,

  init : function()
  {
    FBSInjSetup.setup = {
      logs          : {
        level         : LEVEL.TRACE,
        displayDate   : true ,
        enableConsole : true ,
        enableFile    : false,
      },
      facebook      : {
        api           : {
          uri           : 'https://graph.facebook.com',
          version       : '2.6',
        },
      },
      selfServer    : {
        protocol      : 'all',   // in http | https | all
        port          : {
          http          : 14476,
          https         : 14477
        },
        security      : {
          crtFile         : dataPath + '/<YOUR CERTIFICATE>.fr.crt'   ,
          caFile          : dataPath + '/<YOUR AUTHORITY CERTIFICATE>.fr_ca.crt',
          keyFile         : dataPath + '/<YOUR CERTIFICATE KEY>.fr.key'   ,
          verifyToken     : '<YOUR VERIFY TOKEN>',
          pageAccessToken : '<YOUR PAGE ACCESS TOKEN>',
        },
      },
      data          : {
        cache         : {
          users         : {
            enabled       : true,
            expireInSecs  : 120,
          },
        }
      },
    } ;
  },
} ;
module.exports = FBSInjSetup ;
