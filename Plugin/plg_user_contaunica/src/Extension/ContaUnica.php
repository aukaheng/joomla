<?php

namespace AKH\Plugin\User\ContaUnica\Extension;

use InvalidArgumentException;
use Joomla\Authentication\Exception\UnsupportedPasswordHandlerException;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\Exception\DatabaseNotFoundException;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\Exception\QueryTypeAlreadyDefinedException;
use Joomla\Database\ParameterType;
use Joomla\DI\Exception\KeyNotFoundException;
use Joomla\Registry\Registry;
use Random\RandomException;

\defined('_JEXEC') or die;

final class ContaUnica extends CMSPlugin
{
    use DatabaseAwareTrait;

    /**
     * 
     * @param object $response 
     * @param array $options 
     * @return bool
     */
    public function onUserLogin($response, $options = []): bool
    {
        $user = Factory::getUser();

		$fields = array('com_fields' => [
			'phone' => $response['phone'],
			'identity-number' => $response['identityNumber']
		]);

		$user->bind($fields);

		$user->save();

        return true;
    }

    /**
     * 
     * @param array $options 
     * @return void 
     */
    public function onUserAfterLogin(array $options): void
    {

    }

    /**
     * 
     * @param User $user 
     * @param array $options 
     * @return bool
     */
    public function onUserLogout($user, $options = []): bool
    {
        if ($this->getApplication()->isClient('site')) {
            $userId = (int) $user['id'];

            $db = $this->getDatabase();

            $query = $db->getQuery(true);

            $query->select($db->quoteName('c.title'))
                ->from($db->quoteName('#__users', 'a'))
                ->join('LEFT', $db->quoteName('#__user_usergroup_map', 'b') . ' ON ' . $db->quoteName('b.user_id') . ' = ' . $db->quoteName('a.id'))
                ->join('LEFT', $db->quoteName('#__usergroups', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('b.group_id'))
                ->where($db->quoteName('a.id') . ' = :userId')
                ->bind(':userId', $userId, ParameterType::INTEGER);

            $db->setQuery($query);

            $userGroupNames = $db->loadColumn();

            if (empty($userGroupNames)) {
                return true;
            }

            if (empty(array_intersect(['mo', 'ab'], $userGroupNames))) {
                return true;
            }

            $next = $this->params->get('next');

            // AB
            if (in_array('ab', $userGroupNames)) {
                $url = $this->params->get('entity_url', '') . '/logout?next=' . $next;
            }
            // MO
            else {
                $url = $this->params->get('account_url', '') . '/logout?next=' . $next;
            }

            $this->getApplication()->enqueueMessage('You have been logged out.', 'success');
            $this->getApplication()->redirect($url);
        }
        
        return true;
    }
}
