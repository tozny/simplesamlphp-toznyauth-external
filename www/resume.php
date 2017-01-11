<?php
/**
 * simplesamlphp-modules/toznyauth/www/resume.php
 *
 * @package default
 */


/**
 * This page serves as the point where the user's authentication
 * process is resumed after the login page.
 *
 * It simply passes control back to the class.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
sspmod_toznyauth_Auth_Source_External::resume();
