# Kiamo Messaging Connector Samples

## Toolkit



| Date    | 20190722  |
| :------ | --------- |
| Version | v1.0.0    |
| Author  | S.Iniesta |



------

[Introduction](#introduction)

&nbsp;&nbsp;&nbsp;&nbsp;[Purpose](#purpose)

&nbsp;&nbsp;&nbsp;&nbsp;[Description](#description)

[Main Helpers](#mainHelpers)

&nbsp;&nbsp;&nbsp;&nbsp;[AutoLoader](#autoloader)

&nbsp;&nbsp;&nbsp;&nbsp;[Module](#module)

&nbsp;&nbsp;&nbsp;&nbsp;[ConfManager](#confmanager)

&nbsp;&nbsp;&nbsp;&nbsp;[Logger](#logger)

&nbsp;&nbsp;&nbsp;&nbsp;[SubModule](#submodule)

[Tools](#tools)

&nbsp;&nbsp;&nbsp;&nbsp;[datetimes](#datetimes)

&nbsp;&nbsp;&nbsp;&nbsp;[dict](#dict)

&nbsp;&nbsp;&nbsp;&nbsp;[files](#files)

&nbsp;&nbsp;&nbsp;&nbsp;[resources](#resources)

&nbsp;&nbsp;&nbsp;&nbsp;[strings](#strings)

&nbsp;&nbsp;&nbsp;&nbsp;[userdata](#userdata)

&nbsp;&nbsp;&nbsp;&nbsp;[uuids](#uuids)

&nbsp;&nbsp;&nbsp;&nbsp;[webs](#webs)

[Additional Tools](#additionalTools)

&nbsp;&nbsp;&nbsp;&nbsp;[Command Line Tester](#commandLineTester)

------



<a name="introduction"></a>
### Introduction

<a name="purpose"></a>
#### 	Purpose

The main idea of this toolkit is the following : when a plugging is needed, it has to be standalone and needs to be easily configurable, write logs, create or access resources, access the internet, etc. In addition, a main instance could use sub-modules and share with them configuration and logging capabilities, for instance.

The purpose of the toolkit is to provide to such package a common structure, tools and helpers in order to ease implementation and allow the developer to focus only on the main features he has to implement.




<a name="description"></a>
#### 	Description

The common structure is based on a folder where the main class files will be dropped, and composed of the following sub-folders :

* conf 	: configuration files
* data 	: resources root folder
* logs 	: logs root folder
* tools	: tools source files



------

<a name="mainHelpers"></a>
### Main Helpers

<a name="autoloader"></a>
#### 	AutoLoader

An auto loader is provided to ease the tools loading. Any `Module`, `SubModule` or class willing to use the toolkit should include the following line :

```php
    require_once <rootPath> . "tools/autoload.php" ;
```

 After this line, any tool can be used directly, or aliased :

```php
    use <ToolkitNamespace>\Module    ;
    use <ToolkitNamespace>\Resources ;
    use <ToolkitNamespace>\Uuids     ;

    class MyModule extends Module
    {
      (...)
```



<a name="module"></a>
#### 	Module

A `Module` is a class which purpose is to provide to a extending class all the main capabilities a module could need, as a Logger or a Configuration Manager for instance.

The `Module` class does not have to be described in detail.

Any extending class will be provided the following capabilities :

* Logger :

  * `log( $logStr, [ $logLevel, $caller ] )` => write a log in the daily log file,
  * actionId :
    * `setActionId( [ $actionId ] )`   => start a log section decorated by an unique actionId,
    * `clearActionId()`                              => stop to decorate the log with the last actionId,
    * `getActionId()`                                  => get the current action Id,

* ConfManager :

  * `getConf( $confPath )`                           => get an item of the module configuration.

  * `getGlobalConf( [ $confPath ] )`      => get an item of the global configuration.

    <u>Remark</u> : `$confPath` is either a string (‘.’ separator) or an array :

    * `getConf( 'runtime.datetimes.dateFormat' )`
    * `getConf( [ 'runtime', 'datetimes, 'dateFormat'] )`

 That means anywhere on the class implementation lines, the extending class can use :

```php
    $this->log( “This is a log”, Logger::LOG_INFO, __METHOD__ ) ;
```

which will generate a formatted log in the following file : `<logsPath>/<ClassName>/<YYYYMMDD>.log`

​    `[YYYYMMDD_hhmmss][INFO ][             ][<ClassName>::<MethodName>] This is a log`

or :

```php
    $dateFormat = $this->getConf( “runtime.datetimes.dateFormat” ) ;
```

which will get the value of the `dateFormat` key in the array returned by the following configuration file :

​    `<confPath>/u_<className>.php`

If the extending class is located at the root of the package and want to use the standard package structure and design, the `Module` constructor can be skipped (everything is automated).

Otherwise, all the folder locations and even the module name, driving the configuration file name, can be specified while building the extending class instance.

```php
class MyClass extends Module
{
  public function __construct()
  {
    parent::__construct( <PathToTheToolkit>,<LogsFolderPath>,<ConfFolderPath>,<ModuleName> ) ;
    (...)
```



<a name="confmanager"></a>
#### 	ConfManager

The `ConfManager` class does not have to be described in detail.

All the configuration files are PHP `key => value` arrays, wrote and used like JSON dictionaries.

A `ConfManager` instance is automatically created while instantiating a `Module`.

This class is an helper to manage and access the package configuration :

* the configuration root file is `<rootFolder>/conf/_config.php`. This file describes the declared configurations (package main classes and tools configurations). The Configuration Manager use it to resolve,  access and loads (on demand) the required configuration data.
* a configuration file is only loaded on demand (access to one of its’ items), and only one time (keeped in RAM once loaded the first time).

* the main Configuration Manager methods are :
  * `getConf( $itemPath )` : access to a configuration item. If `$itemPath` is empty, the whole configuration array is returned.

  * `isInConfig( $itemPath )` : check either or not a configuration item is present on the configuration array.

    <u>Warning</u> : the configuration must be loaded before using this method, otherwise the return will be `false` even if the item exists on the conf.

  * `loadConf( $confPath )` : force the load of a configuration. The `$confPath` must end with the configuration item name, as `'tools.logger'` or `'user.MyModule'` for instance.

    <u>Note</u> : most of the time it’s useless to force a configuration load. The use of `getConf` while accessing a conf item will automatically load it.



<a name="logger"></a>
#### 	Logger

The `Logger` class does not have to be described in detail.

This class is a logs manager helper. A Logger instance is automatically created while instantiating a `Module`.

The daily log files will be written in `<logsFolder>/<className>.` Note that if a `Module` uses a `SubModule`, the SubModule will have his own logs sub folder : `<logsFolder>/<subModuleClassName>`.

 It’s also possible to use the Logger capabilities as Logger class methods, without a Logger instance. In such case the logs file will be written in the `<logsFolder>` folder.

* The Logger configuration is file is `<rootPath>/conf/t_logger.php`, and defines :
  * the logs behavior (key : ‘`behavior`’) :

    * global log level (key : ‘`globalLogLevel`’), in :

      * `Logger::LOG_VERBOSE`,
      * `Logger::LOG_TRACE`,
      * `Logger::LOG_DEBUG`,
      * `Logger::LOG_INFO`,
      * `Logger::LOG_WARN`,
      * `Logger::LOG_ERROR`,
      * `Logger::LOG_CRITICAL`.

      Only the logs with a level upper or equals to this global level will be written.

    * date format ( key : ‘`dateFormat`’),

    * « smart » caller method name (key : ‘`smartMethodName`’) :

      * enabled (key : ‘`enabled`’) :
        * if `false`, the full caller method name will be written,
        * if `true`, the caller method name will be cropped to ease the logs reading.
      * Strict length (key : ‘`strictLength`’) : the logs block dedicated to the caller method name will be forced to this size (shorter filled of spaces, longer right truncated).

  * Logs files management (key : ‘`files`’) :

    * root logs folder (key : ‘`folder`’) : relative root logs folder path.

    * obsolete log files management (key : ‘`obsolete`’) :

      * zip (key : ‘`zipOlderThan`’) : the log files older than the defined number of days are individually zipped.
      * delete (key : ‘`deleteOlderThan`’) : the log files older than the defined number of days are deleted.

      Note : this is checked each time a new daily logs file must be created.



* The logs are written in daily files : `<rootFolder>/logs/<ModuleClassName>/YYYYMMDD.log`

* The main function is `log( $str, [ $level, $method, $actionId, $indentLevel ] )` :
  * `$str` the string to log,
  * `$level`, the log level (default value, `Logger::LOG_DEBUG`),
  * `$method`, name of the caller method. It’s recommanded to use the PHP « magic constant » `__METHOD__`.
  * `$actionId` : log action id. Except specific needs, it’s recommanded to use the `Module/SubModule` `setActionId` and `clearActionId` to decorate a logs section, and not using this manual parameter.
  * `$indentLevel` : int value indicating the number of left space to add before the log string (just after the method name block). Can be used to ease the logs readability in specific cases (but ignore it otherwise).

* Logs lines examples :

```
[20190429_102557][INFO ][             ][MyModule::__construct ] ---------
[20190429_102557][INFO ][             ][MyModule::__construct ] INIT : OK
```

where :

* 1st bloc is the log date

* 2nd is the log level

* 3rd the `actionId`

* 4th the caller method

* then the actual log.



<a name="submodule"></a>
#### 	SubModule

The purpose of a sub module is to provide a consistent set of features (database access, web service interface, …) to a main module.

 The purpose of the `SubModule` class is to inherit from their parent Module its main helpers (configuration manager, logger, …), in order they can generate logs or access configuration items without having to create their own helpers instances.

 As a parent `Module`, a sub module must extend the `SubModule` class :

```php
<?php
namespace <ConnectorNamespace> ;

require_once( __DIR__ . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . 'autoloader.php' ) ;

use <ConnectorNamespace>\ConfManager ;
use <ConnectorNamespace>\SubModule   ;

class MySubModule extends SubModule
{
  public   function __construct( $_parent )  // The _parent must be a Module
  {
    parent::__construct( $_parent, get_class( $_parent ), ConfManager::UserConfType ) ;
  }

  public   function doSomething( $args )
  {
    (...)
  }
  (...)
}
?>
```

 Then the sub-module will benefit of the same features than the parent `Module` : `log`, `setActionId`, `clearActionId`, `getConf`, `getGlobalConf`. The log and conf file of a sub module are the parent’s ones.

 <u>Note</u> :

> In the `__construct__`, the second parameter is the sub module name.
>
> If this name is different than the parent `Module` name, the `SubModule` will have its own logs, configuration and resource files. In the other hand in such case, you’ll need to access manually to the parent capabilities, as `$this->_parent-><capability>( ... ) ;`
>
> Otherwise, if the `SubModuleModule` has to access and log file of the parent `Module`, simply pass `get_class( $parent )` as second parameter.



-----

<a name="tools"></a>
### Tools

<a name="datetimes"></a>
#### 	datetimes

The `datetimes` lib provides a set of functions to manipulate and convert dates, times, formats, timestamps in second, millis, …

 In this lib, the default date format is `YYYYMMSS_hhmnss`. But actually, any date format is accepted.

The main features are date / time generation, conversions, operations, comparison.



<a name="dict"></a>
#### 	dict

The `dict` lib provides the `Dict` class, which is a quite complete `key => value` array manipulation helper, where value is either a raw data or a sub tree.

If a `Dict` is created over a given array, the following capabilities are provided :

* get : access to any array leaf, though its path.

  The path is either a string (‘.’ as separator) or an array of keys :

  `get( 'tools.logger.behavior.dateFormat'             )`

  `get( [ 'tools', 'logger', 'behavior', 'dateFormat'] )`

  An optional parameter is available, the `$strict` boolean (Default : `false`) :
  * if it’s `false`, `get` will return `null` if the path is not valid in the `Dict`,
  * if it’s `true` and the path not valid, an `Exception` will be raised.

* `set` : same as get, sets a value anywhere on the tree.

* `set( 'tools.logger.behavior.dateFormat'            , "Ymd_His" )`

  `set( [ 'tools', 'logger', 'behavior', 'dateFormat'], "Ymd_His" )`

  The optional parameter `$strict` is also available (Default : `false`) :

  * if it’s `false`, the whole path is created if needed,
  * if it’s `true` and the full path (except the last leaf) is not present in the tree, an `Exception` is raised.

  This way for instance, one only set can create the path `'tools.logger.behavior.dateFormat'` in an empty array.

* `del` : deletes a path in the `Dict`.

  If the path does not exists, do nothing (no ‘strict’ mode).

* `exists` : `true` if the path exists in the `Dict`, `false` otherwise.

  No ‘strict’ mode.

- `fromFile` : loads a file returning a `key => value` array, and updates the `Dict`.

  `fromFile( $filepath )`

  => the `$filepath` file content remplaces the current `Dict` content.

  `fromFile( $filepath, $atKeyPath )`

  => the `$filepath` file content is added at `$atKeyPath` path in the current `Dict` (non ‘strict’ mode).

* `json` : returns the JSON representation string of the current `Dict`.



In addition, the lib provide common `Dict` features :

* `Dict::splitKey( $key )` : convert a ‘`<key1>.<key2>. … . <key n>`’ string into a keys array (used as `Dict` path).

  *Example* : '`tools.logger.behavior.dateFormat`' => `[ 'tools', 'logger', 'behavior', 'dateFormat']`

* `Dict::joinKey( $key )` : converts a keys array into a formatted path string (mainly used for logging or display).

  *Example* : `[ 'tools', 'logger', 'behavior', 'dateFormat']` => '`tools.logger.behavior.dateFormat`'



<a name="files"></a>
#### 	files

`files` defines the static class `Files`, easing files manipulations :

* `Files::existsFile( $filepath )` : `true` or `false` depending if file or folder exists or not.

* `Files::srm( $filepath )` : deletes a file or a folder. Does nothing if file does not exists.

* `Files::fileInfos( $filepath )` : returns the file system data.

* `Files::folderFiles( $pattern [, $flags = 0 ] )` : returns the files corresponding to the provided pattern.

* `Files::zipFile( $filepath [, $deleteSource = true ] )` : zip the file in its own folder, deletes the source if required.



<a name="resources"></a>
#### 	resources

The static class `Resources` provides, globally, the same features of the `Files` class.

The purpose of the `Resources` class is to manipulate `Module` or `SubModule` resource files in their own resource folder `<rootFolder>/data/<ModuleName>`. The Module or SubModule will be provided a default resource file, or will simply manage its resource files through their base name.

*Example* :

`Resources::existsFile( ‘data.json’ )` will return `true` if the file ‘`data.json`’ exists in the `<rootFolder>/data/<ModuleName>` folder.



<a name="strings"></a>
#### 	strings

This static class provides basic string manipulation features.

* `Strings::strStartsWith( $sourceStr, $searchedStr )` : `true` or `false`.

* `Strings::adjust( $sourceStr, $size = -1, $border = RIGHT, $fill = ' ' )` : returns an adjusted length string, depending on the provided parameters :
  * `$size`              : fix length. Cropped or if required, filled right with the `$fill` character.
  * `$border`          : right, left or center adjustment.
  * `$fill`              : fill character

* `Strings::getJson( $data, $pretty = false )` : returns the JSON string representation of the provided data.

* `Strings::hprint( $data, $prefix = '', $postfix = '' )` : echo of the data in the Web Page.



<a name="userdata"></a>
#### 	userdata

This static class `UserData` eases the user data manipulation, as phone numbers, email addresses, …

* `couldBe` functions : checks if the string is eligible to a given kind of user data :
  * `UserData::couldBeId( $str )` : `true` if alphanum,
  * `UserData::couldBePhone( $str )` : `true` if possible phone number,
  * `UserData::couldBeEmail( $str )` : `true` if probable email address.

* `format` functions :
  * `formatTelNumberForSearch` : removes the non digit characters, and the ‘`0`’ prefix character from a phone number,
  * `formatTelNumberForDisplay` : keeps only, adds a ‘`0`’ prefix character if necessary.



<a name="uuids"></a>
#### 	uuids

Static class `Uuids`, generating uniq ids.

* `Uuids::get( $strong = false, $length = DEFAULT_SIZE )` : generates an uniq id.
  * if `$strong` is `false`, this id is simply based on the current timestamp ; otherwise, the id unicity is reinforced by a strong random number generator.
  * if `$length` is not provided, the default size is `32` characters.

* `Uuids::realrand( $min, $max )` : generates a random integer (strong random algorithm) between `$min` and `$max`.



<a name="webs"></a>
#### 	webs

Static REST requests helper, based on curl.

* `restRequest( $url, $data = null, $header = null, $authData = null, $verbose = false )` :

  returns `[ okFlag, curl_error, http_code, jsonResponse ]`
  
  * if `$data` is not `null` the request is a `POST`, otherwise a `GET`.



------

<a name="additionalTools"></a>
### Additional Tools

In addition to the package structure and the standard tools and helpers, a Command Line Test Helper is provided.

 The purpose of this helper is to ease the implementation and the tests of the features currently implemented without an actual production environment.



<a name="commandLineTester"></a>
#### 	Command Line Tester

It’s a file provided with each sample package : `CommandLineTester.php`

The Command Line Tester is called this way :

```SHELL
> php CommandLineTester.php -f --test=<testNb>
```

 The behavior is the following :

* the provided test number is checked, in order to verify a corresponding test function has been defined,

* in such case, the function is executed.

The tested `Module` and/or `SubModule` instances are created while the `CommandLineTester` is instantiated. The defined test functions are setup in an array. This way it’s easier to add and access the test functions.

Most of the time the sample package’s `CommandLineTester` contains the key tests used to verify the proper implementation behavior (authentication, single API request, user data management, …).



------
