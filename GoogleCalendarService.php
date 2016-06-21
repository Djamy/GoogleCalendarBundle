<?php

namespace Djamy\GoogleCalendarBundle\Service;

/**
 * Useful functions to interact with the Google API
 *
 * @author Djamy <yo@djamy.me>
 */
class GoogleCalendarService
{
    /**
     * API parameters
     *
     * @var array
     */
    protected $parameters;

    /**
     * Routeur Interface : used to generate your SSL route for push notifications
     *
     * @var \Symfony\Component\Routing\RouterInterface $router
     */
    protected $router;
    
    protected $container;

    /**
     * Constructor
     *
     * @param array                                      $parameters
     * @param \Symfony\Component\Routing\RouterInterface $router
     */
    public function __construct($parameters, $router, $container)
    {
        $this->parameters = $parameters;
        $this->router = $router;
        $this->container = $container;
    }

    /**
     * Return the authenticated Google Client
     *
     * @return \Google_Client
     */
    private function getClient()
    {

        $credentials = new \Google_Auth_AssertionCredentials(
            $this->parameters['client_email'],
            array($this->parameters['api_url']),
            file_get_contents($this->container->get('kernel')->getRootDir() . "/../" . $this->parameters['api_key_file'])
        );

        $client = new \Google_Client();
        $client->setAssertionCredentials($credentials);
        if ($client->getAuth()->isAccessTokenExpired()) {
            $client->getAuth()->refreshTokenWithAssertion();
        }

        return $client;
    }

    /**
     * Return the GoogleCalendar service used to manage google objects
     *
     * @return \Google_Service_Calendar
     */
    private function getCalendarService()
    {
        return new \Google_Service_Calendar($this->getClient());
    }

    /**
     * Add an Event to the specified calendar
     *
     * @param string   $calendarId        Calendar's ID in which you want to insert your event
     * @param datetime $eventStart        Event's start date
     * @param datetime $eventEnd          Event's end date
     * @param string   $eventSummary      Event's title
     * @param string   $eventDescription  Event's description where you should put all your informations
     * @param array    $eventAttendee     Event's attendees : to use the invitation system you should add the calendar owner to the attendees
     * @param array    $optionalParams    Optional params
     *
     * @return object Event
     */
    public function addEvent(
        $calendarId,
        $eventStart,
        $eventEnd,
        $eventSummary,
        $eventDescription,
        $eventAttendee,
        $optionalParams = []
    ) {
        // Your new GoogleEvent object
        $event = new \Google_Service_Calendar_Event();

        // Set the title
        $event->setSummary($eventSummary);

        // Set and format the start date
        $formattedStart = $eventStart->format(\DateTime::RFC3339);
        $formattedEnd = $eventEnd->format(\DateTime::RFC3339);

        $start = new \Google_Service_Calendar_EventDateTime();
        $start->setDateTime($formattedStart);
        $event->setStart($start);
        $end = new \Google_Service_Calendar_EventDateTime();
        $end->setDateTime($formattedEnd);
        $event->setEnd($end);

        // Default status for newly created event
        $event->setStatus('tentative');

        // Set event's description
        $event->setDescription($eventDescription);

        // Attendees - permit to manage the event's status
        $attendee = new \Google_Service_Calendar_EventAttendee();
        $attendee->setEmail($eventAttendee);
        $event->attendees = [$attendee];

        // Event insert
        return $this->getCalendarService()->events->insert($calendarId, $event, $optionalParams);
    }

    /**
     * Create a new chanel to receive Google's push notification when a modification is made on the specified calendar
     *
     * @param string $calendarId
     * @param string $routeName  The name of the secured route. Google requires an HTTPS route
     *
     * @return object
     */
    public function watch($calendarId, $routeName)
    {
        // Route target for push notifications
        $route = $this->router->generate($routeName, array(), true);

        $channel = new \Google_Service_Calendar_Channel($this->getClient());
        $uuid = uniqid();
        $channel->setId($uuid);
        $channel->setType('web_hook');
        $channel->setAddress($route);

        return $this->getCalendarService()->events->watch($calendarId, $channel);
    }

    /**
     * Retrieve modified events from a Google push notification
     *
     * @param string $calendarId
     * @param string $syncToken  Synchronised Token to retrieve last changes
     *
     * @return object
     */
    public function getEvents($calendarId, $syncToken)
    {
        // Option array
        $optParams = [
            'syncToken' => $syncToken
        ];

        return $this->getCalendarService()->events->listEvents($calendarId, $optParams);
    }

    /**
     * Init a full list of events
     *
     * @param string $calendarId
     *
     * @return object
     */
    public function initEventsList($calendarId)
    {
        $eventsList = $this->getCalendarService()->events->listEvents($calendarId);

        return $eventsList->getItems();
    } 
    
    /**
     * Return a sync Token to be stored in your system
     *
     * @param string $calendarId
     *
     * @return string
     */
    public function getFirstSyncToken($calendarId)
    {
        $eventsList = $this->getCalendarService()->events->listEvents($calendarId);

        return $eventsList->getNextSyncToken();
    }

    /**
     * Delete an event
     *
     * @param string $calendarId
     * @param string $eventId
     */
    public function deleteEvent($calendarId, $eventId)
    {
        $this->getCalendarService()->events->delete($calendarId, $eventId);
    }

    /**
     * Update an event
     *
     * @param string $calendarId
     * @param object $event
     */
    public function updateEvent($calendarId, $event)
    {
        $this->getCalendarService()->events->update($calendarId, $event->getId(), $event);
    }

    /**
     * List shared and available calendars
     *
     * @return object
     */
    public function listCalendars()
    {
        return $this->getCalendarService()->calendarList->listCalendarList();
    }

    /**
 * Retrieve Google events on a date range
 *
 * @param string   $calendarId
 * @param datetime $start      Range start
 * @param datetime $end        Range end
 *
 * @return object
 */
    public function getEventsOnRange($calendarId, $start, $end)
    {
        $service = $this->getCalendarService();

        $timeMin = $start->format(\DateTime::RFC3339);
        $timeMax = $end->format(\DateTime::RFC3339);

        // Params to send to Google
        $eventOptions = array(
            'timeMin' => $timeMin,
            'timeMax' => $timeMax,
        );
        $eventList = $service->events->listEvents($calendarId, $eventOptions);

        return $eventList;
    }

    
    /**
     * Retrieve Google events filtered by parameters
     *
     * @param string   $calendarId
     * @param array  $eventOptions
     *
     * @return object
     */
    public function getEventsByParams($calendarId, $eventOptions)
    {
        $service = $this->getCalendarService();

        foreach(['timeMin', 'timeMax', 'updatedMin'] as $opt){
            if(isset($eventOptions[$opt])) $eventOptions[$opt] = $eventOptions[$opt]->format(\DateTime::RFC3339);
        }

        $eventList = $service->events->listEvents($calendarId, $eventOptions);

        return $eventList;
    }
}
