# Kiamo Messaging Connector Sample

## Command Line Tester



| Date    | 20191028  |
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

&nbsp;&nbsp;&nbsp;&nbsp;[Web Service API Request Management](#webServiceApiRequestManagement)

[Command Line Tester Class](#commandLineTesterClass)



------



<a name="introduction"></a>
### Introduction

<a name="purpose"></a>
#### 	Purpose

This folder contains the sample of a Kiamo Messaging Connector that can simulate user conversations from a Command Line shell.

The base Messaging Connector documentation is “Messaging Connector Development”, provided in the Kiamo installation package.



<a name="description"></a>
#### 	Description

The Messaging Connector `<MessagingConnector>` must be located in the `<kiamo>data\userfiles\class\Messaging\Connector` folder.

The two main Messaging Connector class methods are :

* `fetch` : get messages from the external Messaging Service, and push it in Kiamo,

* `send`  : send a message from a Kiamo agent to an external customer.

  The actual implementation of such features, and all the related mechanisms, are externalized in a secondary class present in the connector's file, `MessagingManager`.

To ease the implementation other helpers and tools classes are present, as a Logger, ...

------



<a name="messagingConnectorClass"></a>
### Messaging Connector Class

<a name="design"></a>
#### 	Design

The **Command Line Tester** Connector Sample implementation follows the guidelines of the Kiamo dedicated documentation.

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

In the current case, the `MessagingManager` is quite simple, as it does not actually calls an external service, but simulates it. It holds the following main features :

* implement the simulated Web Service requests to return the unread message to the connector, and send a message to a customer,
* map the external entities with the connector’s expected format (users, messages, conversations, …),
* manage the resource files and the cache (where are actually stored the agent's and customers' messages),
* manage the configured runtime behavior (simulated agent, ...).



<a name="webServiceApiRequestManagement"></a>
#### 	Web Service API Request Management

The Web Service API requests management is composed of two sections :

* the conversation simulation tools,

* the connector entry points (`readMessages` and `sendMessage`).

The tools responsibilities are to save messages and user data in a local file, and return to the connector entry point methods valid responses.

The connectors entry points responsibilities are to receive the connector’s requests, treat, sort, filter, format the data returned by the tools and respond to the connector with a valid response (OK, KO, valid and relevant items list or empty list for instance).

In addition, depending on the configuration, they will apply the runtime behavior items and stored the required data in the resource and cache files.



------



<a name="commandLineTesterClass"></a>
### Command Line Tester Class

The Command Line Tester is a tool that is designed to :

* be used through a command line shell,
* help the integration and the test of the main features of a connector, step by step,
* in the current case, provide basic verbs to simulate an external messaging application (get messages, customer send message, agent replies, ...).

The purpose is to help the definition in very few lines, and the execution though a simple cmd shell line, of any kind of connector test.

This class is only instantiated if the php file is executed by php using a command line shell, otherwise it's ignored.

The Command Line Tester is called this way :

```SHELL
> php <ConnectorName>.php --<verb> [--option1=xxx --option2=yyy ...]
  where verbs are :
  --get [--date=YYYYMMDD_hhmmss]
    ==> get all messages [not older than given date],
  --agent --name=<username> --message="<The agent message>"
    ==> simulates an agent reply to a given user (the customer must start the conversation first),
  --user --name=<username> --message="<The customer message>"
    ==> simulates a customer message to the agent (the page),
  --purge
    ==> purge all messages and the related customers (local file purge, not in Kiamo),
  --test=<ID>
    ==> execute the test <ID>, where <ID> is between 00 and 99.
```

 The behavior is the following :

* the CommandLineTester checks the verb and the option provided,
* if a valid verb is passed, the related function is executed with all the provided option values,
* otherwise, the usage() function is called.

It's highly recommended :

* to have a look to these tests and functions in order to understand the very few key entry points of the connector sample
* to customize and use them to test the initial steps of an integration, before any code modification in the connector's module,
* to add and run any test you need to verify your connector's customization.
