# Kiamo Messaging Connector Samples

## Set Description



| Date    | 20191022  |
| :------ | --------- |
| Version | v2.0.0    |
| Author  | S.Iniesta |



------


[Introduction](#introduction)

&nbsp;&nbsp;&nbsp;&nbsp;[Purpose](#purpose)

&nbsp;&nbsp;&nbsp;&nbsp;[Description](#description)

[Design](#design)

[Main Helpers](#mainHelpers)

&nbsp;&nbsp;&nbsp;&nbsp;[Logger](#logger)

&nbsp;&nbsp;&nbsp;&nbsp;[Webs](#webs)

&nbsp;&nbsp;&nbsp;&nbsp;[resources](#resources)

&nbsp;&nbsp;&nbsp;&nbsp;[Command Line Tester](#commandLineTester)



------



<a name="introduction"></a>
### Introduction

<a name="purpose"></a>
####  Purpose

The goal of this set is to provide Messaging Connector Samples to a development team, based on a very simple design.

Each sample can be used as a simple illustration, or as support to a more specific implementation.

<u>Note</u> : In each sample is present, at the end of the file, the `CommandLineTester` class, which helps the tests and integration phases by allowing the developper to run methods of the connector via a simple Command Line Shell. The best way to discover and appropriate a connector may be to read and, when the connector is set up, to run the basic connector's main components tests located in the `CommandLineTester` class.



<a name="description"></a>
####  Description

A messaging connector's purpose is to get unread messages sent by customers, return them to Kiamo on demand, and to send agents' responses to the customers.

A given CRM connector sample is composed of :

* the implementation of the Messaging connector, which is usually composed of :
  * the main Kiamo connector class,
  * the messaging manager class, which creates and maintain a valid session with the external API, and communicates with the external services to read and send messages,
  * some helper tools, as a logger for instance.
* the `readme.md` file, the connector's documentation.


Each sample manages the connection with a specific external Messaging API.

The reference Kiamo documentation to implement a connector is unchanged : « `Development of Messaging connectors` ».



The Kiamo deployment folder for a messaging connector is :

`<Kiamo Folder>/data/userfiles/class/Messaging/Connector`


The sample code and structure are quite easy to read and understand. The documentation provides additional details to understand the global idea and implementation particularities, for a better appropriation.



The current Messaging connector samples (october 2019) are the following :

* a **Facebook** connector sample,

* a **Twitter** connector sample,

* an **Orange SMS+** *(Contact Everyone)* connector sample.



------

<a name="design"></a>
### Design

The two main Messaging Connector class methods are :

* `fetch` : get messages from the external Messaging Service, and push it in Kiamo,

* `send`  : send a message from a Kiamo agent to an external customer.

  The actual implementation of such features, and all the related mechanisms, are externalized in a secondary class present in the connector's file, `MessagingManager`. The main `MessagingManager` entry points are the `readMessages` and `sendMessage` methods.

To ease the implementation other helpers and tools classes are present, as a Logger, a Curl requests helper, ...


<a name="mainHelpers"></a>
### Main Helpers


<a name="logger"></a>
####  Logger

The `Logger` class does not have to be described in detail.

The daily log files will be written in the connector's root folder.

* The logs are written in daily files : `YYYYMMDD.log`

* The main function is `log( $str, [ $level, $method, $actionId, $indentLevel ] )` :
  * `$str` the string to log,
  * `$level`, the log level (default value, `Logger::LOG_DEBUG`),
  * `$method`, name of the caller method. It’s recommanded to use the PHP « magic constant » `__METHOD__`.
  * `$indentLevel` : int value indicating the number of left space to add before the log string (just after the method name block). Can be used to ease the logs readability in specific cases (but ignore it otherwise).

* Logs lines examples :

```
[20190429_102557][INFO ][MyModule::__construct ] ---------
[20190429_102557][INFO ][MyModule::__construct ] INIT : OK
```

where :

* 1st bloc is the log date

* 2nd is the log level

* 3rd the caller method

* then the actual log.



<a name="webs"></a>
####  Webs

Static REST requests helper, based on curl.

* `restRequest( $url, $data = null, $header = null, $authData = null, $verbose = false )` :

  returns `[ okFlag, curl_error, http_code, jsonResponse ]`

  * if `$data` is not `null` the request is a `POST`, otherwise a `GET`.


<a name="resources"></a>
#### 	resources

Sometimes it's necessary to store some runtime or cache data. To ease this resource management, the static class `Resources` provides a simple way is to manipulate a given connector's resource files in his own resource folder `./data/<ModuleName>`. The connector uses a default resource file, `<ModuleName>.json`.

The main `Resources` static methods are :

* `existsDefaultDataFile()` : the path and name are automatically resolved,
* `readDefaultDataFile()` : read the json file, returns an associative key/value array,
* `writeDefaultDataFile( $arr )` : save the associative key/value array parameter as json.


<a name="commandLineTester"></a>
####  Command Line Tester

It’s a file provided with each sample package : `CommandLineTester.php`

The Command Line Tester is called this way :

```SHELL
> php CommandLineTester.php -f --test=<testNb>
```

 The behavior is the following :

* the provided test number is checked, in order to verify a corresponding test function has been defined,

* in such case, the function is executed.

The tested connector instance is created while the `CommandLineTester` is instantiated. The defined test functions are setup in an array. This way it’s easier to add and access the test functions.

Most of the time the sample package’s `CommandLineTester` contains the key tests used to verify the proper implementation behavior (authentication, single API request, user data management, …).
