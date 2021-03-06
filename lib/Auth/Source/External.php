<?php

/**
 * Tozny as an external authentication source.
 */
class sspmod_toznyauth_Auth_Source_External extends SimpleSAML_Auth_Source {

	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		/* Do any other configuration we need here. */
        $this->realm_key_id     = $config['realm_key_id'];
        $this->realm_secret_key = $config['realm_secret_key'];
        $this->api_url          = $config['api_url'];
    }



	/**
	 * Retrieve attributes for the user.
	 *
	 * @return array|NULL  The user's attributes, or NULL if the user isn't authenticated.
	 */
	private function getUser() {

		/*
		 * We assume that the attributes stored in the users PHP session.
		 */

		if (!session_id()) {
			/* session_start not called before. Do it here. */
			session_start();
		}

		if (!isset($_SESSION['uid'])) {
			/* The user isn't authenticated. */
			return NULL;
		}

		/*
		 * Find the attributes for the user.
		 * Note that all attributes in simpleSAMLphp are multivalued, so we need
		 * to store them as arrays.
		 */

		$attributes = array(
			'uid' => array($_SESSION['uid']),
		);
        if(isset($_SESSION['user_meta'])) {
            foreach ($_SESSION['user_meta'] as $key => $val) {
                if (in_array($key, ['user_id', 'return', 'status_code'])) {
                    continue;
                }
                $attributes[$key] = is_array($val) ? $val : array ($val);
            }
        }


		return $attributes;
	}


	/**
	 * Log in using Tozny.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		$attributes = $this->getUser();

		if ($attributes !== NULL) {
			/*
			 * The user is already authenticated.
			 *
			 * Add the users attributes to the $state-array, and return control
			 * to the authentication process.
			 */
			$state['Attributes'] = $attributes;
			return;
		}

		/*
		 * The user isn't authenticated. We therefore need to
		 * send the user to the login page.
		 */

		/*
		 * First we add the identifier of this authentication source
		 * to the state array, so that we know where to resume.
		 */
		$state['toznyauth:AuthID']           = $this->authId;
		$state['toznyauth:realm_key_id']     = $this->realm_key_id;
		$state['toznyauth:realm_secret_key'] = $this->realm_secret_key;
        $state['toznyauth:api_url']          = $this->api_url;


		/*
		 * We need to save the $state-array, so that we can resume the
		 * login process after authentication.
		 *
		 * Note the second parameter to the saveState-function. This is a
		 * unique identifier for where the state was saved, and must be used
		 * again when we retrieve the state.
		 *
		 * The reason for it is to prevent
		 * attacks where the user takes a $state-array saved in one location
		 * and restores it in another location, and thus bypasses steps in
		 * the authentication process.
		 */
		$stateId = SimpleSAML_Auth_State::saveState($state, 'toznyauth:External');

		/*
		 * Now we generate an URL the user should return to after authentication.
		 * We assume that whatever authentication page we send the user to has an
		 * option to return the user to a specific page afterwards.
		 */
		$returnTo = SimpleSAML_Module::getModuleURL('toznyauth/resume.php', array(
			'State' => $stateId,
		));

		/*
		 * Get the URL of the authentication page.
		 *
		 * Here we use the getModuleURL function again, since the authentication page
		 * is also part of this module.
		 */
		$authPage = SimpleSAML_Module::getModuleURL('toznyauth/authpage.php');

		/*
		 * The redirect to the authentication page.
		 *
		 * Note the 'ReturnTo' parameter. This must most likely be replaced with
		 * the real name of the parameter for the login page.
		 */
		SimpleSAML_Utilities::redirect($authPage, array(
			'ReturnTo'     => $returnTo,
		));

		/*
		 * The redirect function never returns, so we never get this far.
		 */
		assert('FALSE');
	}


	/**
	 * Resume authentication process.
	 *
	 * This function resumes the authentication process after the user has
	 * entered her credentials.
	 *
	 * @param array &$state  The authentication state.
	 */
	public static function resume() {

		/*
		 * First we need to restore the $state-array. We should have the identifier for
		 * it in the 'State' request parameter.
		 */
		if (!isset($_REQUEST['State'])) {
			throw new SimpleSAML_Error_BadRequest('Missing "State" parameter.');
		}
		$stateId = (string)$_REQUEST['State'];

		/*
		 * Once again, note the second parameter to the loadState function. This must
		 * match the string we used in the saveState-call above.
		 */
		$state = SimpleSAML_Auth_State::loadState($stateId, 'toznyauth:External');

		/*
		 * Now we have the $state-array, and can use it to locate the authentication
		 * source.
		 */
		$source = SimpleSAML_Auth_Source::getById($state['toznyauth:AuthID']);
		if ($source === NULL) {
			/*
			 * The only way this should fail is if we remove or rename the authentication source
			 * while the user is at the login page.
			 */
			throw new SimpleSAML_Error_Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
		}

		/*
		 * Make sure that we haven't switched the source type while the
		 * user was at the authentication page. This can only happen if we
		 * change config/authsources.php while an user is logging in.
		 */
		if (! ($source instanceof self)) {
			throw new SimpleSAML_Error_Exception('Authentication source type changed.');
		}


		/*
		 * OK, now we know that our current state is sane. Time to actually log the user in.
		 *
		 * First we check that the user is actually logged in, and didn't simply skip the login page.
		 */
		$attributes = $source->getUser();
		if ($attributes === NULL) {
			/*
			 * The user isn't authenticated.
			 *
			 * Here we simply throw an exception, but we could also redirect the user back to the
			 * login page.
			 */
			throw new SimpleSAML_Error_Exception('User not authenticated after login page.');
		}

		/*
		 * So, we have a valid user. Time to resume the authentication process where we
		 * paused it in the authenticate()-function above.
		 */

		$state['Attributes'] = $attributes;
		SimpleSAML_Auth_Source::completeAuth($state);

		/*
		 * The completeAuth-function never returns, so we never get this far.
		 */
		assert('FALSE');
	}


	/**
	 * This function is called when the user starts a logout operation, for example
	 * by logging out of a SP that supports single logout.
	 *
	 * @param array &$state  The logout state array.
	 */
	public function logout(&$state) {
		assert('is_array($state)');

		if (!session_id()) {
			/* session_start not called before. Do it here. */
			session_start();
		}

		unset($_SESSION['uid']);
	}

}
