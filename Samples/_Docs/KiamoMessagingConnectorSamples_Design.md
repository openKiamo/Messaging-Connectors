# Kiamo Messaging Connector Samples

## Design



| Date    | 20190722  |
| :------ | --------- |
| Version | v1.0.0    |
| Author  | S.Iniesta |



------


[Introduction](#introduction)

&nbsp;&nbsp;&nbsp;&nbsp;[Purpose](#purpose)

&nbsp;&nbsp;&nbsp;&nbsp;[Description](#description)

[Messaging Connector](#messagingConnector)

&nbsp;&nbsp;&nbsp;&nbsp;[Design](#design)

&nbsp;&nbsp;&nbsp;&nbsp;[Main functions](#mainFunctions)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[getName](#getname)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[fetch](#fetch)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[send](#send)

[Messaging Manager Submodule](#messagingManagerSubmodule)

&nbsp;&nbsp;&nbsp;&nbsp;[Web Service API Access and Session](#webServiceApiAccessAndSession)

&nbsp;&nbsp;&nbsp;&nbsp;[Web Service API Request Management](#webServiceApiRequestManagement)

&nbsp;&nbsp;&nbsp;&nbsp;[Entities Mapping](#entitiesMapping)

&nbsp;&nbsp;&nbsp;&nbsp;[Resource Files and Cache](#resourceFilesAndCache)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Configuration](#configuration)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Resources](#resources)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Cache](#cache)

&nbsp;&nbsp;&nbsp;&nbsp;[Runtime Behavior](#runtimeBehavior)



------



<a name="introduction"></a>
### Introduction

<a name="purpose"></a>
#### 	Purpose

The goal of a Kiamo Messaging Connector Sample is to provide a example of implementation of such a plugin. It’s implemented both simply and quite efficiently, allowing both production and debug modes.

The base Messaging Connector documentation is “Messaging Connector Development”, provided in the Kiamo installation package.

Each sample is based on the same structure and toolkit. This structure and the toolkit are described in the “KiamoMessagingConnectorSample_Toolkit” documentation.

 The purpose of this document is to describe the design of the link between the actual Messaging Connector and the external Web Services APIs.



<a name="description"></a>
#### 	Description

In addition to the toolkit package (configuration, resources, tools, logs, Command Line Tester, …) come :

* The actual Messaging Connector `<MessagingConnector>`

  It’s located in the `<kiamo>data\userfiles\class\Messaging\Connector` folder.

  It extends the Module tool from the tookit, which grants it logs, configuration and resource management, and sub module extensions capabilities.

  The two main Messaging Connector methods are :

  ​	o  `fetch` : get messages from the external Messaging Service, and push it in Kiamo,

  ​	o   `send`  : send a message from a Kiamo agent to an external customer.

  The actual implementation of such features, and all the related mechanisms, are externalized in a common sub module, `MessagingManager`.

* The implementation package is a folder named `<MessagingConnector>` and located in the same folder than the messaging connector.

  It contains all the toolkit and its structure (conf, logs, resources, tools, …) and the `MessagingManager`.

* The `MessagingManager` which contains the actual implementation of the external Web Services APIs calls and all related mechanisms.



------



<a name="messagingConnector"></a>
### Messaging Connector

<a name="design"></a>
#### 	Design

A Messaging Connector implementation follows the guidelines of the Kiamo dedicated documentation.

 In addition, a Messaging Connector Sample is based on the common Samples toolkit. For this reason it must :

* declare the dependencies with the package (include the `autoloader` and the `MessagingManager` definitions),
* extend the `Module` class (which grant it conf, logs and resource management),
* build it `MessagingManager SubModule` instance.

As it’s a `Module`, the configuration, resources and logs capabilities are complete and can be used immediately (`$this->log( … )`, `$this->getConf( … )`, …).



The configuration, logs and resources are located in the `<MessagingConnector>` sub folder. See the Toolkit documentation for further details.



The configuration of the connector is mainly based on two files :

* the global configuration file : `_config.php` :

  => the `user.items` array must contain the item `<MessagingConnector>`

  This way the `ConfigurationManager` tool will know how to access the connector’s conf.

* the specific Messaging Connector configuration file : `u_<MessagingConnector>.php` :

  This file will contain the connector’s configuration, as :

  * `self`            : description items of the Messaging Connector,
  * `runtime`      : runtime data setup (pagination, duration limits, …),
  * `resources`  : written data and cache (customer ids, lastMessageDate, conversations, …),
  * `accessData `: all the data required to access the external service : urls, login, password, secret key or token, …



<a name="mainFunctions"></a>
#### 	Main functions

<a name="getname"></a>
##### 		getName

Returns the name of the connector, to be displayed in the Kiamo management interface.

Usually, it’s set in the connector’s conf file, value “`self.service`”.



<a name="fetch"></a>
##### 		fetch

The goal of this function is to get the unread messaging messages and to push it into Kiamo.

 Basically, it will call its `MessagingManager` instance method `readMessages`, and map them as the Kiamo expected format.

In addition to that when possible, it will get from/ set or update in Kiamo a variable in order to optimize the Web Service number of requests and limit the number of message to check (as `lastMessageId` or `lastReadMessageDate` for instance). This variable is passed to the `readMessages` method.

It also decorates the logs with an unique action id corresponding to this call.



<a name="send"></a>
##### 		send

The goal of this function is to send a message from a Kiamo agent to an external customer.

Basically, it will call its `MessagingManager` instance method `sendMessage`.

 It also decorates the logs with an unique action id corresponding to this call.



-----



<a name="messagingManagerSubmodule"></a>
### Messaging Manager Submodule

The `MessagingManager` sub module purpose is to externalize from the connector the implementation of the Web Service API authentication, requests, and all the related mechanisms.

 As it’s a connector’s `SubModule`, its configuration manager, logger and resource manager are the same than the parent connector. It will use the same configuration file and log in the same daily log file, for instance.

 The `MessagingManager` holds the following main features :

* grant access and keep alive the session with the external service,
* implement the required Web Service requests to return the unread message to the connector, and send a message to a customer,
* map the external entities with the connector’s expected format (users, messages, conversations, …),
* manage the resource files and the cache, when used,
* manage the configured runtime behavior (duration limits, pagination, …),
* ideally, optimize the number of Web Service requests and the stored data use.



<a name="webServiceApiAccessAndSession"></a>
#### 	Web Service API Access and Session

Depending on the external Web Service specificities, the `MessagingManager` will :

* build the access context (urls, login, password, token, …),
* get, check, update and store a session token (if this token has a validity duration, it’s stored in a flat file in order to avoid useless WS API calls).



<a name="webServiceApiRequestManagement"></a>
#### 	Web Service API Request Management

The Web Service API requests management is composed of two sections :

* the generic external requests tools (url builder, context builder, requester, error response management, …),

* the connector entry points (`readMessages` and `sendMessage`).

The tools responsibilities are to return to the connector entry point methods valid responses or clear errors from the external APIs, hiding the errors management, the session token renew mechanism, the context building, etc.

The connectors entry points responsibilities are to receive the connector’s requests, treat, sort, filter, format the data returned by the tools and respond to the connector with a valid response (OK, KO, valid and relevant items list or empty list for instance).

In addition, depending on the configuration, they will apply the runtime behavior items and stored the required data in the resource and cache files.



<a name="entitiesMapping"></a>
#### 	Entities Mapping

The entities mapping is usually managed by the read/send message methods.

 To ease the mapping, the methods “`buildRecord`” (`buildMessageRecord`, `buildUserRecord` for instance) are used to map the external Web Service response entities into a standard internal entity format (which contains the subset of data that will be used to build the Kiamo response, but usually more than that).



<a name="resourceFilesAndCache"></a>
#### 	Resource Files and Cache

<a name="configuration"></a>
##### 		Configuration

Depending on the context and the connector’s configuration, the `MessagingManager` will store the following resource files :

* `<MessagingConnector>.json` file : will contain the access token and the cache, if required.
* `customers.json` file : usually, only for debug purpose : the “database” of all customers having contacted this messaging service.
* `conversations.json` file : usually, only for debug purpose : the “database” of all conversations between the customers and the agent.
* `cursors.json file` : usually, only for debug purpose : the main cursors (last read or sent message id, or timestamp, …).

 Most of the time it’s recommended to use these three last files only for debug purpose.

The main resource file will be created automatically if needed, and will contain the cache if required.



<a name="resources"></a>
##### 		Resources

During the read / send message method execution flow, data will be stored in those files depending on the configuration. If the related resource is enabled, the corresponding file will be enriched with the new  conversations, messages, users, …



<a name="cache"></a>
##### 		Cache

The cache may be required to map customer ids and conversation ids for a given period of time, either because it’s the simpler way to make this link, or to avoid useless WS API calls.

 The cache configuration is composed of :

* an `enabled` flag : the cache is used, or not,
* a `checkEveryInSecs` integer value : the minimum number of seconds between two cache cleanups,
* an `expirationInSecs` integer value : the duration before a cache data is considered expired.

 The cache management is automated through the methods “`addToCache`”, “`getFromCache`” and “`cleanCache`”.



<a name="runtimeBehavior"></a>
#### 	Runtime Behavior

During the read / send message method execution flow, the runtime configuration will be used to drive the algorithm decisions, for instance, the number of items per pagination page, the date/time formats, the encoding conversion, …

 Usually, this behavior is build and loaded in RAM only one time per connector instantiation, for efficiency reasons.



------

