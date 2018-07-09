=== Posts 2 Posts API ===
Tags: p2p,rest,api,connections,relationships
Donate link: https://waterproof-webdesign.info/donate
Contributors: jhotadhari
Tested up to: 4.9.7
Requires at least: 4.7
Requires PHP: 5.6
Stable tag: trunk
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

REST API extension for Posts 2 Posts plugin


== Description ==

REST API extension for Posts 2 Posts plugin

> This plugin is **NOT** production ready <br/>
> It will work, but it does  **NOT HAVE PERMISSION CHECKS** yet!  <br/>
> So anyone can create, read, update and delete connections using the api endpoints.<br/><br/>
> If you want to dig the code, check the REST controllers and their permission callback methods:
>   * [./src/inc/fun/autoload/class-p2papi_rest_connections_controller.php](https://github.com/jhotadhari/posts-2-posts-api/tree/master/src/inc/fun/autoload/class-p2papi_rest_connections_controller.php)
>   * [./src/inc/fun/autoload/class-p2papi_rest_connection_types_controller.php](https://github.com/jhotadhari/posts-2-posts-api/tree/master/src/inc/fun/autoload/class-p2papi_rest_connection_types_controller.php)

The Posts 2 Posts API Plugin provides API routes and endpoints for Connection-Types and Connections registered by [Posts 2 Posts](https://github.com/scribu/wp-posts-to-posts) Plugin.


> Posts 2 Posts API on [GitHub](https://github.com/jhotadhari/posts-2-posts-api)

> This Plugin is happy for contribution, sponsorship or [donations](http://waterproof-webdesign.info/donate)



= Routes =

Access Posts 2 Posts API’s index by making a GET request to https://example-site.com/wp-json/p2papi/v1.
The index provides information regarding what routes are available, along with what HTTP methods are supported, what endpoints are registered and the endpoints arguments.

List of routes and supported crud actions:
* ```/p2papi/v1```
* ```/p2papi/v1/connection-types/schema```
  * read
* ```/p2papi/v1/connection-types```
  * read
* ```/p2papi/v1/connection-types/(?P<name>[a-zA-Z0-9-_]+)```
  * read
* ```/p2papi/v1/connections/schema```
  * read
* ```/p2papi/v1/connections```
  * read
  * create
* ```/p2papi/v1/connections/(?P<p2p_id>[\d]+)```
  * read
  * update
  * delete
* ```/p2papi/v1/connections/(?P<p2p_type>[a-zA-Z0-9-_]+)```
  * read
  * create
* ```/p2papi/v1/connections/(?P<p2p_type>[a-zA-Z0-9-_]+)/(?P<p2p_id>[\d]+)```
  * read
  * update
  * delete


This Plugin is generated with [generator-pluginboilerplate version 1.2.0](https://github.com/jhotadhari/generator-pluginboilerplate)

== Installation ==
Upload and install this Plugin the same way you'd install any other plugin.

### Requirements:
* php 5.6
* [Posts 2 Posts](https://github.com/scribu/wp-posts-to-posts) Plugin by [Scribu](http://scribu.net/)

If the Plugin is not available in the official WordPress Plugin Repository, you will find the [latest distributed version in its github repository: ./dist/trunk/](https://github.com/jhotadhari/posts-2-posts-api/tree/master/dist/trunk). Copy the ./dist/trunk/ folder, rename it to 'posts-2-posts-api' and upload it to your WordPress plugin directory.

== Screenshots ==

== Upgrade Notice ==

This Plugin is still in early development. Reality might be in movement.


== Changelog ==

0.0.1
* added plugin dependency for p2p;
* added basic controller for p2p connection types;
* added basic controller for p2p connections;
* edited readme;

