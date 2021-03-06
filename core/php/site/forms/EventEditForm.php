<?php

namespace site\forms;

use ExtensionManager;
use repositories\SiteFeatureRepository;
use Silex\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;
use models\SiteModel;
use repositories\builders\CountryRepositoryBuilder;
use repositories\builders\VenueRepositoryBuilder;


/**
 *
 * @package Core
 * @link http://ican.openacalendar.org/ OpenACalendar Open Source Software
 * @license http://ican.openacalendar.org/license.html 3-clause BSD
 * @copyright (c) JMB Technology Limited, http://jmbtechnology.co.uk/
 * @author James Baster <james@jarofgreen.co.uk> 
 */
class EventEditForm extends \BaseFormWithEditComment {

    /** @var Application */
    protected $app;

	protected $timeZoneName;

	/** @var SiteModel **/
	protected $site;

    protected $siteFeaturePhysicalEvents = false;
    protected $siteFeatureVirtualEvents = false;

	protected $formWidgetTimeMinutesMultiples;

	/** @var  ExtensionManager */
	protected $extensionManager;

	function __construct(SiteModel $site, $timeZoneName, Application $application) {
		parent::__construct($application);
		$this->site = $site;
		$this->timeZoneName = $timeZoneName;
		$this->formWidgetTimeMinutesMultiples = $application['config']->formWidgetTimeMinutesMultiples;
		$this->extensionManager = $application['extensions'];
        $this->app = $application;
        $siteFeatureRepo = new SiteFeatureRepository($application);
        $this->siteFeaturePhysicalEvents = $siteFeatureRepo->doesSiteHaveFeatureByExtensionAndId($this->site,'org.openacalendar','PhysicalEvents');
        $this->siteFeatureVirtualEvents = $siteFeatureRepo->doesSiteHaveFeatureByExtensionAndId($this->site,'org.openacalendar','VirtualEvents');
	}

	protected $customFields;


	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);


		$builder->add('summary', 'text', array(
			'label'=>'Summary',
			'required'=>true,
			'max_length'=>VARCHAR_COLUMN_LENGTH_USED,
			'attr' => array('autofocus' => 'autofocus')
		));

		$builder->add('description', 'textarea', array(
			'label'=>'Description',
			'required'=>false
		));

		$builder->add('url', new \symfony\form\MagicUrlType(), array(
			'label'=>'Information Web Page URL',
			'required'=>false
		));

		$builder->add('ticket_url', new \symfony\form\MagicUrlType(), array(
			'label'=>'Tickets Web Page URL',
			'required'=>false
		));

		$crb = new CountryRepositoryBuilder($this->app);
		$crb->setSiteIn($this->site);
		$countries = array();
		foreach($crb->fetchAll() as $country) {
			$countries[$country->getId()] = $country->getTitle();
		}
		// TODO if current country not in list add it now
		if (count($countries) != 1) {
			$builder->add('country_id', 'choice', array(
				'label'=>'Country',
				'choices' => $countries,
				'required' => true,
                'choices_as_values'=>false,
			));
		} else {
			$countryID = array_shift(array_keys($countries));
			$builder->add('country_id', 'hidden', array(
				'data' => $countryID,
			));
		}

		$timezones = array();
		// Must explicetly set name as key otherwise Symfony forms puts an ID in, and that's no good for processing outside form
		foreach($this->site->getCachedTimezonesAsList() as $timezone) {
			$timezones[$timezone] = $timezone;
		}
		// TODO if current timezone not in list add it now
		if (count($timezones) != 1) {
			$builder->add('timezone', 'choice', array(
				'label'=>'Time Zone',
				'choices' => $timezones,
				'required' => true,
                'choices_as_values'=>false,
			));
		} else {
			$timezone = array_pop($timezones);
			$builder->add('timezone', 'hidden', array(
				'data' => $timezone,
			));
		}


		if ($this->siteFeatureVirtualEvents) {

			//  if both are an option, user must check which one.
			if ($this->siteFeaturePhysicalEvents) {

				$builder->add("is_virtual",
					"checkbox",
						array(
							'required'=>false,
							'label'=>'Is event accessible online?'
						)
					);
			}

		}


		if ($this->siteFeaturePhysicalEvents) {

			// if both are an option, user must check which one.
			if ($this->siteFeatureVirtualEvents) {

				$builder->add("is_physical",
					"checkbox",
						array(
							'required'=>false,
							'label'=>'Does the event happen at a place?'
						)
					);

			}

		}

		$years = array( date('Y'), date('Y')+1 );

		$startOptions = array(
			'label'=>'Start',
			'date_widget'=> 'single_text',
			'date_format'=>'d/M/y',
			'model_timezone' => 'UTC',
			'view_timezone' => $this->timeZoneName,
			'years' => $years,
			'attr' => array('class' => 'dateInput'),
			'required'=>true
		);
		if ($this->formWidgetTimeMinutesMultiples > 1) {
			$startOptions['minutes'] = array();
			for ($i = 0; $i <= 59; $i=$i+$this->formWidgetTimeMinutesMultiples) {
				$startOptions['minutes'][] = $i;
			}
		}
		$builder->add('start_at', 'datetime' , $startOptions);

		$endOptions = array(
			'label'=>'End',
			'date_widget'=> 'single_text',
			'date_format'=>'d/M/y',
			'model_timezone' => 'UTC',
			'view_timezone' => $this->timeZoneName,
			'years' => $years,
			'attr' => array('class' => 'dateInput'),
			'required'=>true
		);
		if ($this->formWidgetTimeMinutesMultiples > 1) {
			$endOptions['minutes'] = array();
			for ($i = 0; $i <= 59; $i=$i+$this->formWidgetTimeMinutesMultiples) {
				$endOptions['minutes'][] = $i;
			}
		}
		$builder->add('end_at', 'datetime' , $endOptions);

		$this->customFields = array();
		foreach($this->site->getCachedEventCustomFieldDefinitionsAsModels() as $customField) {
			if ($customField->getIsActive()) {
				$extension = $this->extensionManager->getExtensionById($customField->getExtensionId());
				if ($extension) {
					$fieldType = $extension->getEventCustomFieldByType($customField->getType());
					if ($fieldType) {
						$this->customFields[] = $customField;
						$options = $fieldType->getSymfonyFormOptions($customField);
						$options['mapped'] = false;
						$options['data'] = $builder->getData()->getCustomField($customField);
						$builder->add('custom_' . $customField->getKey(), $fieldType->getSymfonyFormType($customField), $options);
					}
				}
			}
		}

		/** @var \closure $myExtraFieldValidator **/
		$myExtraFieldValidator = function(FormEvent $event){
			$form = $event->getForm();
			$myExtraFieldStart = $form->get('start_at')->getData();
			$myExtraFieldEnd = $form->get('end_at')->getData();
			// Validate end is not the same as start
			if ($myExtraFieldStart == $myExtraFieldEnd) {
				$form['end_at']->addError(new FormError("The end can not be the same as the start!"));
			}
			// Validate end is after start?
			if ($myExtraFieldStart > $myExtraFieldEnd) {
				$form['start_at']->addError(new FormError("The start can not be after the end!"));
			}
			// validate not to far in future
			$max = \TimeSource::getDateTime();
			$max->add(new \DateInterval(("P".$this->app['config']->eventsCantBeMoreThanYearsInFuture."Y")));
			if ($myExtraFieldStart > $max) {
				$form['start_at']->addError(new FormError("The event can not be more than ".
						($this->app['config']->eventsCantBeMoreThanYearsInFuture > 1 ? $this->app['config']->eventsCantBeMoreThanYearsInFuture." years"  : "a year" ).
						" in the future."));
			}
			if ($myExtraFieldEnd > $max) {
				$form['end_at']->addError(new FormError("The event can not be more than ".
						($this->app['config']->eventsCantBeMoreThanYearsInFuture > 1 ? $this->app['config']->eventsCantBeMoreThanYearsInFuture." years"  : "a year" ).
						" in the future."));			}
			// validate not to far in past
			$min = \TimeSource::getDateTime();
			$min->sub(new \DateInterval(("P".$this->app['config']->eventsCantBeMoreThanYearsInPast."Y")));
			if ($myExtraFieldStart < $min) {
				$form['start_at']->addError(new FormError("The event can not be more than ".
						($this->app['config']->eventsCantBeMoreThanYearsInPast > 1 ? $this->app['config']->eventsCantBeMoreThanYearsInPast." years"  : "a year" ).
						" in the past."));
			}
			if ($myExtraFieldEnd < $min) {
				$form['end_at']->addError(new FormError("The event can not be more than ".
						($this->app['config']->eventsCantBeMoreThanYearsInPast > 1 ? $this->app['config']->eventsCantBeMoreThanYearsInPast." years"  : "a year" ).
						" in the past."));
			}
			// URL validation. We really can't do much except verify ppl haven't put a space in, which they might do if they just type in Google search terms (seen it done)
			if (strpos($form->get("url")->getData(), " ") !== false) {
				$form['url']->addError(new FormError("Please enter a URL"));
			}
			if (strpos($form->get("ticket_url")->getData(), " ") !== false) {
				$form['ticket_url']->addError(new FormError("Please enter a URL"));
			}
		};

		// adding the validator to the FormBuilderInterface
		$builder->addEventListener(FormEvents::POST_BIND, $myExtraFieldValidator);
	}
	
	public function getName() {
		return 'EventEditForm';
	}
	
	public function getDefaultOptions(array $options) {
		return array(
		);
	}

	/**
	 * @return mixed
	 */
	public function getCustomFields()
	{
		return $this->customFields;
	}


	
}


