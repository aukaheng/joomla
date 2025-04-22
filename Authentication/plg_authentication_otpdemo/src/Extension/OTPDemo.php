<?php

namespace AKH\Plugin\Authentication\OTPDemo\Extension;

use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseAwareTrait;

\defined('_JEXEC') or die;

final class OTPDemo extends CMSPlugin
{
    use DatabaseAwareTrait;

    public function onUserAuthenticate($credentials, $options, &$response)
    {
        $response->type = 'otpdemo';

        // Joomla does not like blank passwords
        if (empty($credentials['password'])) {
            $response->status = Authentication::STATUS_FAILURE;
            $response->error_message = $this->getApplication()->getLanguage()->_('JGLOBAL_AUTH_EMPTY_PASS_NOT_ALLOWED');

            return;
        }

        // $session = Factory::getSession();
        // $otpId = $session->get('login.otpid', '');
        $otpId = $this->getApplication()->getUserState('login.otpid', '');

        $db = $this->getDatabase();

        $query = $db->getQuery(true);

        $query->select($db->quoteName(array('a.id', 'c.phone')))
            ->from($db->quoteName('#__users', 'a'))
            ->join('left', $db->quoteName('#__registrations', 'b') . ' on (' . $db->quoteName('b.user_id') . ' = ' . $db->quoteName('a.id') . ')')
            ->join('left', $db->quoteName('#__registrations_members', 'c') . ' on (' . $db->quoteName('c.registration_id') . ' = ' . $db->quoteName('b.id') . ')')
            ->where($db->quoteName('a.username') . ' = :username')
            ->where($db->quoteName('c.is_contact') . ' = 1')
            ->bind(':username', $credentials['username']);

        $db->setQuery($query);

        $result = $db->loadAssoc();

        if ($result) {
            $query->clear();

            $query->select($db->quoteName(array('code', 'created')))
                ->from($db->quoteName('#__otp_codes'))
                ->where($db->quoteName('id') . ' = :otpId')
                ->where($db->quoteName('phone') . ' = :phone')
                ->bind(':otpId', $otpId)
                ->bind(':phone', $result['phone']);

            $db->setQuery($query);

            $otp = $db->loadObject();

            $now = time();
            
            $diff = $now - $otp->created;
            if ($diff > 310) {
                $response->status = Authentication::STATUS_FAILURE;
                $response->error_message = '';
                return;
            }

            $match = $credentials['password'] == $otp->code;

            if ($match === true) {
                $this->getApplication()->setUserState('login.otpid', '');

                $user = User::getInstance($result['id']);

                // $response->email = $user->email;
                // $response->fullname = $user->name;

                // Set default status response to success
                $_status = Authentication::STATUS_SUCCESS;
                $_errorMessage = '';

                if ($this->getApplication()->isClient('administrator')) {
                    $response->language = $user->getParam('admin_language');
                } else {
                    $response->language = $user->getParam('language');

                    if ($this->getApplication()->get('offline') && !$user->authorise('core.login.offline')) {
                        $_status = Authentication::STATUS_FAILURE;
                        $_errorMessage = Text::_('JLIB_LOGIN_DENIED');
                    }
                }

                $response->status = $_status;
                $response->error_message = $_errorMessage;
            } else {
                $response->status = Authentication::STATUS_FAILURE;
                $response->error_message = $this->getApplication()->getLanguage()->_('JGLOBAL_AUTH_INVALID_PASS');
            }
        } else {
                $response->status = Authentication::STATUS_FAILURE;
                $response->error_message = $this->getApplication()->getLanguage()->_('JGLOBAL_AUTH_NO_USER');
        }
    }
}
