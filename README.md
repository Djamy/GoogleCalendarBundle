# GoogleCalendarBundle

This bundle use Google API for list, create, or update events in Google Calendar.

Please feel free to contribute, to fork, to send merge request and to create ticket.

##Requirement
### Create a API account

https://console.developers.google.com
Choose Server to Server type


##Installation

### Step 1: Install GoogleCalendarBundle

Run

```bash
composer require djamy/google-calendar-bundle:dev-master
```

### Step 2: Enable the bundle

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Djamy\GoogleCalendarBundle\DjamyGoogleCalendarBundle()
    );
}
```

### Step 3: Configuration
// app/config/parameters.yml
Copy your P12 file for example in app/Resources/GoogleCalendarBundle/XXXX.p12

```yml
    google_calendar:
        api_key_file: PATH TO YOUR P12 FILE # For example app/Resources/GoogleCalendarBundle/XXXX.p12
        api_url: 'https://www.googleapis.com/auth/calendar'
        client_email: XXXX@XXXX.iam.gserviceaccount.com
```
