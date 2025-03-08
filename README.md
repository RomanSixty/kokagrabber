# KoKaGrabber

## About

KoKaGrabber is a simple project to grab the event list from koka36.de and mark newly added events for a better overview. It updates in a maximum frequency of three hours and marks events added in the last 24 hours or since last visit.

![Screenshot KoKaGrabber](/screenshot.png?raw=true)

## Features

* pulls all concert events from koka36.de
* list view of all concerts
  * new events are marked
  * events on wishlist are highlighted
* filters to show only new/wishlisted events
* sorting by name or by date

## Requirements

The tool uses phpQuery to scrape the events from an HTML page. It's no longer maintained, but you can get it from here:

https://code.google.com/archive/p/phpquery/downloads

The default inclusion path is set to `/usr/lib/phpquery`, you can change that in `/lib/koka_update.php`.

Also the tool uses SQLite as database backend. On Debian GNU/Linux this can be installed using

```
apt install php-sqlite3
```

## Legal

KoKaGrabber is distributed under the GPL, see LICENSE.