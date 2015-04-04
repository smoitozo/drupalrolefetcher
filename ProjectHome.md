# Overview #
The Drupal Role Fetcher (DRF) is a small PHP application that runs alongside a Drupal 7.x site and answers queries for user roles from authorized agents. This software was commissioned by the [Polder Consortium](http://www.polderconsortium.org). Our purpose for developing this application is that it plays a key role in the Consortium's Web application architecture made up of a series of SAML-enabled (federated) applications. Our Web application architecture includes: [simpleSAMLphp](http://simplesamlphp.org), [Drupal](http://www.drupal.org), [DokuWiki](http://www.dokuwiki.org), and others. Additionally, the Consortium saw the value of producing this for others to leverage in their environments.

We needed centralized role management for these applications but the problem we faced was that users could be authenticating against one of several SAML IdP's. Since we wanted access to our Web resources to be controlled centrally in a Drupal site our SAML SPs needed a way to get the roles from our Drupal site and append them to the user identity. This effectively allows us to do centralized role management, regardless of the IdP the user authenticates against.

At login time our simpleSAMLphp Service Provider queries the Drupal site for the list of roles assigned to the user. This is accomplished using two components: the DRF and the AddRolesFromDrupal authentication processing filter for SimpleSAMLphp, which is part of this project (see the integrations directory).

# Features #
  * roles are rooted in a configurable realm – for instance, if “www.example.org” is the realm then the role “xyz-role” would become xyz-role@www.example.org
  * authorized agents are configured in the configuration file of the DRF using a simple array that includes the following information for each agent
    1. shared secret key
    1. agent name
    1. agent description
    1. agent contact person's e-mail
  * the DRF utilizes the Drupal API for retrieving user information
  * requests to the DRF are idempotent (i.e., they can never cause harm to the Drupal site or result in an error that will negatively affect the requesting agent)
  * if an agent requests roles for a bogus user the DRF will return NULL
  * if an agent requests roles for a user that has no roles the DRF will return NULL
  * the DRF suppresses the “Authenticated User” and “Administrator” roles
  * requests to the DRF will be simple GET requests with two parameters: sharedsec (the shared secret key for the agent) and userid (typically this will be the value of the user's eduPersonPrincipleName or eduPersonTargettedID attribute)
  * the value of “userid” will be used to search for a matching user in Drupal's authmap table (via Drupal's user\_external\_load function)
  * agents can request responses to be serialized in one of three ways by passing the optional “mode” parameter: CSV, XML, or JSON (if no serialization is specified the default will be CSV)
  * the DRF will respond with either NULL (in the case of no matching user or no roles for the user) or with a list (array) containing the following information serialized in the requested serialization (CSV, PHP, or JSON; the default will be a CSV string)
    1. role name
  * the DRF requires all requests to come over encrypted HTTP (HTTPS)