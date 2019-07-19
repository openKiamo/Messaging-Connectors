# Kiamo Messaging Connector Samples

## Design



| Date    | 20190712  |
| :------ | --------- |
| Version | v1.0.0    |
| Author  | S.Iniesta |



------



[TOC]

------



### Introduction

#### 	Purpose

The goal of a Kiamo Messaging Connector Sample is to provide a example of implementation of such a plugin. It’s implemented both simply and quite efficiently, allowing both production and debug modes.

The base Messaging Connector documentation is “Messaging Connector Development”, provided in the Kiamo installation package.

Each sample is based on the same structure and toolkit. This structure and the toolkit are described in the “KiamoMessagingConnectorSample_Toolkit” documentation.

 The purpose of this document is to describe the design of the link between the actual Messaging Connector and the external Web Services APIs.



#### 	Description

In addition to the toolkit package (configuration, resources, tools, logs, Command Line Tester, …) come :

* The actual Messaging Connector <MessagingConnector>

  It’s located in the <kiamo>data\userfiles\class\Messaging\Connector folder.

  It extends the Module tool from the tookit, which grants it logs, configuration and resource management, and sub module extensions capabilities.

  The two main Messaging Connector methods are :

  ​	o   fetch : get messages from the external Messaging Service, and push it in Kiamo,

  ​	o   send  : send a message from a Kiamo agent to an external customer.

  The actual implementation of such features, and all the related mechanisms, are externalized in a common sub module, MessagingManager.

* The implementation package is a folder named <MessagingConnector> and located in the same folder than the messaging connector.

  It contains all the toolkit and its structure (conf, logs, resources, tools, …) and the MessagingManager.

* The MessagingManager which contains the actual implementation of the external Web Services APIs calls and all related mechanisms.

  

------



### Messaging Connector

#### 	Design

A Messaging Connector implementation follows the guidelines of the Kiamo dedicated documentation.

 In addition, a Messaging Connector Sample is based on the common Samples toolkit. For this reason it must :

* declare the dependencies with the package (include the autoloader and the MessagingManager definitions),
* extend the Module class (which grant it conf, logs and resource management),
* build it MessagingManager SubModule instance.

As it’s a Module, the configuration, resources and logs capabilities are complete and can be used immediately ($this->log( … ), $this->getConf( … ), …).



The configuration, logs and resources are located in the <MessagingConnector> sub folder. See the Toolkit documentation for further details.

 

The configuration of the connector is mainly based on two files :

* the global configuration file : _config.php :

  => the user.items array must contain the item <MessagingConnector>

  This way the ConfigurationManager tool will know how to access the connector’s conf.

* the specific Messaging Connector configuration file : u_<MessagingConnector>.php :

  This file will contain the connector’s configuration, as :

  * self : description items of the Messaging Connector,
  * runtime : runtime data setup (pagination, duration limits, …),
  * resources : written data and cache (customer ids, lastMessageDate, conversations, …),
  * accessData : all the data required to access the external service : urls, login, password, secret key or token, …



#### 	Main functions

##### 		getName

##### 		fetch

##### 		send

### Messaging Manager Submodule

#### 	Web Service API Access and Session

#### 	Web Service API Request Management

#### 	Entities Mapping

#### 	Resource Files and Cache

##### 		Configuration

##### 		Resources

##### 		Cache

#### 	Runtime Behavior



------

