<?php
/**
 * Request handler for redirect filter test.
 *
 * @author Olav Morken, UNINETT AS.
 */
if (!array_key_exists('StateId', $_REQUEST)) {
    throw new SimpleSAML_Error_BadRequest('Missing required StateId query parameter.');
}

$id = $_REQUEST['StateId'];
$state = SimpleSAML_Auth_State::loadState($id, 'toznyauth:redirectfilter-test');

$state['Attributes']['RedirectTest2'] = array('OK');

SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);
