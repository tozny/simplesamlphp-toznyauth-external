Tozny External Authentication for SimpleSAMLphp
====

[Tozny’s Authenticator](https://tozny.com/secure-login/) allows users to authenticate securely using cryptographically strong keys stored on their mobile device rather than a traditional password.

[SimpleSAMLphp](https://simplesamlphp.org/) is an open source utility that allows for centralized authentication from and to a variety of remote services.
 
Tozny's External Authentication module allows a SimpleSAMLphp server to use Tozny itself as a canonical source of authentication.

Installation
----

The module itself can be cloned directly from GitHub into the `/modules` directory of your SimpleSAMLphp installation. Make sure the cloned directory is named `toznyauth`: 

    $ git clone git@github.com:tozny/simplesamlphp-toznyauth-external.git toznyauth

Once it's there, you'll need to use [Composer](https://getcomposer.org/) to install its dependencies:
 
    $ cd toznyauth
    $ composer install

The module will be disabled by default - you'll need to create a file called `enable` in the module's root to turn it on:

    $ touch enable

Configuration
----

Before configuring anything, you will need to have a Realm set up with Tozny. A Realm is the entity that contains your users and holds all of their identifying information - end users will authenticate against this Realm using the Tozny Authenticator app on their mobile device. To get started with a new Realm, [sign up for the free Tozny beta](https://tozny.com/beta/).

Once you're signed up for the beta, you will need to log in to the [Tozny Administration Portal](https://admin.tozny.com) and retrieve your Realm's key ID and secret. For more details, see the [Tozny Developer Portal](https://developer.tozny.com/#key-management).

Open your SimpleSAMLphp's `authsources.php` configuration file in your favorite text editor. Add the following block to configure a new authentication source called "somesite":

    'somesite' => array(
        'toznyauth:External',
        'realm_key_id'     => 'sid_...',
        'realm_secret_key' => '49f127...fd9',
        'api_url'          => 'https://api.tozny.com',
    ),
    
You can call the authentication source whatever you want - "somesite" is just for demonstration purposes. The `realm_key_id` and `realm_secret_key` fields must be populated with the information you gathered about your Realm from the Tozny Administration Portal.

Now, configure your relying parties as usual, pointing at the new authentication source when needed.