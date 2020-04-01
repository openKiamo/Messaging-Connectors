# Kiamo Messaging Connector Samples

## Orange SMS Requirements



| Date    | 20191021  |
| :------ | --------- |
| Version | v1.0.0    |
| Author  | S.Iniesta |



------


[Introduction](#introduction)

&nbsp;&nbsp;&nbsp;&nbsp;[Purpose](#purpose)

[Procedure](#procedure)

&nbsp;&nbsp;&nbsp;&nbsp;[Prerequisites](#prerequisites)

&nbsp;&nbsp;&nbsp;&nbsp;[Orange SMS Account](#orangeSmsAccount)

&nbsp;&nbsp;&nbsp;&nbsp;[Connector](#connector)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Configuration](#configuration)


------



<a name="introduction"></a>
### Introduction

<a name="purpose"></a>
####  Purpose

This document describes the requirements and the minimal configuration in order to build and connect a Kiamo Messaging Connector with Orange SMS (Contact Everyone).

The Kiamo Messaging Connector purpose is to read and respond to customers' messages, sent on an official SMS phone number.



***<u>Important</u>*** : this procedure is provided as an example. It's working the day this document is released, but we cannot guaranty Orange Business Contact Everyone wont change the way to use their APIs in the future.



------



<a name="procedure"></a>
### Procedure

<a name="prerequisites"></a>
#### Prerequisites

There are three prerequisites :

1. Create (or use) an Orange SMS account (Orange Business Contact Everyone)
2. Configure the Orange SMS Connector

Those steps are detailed bellow.



<a name="orangeSmsAccount"></a>
#### Orange SMS Account

Contact **[Orange Business Contact Everyone](https://www.orange-business.com/fr/produits/contact-everyone)** to get an Orange SMS account.

This account will be used to configure the Connector.



***<u>Important</u>*** :  Note the following data :

* `sender` : the phone number where the SMS will be sent. It's a string without the '`0`' prefix, but with the '`33`' (France prefix), as '`33XXXXXXXXX`'.
* `SMSPrefixKeyword` : the keyword used by the customers as prefix in their SMS, to indicate the SMS routage rule.
* `Login` : the account login (usually, an email address).
* `Password` : the account password.

They will be used to configure the connector.



<a name="connector"></a>
#### Connector

<a name="configuration"></a>
##### Configuration

Deploy your connector package on `<kiamoPath>/data/userfiles/class/Messaging/Connector`.

On the connector's implementation folder, edit your dedicated configuration file (`conf/u_<connectorName>.php`) and set the required credentials :

![Connector Configuration](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/OrangeSMS%20Simple%20Connector%20Sample/OrangeSMSRequirements/data/OrSMS_0301_ConnectorConfiguration.png)

