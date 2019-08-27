# Kiamo Messaging Connector Samples

## Facebook Requirements



| Date    | 20190730  |
| :------ | --------- |
| Version | v1.0.0    |
| Author  | S.Iniesta |



------


[Introduction](#introduction)

&nbsp;&nbsp;&nbsp;&nbsp;[Purpose](#purpose)

[Procedure](#procedure)

&nbsp;&nbsp;&nbsp;&nbsp;[Prerequisites](#prerequisites)

&nbsp;&nbsp;&nbsp;&nbsp;[Developer Account](#developerAccount)

&nbsp;&nbsp;&nbsp;&nbsp;[Organization page](#organizationPage)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Creation](#creation)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Page Id](#pageId)

&nbsp;&nbsp;&nbsp;&nbsp;[Application](#application)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Creation](#creation2)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[User Token](#userToken)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Messenger Product](#messengerProduct)

&nbsp;&nbsp;&nbsp;&nbsp;[Connector](#connector)

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Configuration](#configuration)


------



<a name="introduction"></a>
### Introduction

<a name="purpose"></a>
####  Purpose

This document describes the requirements and the minimal configuration on Facebook, in order to build and connect a Kiamo Messaging Connector.

The Kiamo Messaging Connector purpose is to read and respond to private messages, sent on an official Facebook organization page.



***<u>Important</u>*** : this procedure is provided as an example. It's working the day this document is released, but we cannot guaranty Facebook wont change the way to use their APIs in the future.



------



<a name="procedure"></a>
### Procedure

<a name="prerequisites"></a>
#### Prerequisites

There are three prerequisites :

1. Create (or use) a Facebook Developer Account
2. Create (or use) an official Facebook Organization Page
3. Create and configure a Facebook App

Those three steps are detailed bellow.



<a name="developerAccount"></a>
#### Developer Account

Create a **[Facebook developer account](https://developers.facebook.com)**.

This account will be used to create the required Facebook page and app.



<a name="organizationPage"></a>
#### Organization page

<a name="creation"></a>
##### Creation

Create a **[Facebook organization page](http://www.facebook.com/pages/create)** : select the category and follow the procedure.

![Page Creation](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Facebook%20Messenger%20Test%20Connector/_Docs/data/FB_0101_CreatePage.png)



<a name="pageId"></a>
##### Page Id

Once your page is created, **[get the PageId](https://www.facebook.com/help/1503421039731588)**. On your page :

* click **About** on the left column (click **See More** if **About** does not appear),
* scroll down to get your `PageId`.

![Get the PageId](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Facebook%20Messenger%20Test%20Connector/_Docs/data/FB_0102_CreatePage.png)

***<u>Important</u>*** :  Note the `PageName` and the `PageId`, they will be used to configure the connector.



<a name="application"></a>
#### Application

<a name="creation2"></a>
##### Creation

Create a Facebook application on your **[developer account](https://developers.facebook.com/apps)**.

![Application Creation](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Facebook%20Messenger%20Test%20Connector/_Docs/data/FB_0201_CreateApp.png)



***<u>Important</u>*** :  Note the `AppName`, it will be used to configure the connector.

For further details, follow the **[Facebook documentation](https://developers.facebook.com/docs/apps/register)**.



Once the application is created, fill the application form :

![Application Form](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Facebook%20Messenger%20Test%20Connector/_Docs/data/FB_0202_CreateApp.png)

***<u>Important</u>*** :  Note the `AppId` and the `AppSecret`, they will be used to configure the connector.



<a name="userToken"></a>
##### User Token

Open the **[Facebook Graph API page](https://developers.facebook.com/apps)** :

* open the API then your application,
* select **Get Token** => **Get User Access Token**,
* select :
  * `manage_pages`
  * `page_messaging`
  * `page_messaging_phone_number`
  * `read_page_mailboxes`
* Then click on **Get Access Token**.

![Get User Access Token](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Facebook%20Messenger%20Test%20Connector/_Docs/data/FB_0203_CreateApp.png)



<a name="messengerProduct"></a>
##### Messenger Product

Once the token generated, go back on the application setup page :

* on the left side menu, category **PRODUCTS**, click on ![](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Facebook%20Messenger%20Test%20Connector/_Docs/data/FB_plus.png) => **Add a product**,
* select **Messenger** and click **Configure** :

![Messenger Product](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Facebook%20Messenger%20Test%20Connector/_Docs/data/FB_0204_CreateApp.png)



On the **Token Generation** area, select your application page : an `Page Access Token` is automatically generated :

![Page Access Token](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Facebook%20Messenger%20Test%20Connector/_Docs/data/FB_0205_CreateApp.png)



***<u>Important</u>*** :  Note this `Page Access Token`, it will be used to configure the connector.



<a name="connector"></a>
#### Connector

<a name="configuration"></a>
##### Configuration

Deploy your connector package on `<kiamoPath>/data/userfiles/class/Messaging/Connector`.

On the connector's implementation folder, edit your dedicated configuration file (`conf/u_<connectorName>.php`) and set the page and application credentials :

![Connector Configuration](https://github.com/openKiamo/Messaging-Connectors/blob/master/Samples/GET_Connectors/Facebook%20Messenger%20Test%20Connector/_Docs/data/FB_0301_ConnectorConfiguration.png)

