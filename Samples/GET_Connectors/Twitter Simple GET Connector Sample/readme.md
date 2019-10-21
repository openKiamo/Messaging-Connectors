# Kiamo Messaging Connector Sample

## Twitter



| Date    | 20191010  |
| :------ | --------- |
| Version | v1.0.0    |
| Author  | S.Iniesta |



------


[Introduction](#introduction)

&nbsp;&nbsp;&nbsp;&nbsp;[Purpose](#purpose)

&nbsp;&nbsp;&nbsp;&nbsp;[Description](#description)

[Messaging Connector Class](#messagingConnectorClass)

&nbsp;&nbsp;&nbsp;&nbsp;[Design](#design)

&nbsp;&nbsp;&nbsp;&nbsp;[Main functions](#mainFunctions)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[getName](#getname)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[fetch](#fetch)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[send](#send)

[Messaging Manager Class](#messagingManagerClass)

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

This folder contains the sample of a Kiamo Messaging Connector for **Twitter**.
This sample can be used as a simple illustration or as support to a more specific implementation.

The base Messaging Connector documentation is “Messaging Connector Development”, provided in the Kiamo installation package.



<a name="description"></a>
#### 	Description

The Messaging Connector `<MessagingConnector>` must be located in the `<kiamo>data\userfiles\class\Messaging\Connector` folder.

The two main Messaging Connector class methods are :

* `fetch` : get messages from the external Messaging Service, and push it in Kiamo,

* `send`  : send a message from a Kiamo agent to an external customer.

  The actual implementation of such features, and all the related mechanisms, are externalized in a secondary class present in the connector's file, `MessagingManager`.

To ease the implementation other helpers and tools classes are present, as a Logger, a Curl requests helper, ...

------



<a name="messagingConnectorClass"></a>
### Messaging Connector Class

<a name="design"></a>
#### 	Design

The **Twitter** Connector Sample implementation follows the guidelines of the Kiamo dedicated documentation.

In addition, this connector can generate logs and use a cache mechanism in order to avoid useless external API calls.
For this reason, it can create a `log` and a `data` folders, where the dedicated logs and resources will be managed.


<a name="mainFunctions"></a>
#### 	Main functions

<a name="getname"></a>
##### 		getName

Returns the name of the connector, to be displayed in the Kiamo management interface.

It’s set in the connector’s configuration, value “`self.service`”.



<a name="fetch"></a>
##### 		fetch

The goal of this function is to get the unread messaging messages and to push it into Kiamo.

Basically, it will call its `MessagingManager` instance method `readMessages`, and map them as the Kiamo expected format.

In addition to that when possible, it will get from / set or update in Kiamo a variable in order to optimize the Web Service number of requests and limit the number of message to check (as `lastMessageId` or `lastReadMessageDate` for instance). This variable is passed to the `readMessages` method.



<a name="send"></a>
##### 		send

The goal of this function is to send a message from a Kiamo agent to an external customer.

Basically, it will call its `MessagingManager` instance method `sendMessage`.



-----



<a name="messagingManagerClass"></a>
### Messaging Manager Class

The `MessagingManager` class purpose is to externalize from the connector the implementation all the Web Service API authentication, requests, and related mechanisms.

It will use the same configuration file and log in the same daily log file than the parent's connector instance.

The `MessagingManager` holds the following main features :

* grant access and keep alive the session with the external service,
* implement the required Web Service requests to return the unread message to the connector, and send a message to a customer,
* map the external entities with the connector’s expected format (users, messages, conversations, …),
* manage the resource files and the cache, when used,
* manage the configured runtime behavior (duration limits, pagination, ...),
* ideally, optimize the number of Web Service requests and the stored data use.



<a name="webServiceApiAccessAndSession"></a>
#### 	Web Service API Access and Session

The `MessagingManager` :

* builds the access context (urls, login, password, token, …),
* get, check, update and store a session token (if this token has a validity duration, it’s stored in a flat file in order to avoid useless external API calls).



<a name="webServiceApiRequestManagement"></a>
#### 	Web Service API Request Management

The Web Service API requests management is composed of two sections :

* the external requests tools (url builder, context builder, requester, error response management, ...),

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

It’s recommended to use these three last files only for debug purpose.

The main resource file will be created automatically if needed, and will contain the cache if required.



<a name="resources"></a>
##### 		Resources

During the read / send message method execution flow, data will be stored in those files depending on the configuration. If the related resource is enabled, the corresponding file will be enriched with the new  conversations, messages, users, ...



<a name="cache"></a>
##### 		Cache

The cache may be required to map customer ids and conversation ids for a given period of time, either because it’s the simpler way to make this link, or to avoid useless external API calls.

 The cache configuration is composed of :

* an `enabled` flag : the cache is used, or not,
* a `checkEveryInSecs` integer value : the minimum number of seconds between two cache cleanups,
* an `expirationInSecs` integer value : the duration before a cache data is considered expired.

The cache management is automated through the methods “`addToCache`”, “`getFromCache`” and “`cleanCache`”.



<a name="runtimeBehavior"></a>
#### 	Runtime Behavior

During the read / send message method execution flow, the runtime configuration will be used to drive the algorithm decisions, for instance, the number of items per pagination page, the date/time formats, the encoding conversion, ...

This behavior is build and loaded in RAM only one time per connector instantiation, for efficiency reasons.

------



##  Command Line Tester Class

The Command Line Tester is a tool that is designed to :

* be used through a command line shell,
* help the integration and the test of the main features of a connector, step by step.

The purpose is to help the definition in very few lines, and the execution though a simple cmd shell line, of any kind of connector test.

This class is only instantiated if the php file is executed by php using a command line shell, otherwise it's ignored.

Basically, you will find the following kind of tests :

* the external Messaging Environment authentication test (get a session token),
* an external Messaging Environment query string build,
* the main external Messaging Environment Web Service function requests (read messages, send message, ...),
* the test of the mapping between raw returns and Kiamo items or lists,
* the end to end connector main functions tests,
* ...

The Command Line Tester is called this way :

```SHELL
> php <ConnectorName>.php -f --test=<testNb>
```

 The behavior is the following :

* the provided test number is checked, in order to verify a corresponding test function has been defined,

* in such case, the function is executed.

It's highly recommended :

* to have a look to these tests in order to understand the very few key entry points of the connector sample
* to customize and use them to test the initial steps of an integration, before any code modification in the connector's module,
* to add and run any test you need to verify your connector's customization.
