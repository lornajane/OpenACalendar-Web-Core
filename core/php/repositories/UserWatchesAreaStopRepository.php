<?php

namespace repositories;

use models\UserAccountModel;
use models\UserWatchesAreaStopModel;
use models\SiteModel;
use models\AreaModel;

/**
 *
 * @package Core
 * @link http://ican.openacalendar.org/ OpenACalendar Open Source Software
 * @license http://ican.openacalendar.org/license.html 3-clause BSD
 * @copyright (c) JMB Technology Limited, http://jmbtechnology.co.uk/
 * @author James Baster <james@jarofgreen.co.uk> 
 */
class UserWatchesAreaStopRepository {
	
	/**
	 * This will always return something. If one doesn't exist, one will be created.
	 * @return UserWatchesSiteStopModel
	 */
	public function getForUserAndArea(UserAccountModel $user, AreaModel $area) {
		global $DB;
		
		$stat = $DB->prepare("SELECT * FROM user_watches_area_stop WHERE user_account_id=:uid AND area_id=:gid");
		$stat->execute(array('uid'=>$user->getId(),'gid'=>$area->getId()));
		if ($stat->rowCount() > 0) {
			$uwgs = new UserWatchesAreaStopModel();
			$uwgs->setFromDataBaseRow($stat->fetch());
			return $uwgs;
		}
		
		$uwgs = new UserWatchesAreaStopModel();
		$uwgs->setUserAccountId($user->getId());
		$uwgs->setAreaId($area->getId());
		$uwgs->setAccessKey(createKey(2,150));
		
		// TODO check not already used
		
		$stat = $DB->prepare("INSERT INTO user_watches_area_stop (user_account_id, area_id, access_key, created_at) ".
				"VALUES (:user_account_id, :area_id, :access_key, :created_at)");
		$stat->execute(array(
				'user_account_id'=>$uwgs->getUserAccountId(),
				'area_id'=>$uwgs->getAreaId(),
				'access_key'=>$uwgs->getAccessKey(),
				'created_at'=>\TimeSource::getFormattedForDataBase()
			));
		
		return $uwgs;
		
	}
	
	/** @return UserWatchesSiteStopModel **/
	public function loadByUserAccountIDAndAreaIDAndAccessKey($userId, $areaId, $access) {
		global $DB;
		$stat = $DB->prepare("SELECT * FROM user_watches_area_stop WHERE user_account_id=:uid AND area_id=:gid");
		$stat->execute(array('uid'=>$userId,'gid'=>$areaId));
		if ($stat->rowCount() > 0) {
			$uwss = new UserWatchesAreaStopModel();
			$uwss->setFromDataBaseRow($stat->fetch());
			return $uwss;
		}
	}
	
}
