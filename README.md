# GoogleCalendarBundle

This bundle use Google API for list, create, or update events in Google Calendar.

Please feel free to contribute, to fork, to send merge request and to create ticket.

##Requirement
### Create a API account

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