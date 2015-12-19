<?php

namespace import;
use Guzzle\Http\Client;
use models\ImportURLModel;
use models\SiteModel;
use models\GroupModel;
use models\CountryModel;
use repositories\SiteRepository;
use repositories\GroupRepository;
use repositories\CountryRepository;
use repositories\AreaRepository;

/**
 *
 * @package Core
 * @link http://ican.openacalendar.org/ OpenACalendar Open Source Software
 * @license http://ican.openacalendar.org/license.html 3-clause BSD
 * @copyright (c) JMB Technology Limited, http://jmbtechnology.co.uk/
 * @author James Baster <james@jarofgreen.co.uk>
 */
class ImportURLRun {

	/** @var Client */
	protected $guzzle;

	/** @var ImportURLModel **/
	protected $importURL;
	
	/** @var SiteModel **/
	protected $site;

	/** @var GroupModel **/
	protected $group;
	
	/** @var CountryModel **/
	protected $country;
	
	/** @var AreaModel **/
	protected $area;
	
	protected $realurl;
		
	public static $FLAG_ADD_UIDS = 1;
	public static $FLAG_SET_TICKET_URL_AS_URL = 2;

	protected $flags = array();

	protected $temporaryFileStorage;
	protected $temporaryFileStorageFromTesting;
	
	function __construct(ImportURLModel $importURL, SiteModel $site = null) {
		global $CONFIG;
		$this->importURL = $importURL;
		$this->realurl = $importURL->getUrl();
		if ($site) {
			$this->site = $site;
		} else {
			$siteRepo = new SiteRepository();
			$this->site = $siteRepo->loadById($importURL->getSiteId());
		}
		if ($importURL->getCountryId()) {
			$countryRepo = new CountryRepository();
			$this->country = $countryRepo->loadById($importURL->getCountryId());
		}
		if ($importURL->getAreaId()) {
			$areaRepo = new AreaRepository();
			$this->area = $areaRepo->loadById($importURL->getAreaId());
		}
		$groupRepository = new GroupRepository();
		$this->group = $groupRepository->loadById($importURL->getGroupId());
		$this->guzzle = new Client();
		$this->guzzle->setUserAgent('OpenACalendar from ican.openacalendar.org, install '.$CONFIG->webIndexDomain);
	}

	public function getImportURL() {
		return $this->importURL;
	}
	
	public function getSite() {
		return $this->site;
	}

	public function getGroup() {
		return $this->group;
	}

	public function getCountry() {
		return $this->country;
	}	

	public function getArea() {
		return $this->area;
	}

	/**
	 * @return Client
	 */
	public function getGuzzle()
	{
		return $this->guzzle;
	}

	public function downloadURLreturnFileName() {
		if ($this->temporaryFileStorageFromTesting) return $this->temporaryFileStorageFromTesting;
		if ($this->temporaryFileStorage) return $this->temporaryFileStorage;
		
		$request = $this->guzzle->get($this->getRealUrl());
		$response = $this->guzzle->send($request);
		if ($response->getStatusCode() == 200) {
			$this->temporaryFileStorage = tempnam("/tmp", "oacimport");
			file_put_contents($this->temporaryFileStorage, $response->getBody(true));
			return $this->temporaryFileStorage;
		}

		return null;
	}
	
	public function deleteLocallyStoredURL() {
		if ($this->temporaryFileStorage) {
			unlink($this->temporaryFileStorage);
			$this->temporaryFileStorage = null;
		}
	}
	
	public function setTemporaryFileStorageForTesting($temporaryFileStorageFromTesting) {
		$this->temporaryFileStorageFromTesting = $temporaryFileStorageFromTesting;
		return $this;
	}

	public function setFlag($flag) {  $this->flags[$flag] = true; }
	public function hasFlag($flag) { return isset($this->flags[$flag]) && $this->flags[$flag]; }


    public function setRealUrl($realurl)
    {
        $this->realurl = $realurl;
		$this->deleteLocallyStoredURL();
    }

    public function getRealUrl()
    {
        return $this->realurl;
    }	
	
	function __destruct() {
		$this->deleteLocallyStoredURL();
	}
	
}

