<?php

namespace AKH\Plugin\Authentication\ContaUnica\Extension;

use InvalidArgumentException;
use Joomla\Authentication\Exception\UnsupportedPasswordHandlerException;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\DI\Exception\KeyNotFoundException;
use Random\RandomException;
use RuntimeException;

\defined('_JEXEC') or die;

final class ContaUnica extends CMSPlugin
{
    use DatabaseAwareTrait;

	/**
	 * 
	 * @param array $credentials 
	 * @param array $options 
	 * @param object &$response 
	 * @return void 
	 */
	public function onUserAuthenticate(array $credentials, array $options, &$response): void
	{
        $response->type = 'ContaUnica';

		if (!isset($options['grantCode'])) {
			return;
		}

		$grantCode = $options['grantCode'];
		$codeVerifier = $options['codeVerifier'];
		$accoutType = $options['accountType'];

		try {
			$profile = $this->getProfile($accoutType, $grantCode, $codeVerifier);
		} catch (\Exception $e) {
			$response->status = Authentication::STATUS_FAILURE;
			$response->error_message = $e->getMessage();
			return;
		}

		if ($options['accountType'] == 'ab' && strpos($profile->euid, 'CU') !== 0) {
			$response->status = Authentication::STATUS_FAILURE;
			$response->error_message = 'Invalid euid for ab account';
			return;
		}

		if ($options['accountType'] == 'mo' && strpos($profile->euid, 'GA') !== 0) {
			$response->status = Authentication::STATUS_FAILURE;
			$response->error_message = 'Invalid euid for mo account';
			return;
		}

		$db = $this->getDatabase();

		$query = $db->getQuery(true);

		$query->select($db->quoteName('id'))
			->from($db->quoteName('#__users'))
			->where($db->quoteName('username') . ' = :username')
			->bind(':username', $profile->euid);

		$db->setQuery($query);

		$userId = $db->loadResult();

		if ($userId) {
			$user = User::getInstance($userId);

			$response->username = $user->username;
			$response->fullname = $user->name;
		} else {
			$userData = array(
				'username'   => $profile->euid,
				'name'       => $profile->name,
				'email'      => $profile->euid . '@example.com',
				'password'   => 'abcdefgABCDEFG123456!@#',
				'sendEmail'  => 0,
				'activation' => 0,
				'block'      => 0,
				'groups'     => ($options['accountType'] == 'ab') ? array(11) : array(10)
			);

			$newUser = new User();

			$newUser->bind($userData);

			$newUser->save();

			$response->username = $profile->euid;
			$response->fullname = $profile->name;
		}
		
		$response->phone = $profile->phone;
		$response->identityNumber = $profile->identityNumber;
		
		$response->status = Authentication::STATUS_SUCCESS;
		$response->error_message = '';
	}

    private function getProfile(string $accountType, string $grantCode, string $codeVerifier): object
    {
		$url = '';
		$clientId = '';
		$clientSecret = '';
		$redirectUri = $this->params->get('redirect_uri_authority') . '/index.php?option=com_mycomponent&task=user.login&';

		if ($accountType == 'ab') {
			$url = $this->params->get('entity_url');
			$clientId = $this->params->get('entity_client_id');
			$clientSecret = $this->params->get('entity_client_secret');
			$redirectUri .= 'ab=';
		} else {
			$url = $this->params->get('account_url');
			$clientId = $this->params->get('account_client_id');
			$clientSecret = $this->params->get('account_client_secret');
			$redirectUri .= 'mo=';
		}

        $client = HttpFactory::getHttp();

        // CurlTransport.php
        // CURLOPT_RETURNTRANSFER is true
		// DO NOT urlencode the "redirect_uri".
		// AB requires the "redirect_uri" to be exactly the same as the one used in the authorization request.
        // MO is a little bit less restricted, matching the domain name is Okay.
        $response = $client->post(
            $url . '/o/token/',
            array(
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $grantCode,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
                'code_verifier' => $codeVerifier
            )
        );

        $result = json_decode($response->body);

		if (isset($result->error)) {
			throw new \Exception($result->error);
		}

        $client = HttpFactory::getHttp();

        $response = $client->get(
            $url . '/o/profile/',
            array(
                'Authorization' => 'Bearer ' . $result->access_token
            )
        );

        $result = json_decode($response->body);

		$profile = new \stdClass();

		if ($accountType == 'ab') {
			$profile->euid = $result->euid;
			$profile->name = $result->nameCn;
			$profile->phone = $result->contactPhone;
			$profile->identityNumber = $result->identityNo;
		} else {
			$profile->euid = $result->euid;
			$profile->name = $result->nameCn;
			$profile->phone = $result->mobile;
			$profile->identityNumber = $result->identityNo;
		}

		return $profile;
    }
}
