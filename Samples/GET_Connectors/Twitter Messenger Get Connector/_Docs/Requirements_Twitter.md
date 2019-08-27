# Kiamo Messaging Connector Samples

## Twitter Requirements



| Date    | 20190801  |
| :------ | --------- |
| Version | v1.0.0    |
| Author  | S.Iniesta |



------


[Introduction](#introduction)

&nbsp;&nbsp;&nbsp;&nbsp;[Purpose](#purpose)

[Procedure](#procedure)

&nbsp;&nbsp;&nbsp;&nbsp;[Prerequisites](#prerequisites)

&nbsp;&nbsp;&nbsp;&nbsp;[Organization page](#organizationPage)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Creation](#creation)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Direct Messages Setup](#directMessagesSetup)

&nbsp;&nbsp;&nbsp;&nbsp;[Developer Account](#developerAccount)

&nbsp;&nbsp;&nbsp;&nbsp;[Application](#application)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Creation](#creation2)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Permissions](#permissions)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Access Keys and Tokens](#accessKeysAndTokens)

&nbsp;&nbsp;&nbsp;&nbsp;[Connector](#connector)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Configuration](#configuration)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Get the User Id](#getTheUserId)

------



<a name="introduction"></a>
### Introduction

<a name="purpose"></a>
####  Purpose

This document describes the requirements and the minimal configuration on Twitter, in order to build and connect a Kiamo Messaging Connector.

The Kiamo Messaging Connector purpose is to read and respond to private messages, sent on an official Twitter organization page.



***<u>Important</u>*** : this procedure is provided as an example. It's working the day this document is released, but we cannot guaranty Twitter wont change the way to use their APIs in the future.



------



<a name="procedure"></a>
### Procedure

<a name="prerequisites"></a>
#### Prerequisites

There are three prerequisites :

1. Create (or use) an official Twitter Organization Page
3. Create (or use) a Twitter Developer Account
3. Create and configure a Twitter App

Those three steps are detailed bellow.



<a name="organizationPage"></a>
#### Organization page

<a name="creation"></a>
##### Creation

Create a **[Twitter page](http://www.twitter.com)**.

![Page Creation](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Twitter%20Messenger%20Get%20Connector/_Docs/data/TW_0101_CreatePage.png)



<a name="directMessagesSetup"></a>
##### Direct Messages Setup

In order to receive customers' messages, you must enable direct messages from anyone :

* on the left column, click on ![](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Twitter%20Messenger%20Get%20Connector/_Docs/data/TW_More.png),
* on the left column, click on "**Settings and privacy**",
* on the center column, click on "**Privacy and safety**",
* on the right column, toggle on the "**Direct Messages**" => "Receive messages from anyone".

![Direct Messages Setup](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Twitter%20Messenger%20Get%20Connector/_Docs/data/TW_0102_CreatePage.png)



<a name="developerAccount"></a>
#### Developer Account

Create a **[Twitter developer account]([https://developer.twitter.com])**.

This account will be used to create the required Twitter app.



<a name="application"></a>
#### Application

<a name="creation2"></a>
##### Creation

Create a Twitter application on your **[developer account](https://developers.Twitter.com/apps)**.



***<u>Important</u>*** :  Note the `AppName`, it will be used to configure the connector.



<a name="permissions"></a>
##### Permissions

On the **Permissions** tab, enable the "**Read, write and Direct Messages**" option :

![App Permissions](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Twitter%20Messenger%20Get%20Connector/_Docs/data/TW_0201_CreateApp.png)



<a name="accessKeysAndTokens"></a>
##### Access Keys and Tokens

On the **Keys and tokens** tag, generate the required keys and tokens :

![App Keys and Tokens](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Twitter%20Messenger%20Get%20Connector/_Docs/data/TW_0202_CreateApp.png)

***<u>Important</u>*** :  Note the `APIKey`, `APISecretKey`, `AccessToken` and `AccessTokenSecret`, they will be used to configure the connector.



<a name="connector"></a>
#### Connector

<a name="configuration"></a>
##### Configuration

Deploy your connector package on `<kiamoPath>/data/userfiles/class/Messaging/Connector`.

On the connector's implementation folder, edit your dedicated configuration file (`conf/u_<connectorName>.php`) and set the application credentials :

![Connector Configuration](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Twitter%20Messenger%20Get%20Connector/_Docs/data/TW_0301_ConnectorConfiguration.png)

**<u>Note</u>** : at this stage, you won't be able to fill the `userId` field. The procedure to get it is described bellow.



<a name="getTheUserId"></a>
##### Get the User Id

There are several ways to get your own Twitter User Id :

* search "*get twitter id*" on the internet. Several web sites provide the Twitter User Id from the page name,

* use the package provided Command Line Tester :

  * either using the users show API, through the test #04 :

    * edit the Command Line Tester,

    * enter your Twitter page name :

      ![Command Line Tester : screen_name](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Twitter%20Messenger%20Get%20Connector/_Docs/data/TW_0401_GetUserId.png)

    * run the test #04 of the Command Line Tester on a Command Line Console :

      ![Command Line Tester : run](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Twitter%20Messenger%20Get%20Connector/_Docs/data/TW_0402_GetUserId.png)

  * or using the read messages API, through the test #03 :

    * send a direct message to yourself on your Twitter account,
    * run the test #03 of the Command Line Tester on a Command Line Console, and read the user id on the call result.

  Those two last ways will allow you to verify your access to the Twitter API via your app is OK.



***<u>Important</u>*** :  finalize the connector configuration by setting up the `UserId`.

