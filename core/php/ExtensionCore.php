<?php

/**
 *
 * @package Core
 * @link http://ican.openacalendar.org/ OpenACalendar Open Source Software
 * @license http://ican.openacalendar.org/license.html 3-clause BSD
 * @copyright (c) 2013-2014, JMB Technology Limited, http://jmbtechnology.co.uk/
 * @author James Baster <james@jarofgreen.co.uk>
 */
class ExtensionCore extends BaseExtension {
		
	public function getId() {
		return 'org.openacalendar';
	}

	public function getTitle() {
		return 'Core';
	}	
	
	public function getUserNotificationTypes() {
		return array('UpcomingEvents','UserWatchesGroupPrompt','UserWatchesGroupNotify',
			'UserWatchesSiteNotify','UserWatchesSiteGroupPrompt','UserWatchesSitePrompt');
	}
	
	public function getUserNotificationType($type) {
		if ($type == 'UpcomingEvents') {
			return new usernotifications\UpcomingEventsUserNotificationType();
		} else if ($type == 'UserWatchesGroupPrompt') {
			return new usernotifications\UserWatchesGroupPromptNotificationType();
		} else if ($type == 'UserWatchesGroupNotify') {
			return new usernotifications\UserWatchesGroupNotifyNotificationType();
		} else if ($type == 'UserWatchesSiteNotify') {
			return new usernotifications\UserWatchesSiteNotifyNotificationType();
		} else if ($type == 'UserWatchesSiteGroupPrompt') {
			return new usernotifications\UserWatchesSiteGroupPromptNotificationType();
		} else if ($type == 'UserWatchesSitePrompt') {
			return new usernotifications\UserWatchesSitePromptNotificationType();
		} else {
			return null;
		}
	}
	
	
	public function getUserNotificationPreferenceTypes() {
		return array('WatchPrompt','WatchNotify','UpcomingEvents');
		// WatchImportExpired left out for now as that is using the old flag.
	}
	
	public function getUserNotificationPreference($type) {
		if ($type == 'WatchPrompt') {
			return new usernotifications\preferences\WatchPromptNotificationPreference();
		} else if ($type == 'WatchNotify') {
			return new usernotifications\preferences\WatchNotifyNotificationPreference();
		} else if ($type == 'WatchImportExpired') {
			return new usernotifications\preferences\WatchImportExpiredNotificationPreference();
		} else if ($type == 'UpcomingEvents') {
			return new usernotifications\preferences\UpcomingEventsNotificationPreference();
		}
		return null;
	}
	
}