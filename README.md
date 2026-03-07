# KoKaGrabber

## About

KoKaGrabber is a simple project to grab the event list from ticket providers and mark newly added events for a better overview. It updates in a maximum frequency of three hours and marks events added in the last 24 hours or since last visit.

The following providers are included:

* koka36.de (R.I.P.)
* Eventim
* Ticketmaster

![Screenshot KoKaGrabber](/screenshot.png?raw=true)

## Features

* pulls all music concert events (for Berlin)
* list view of all concerts
  * new events are marked
  * events on wishlist are highlighted
* filters to show only new/wishlisted events
* sorting by name or by date

## Requirements

The tool uses SQLite as database backend. On Debian GNU/Linux this can be installed using

```
apt install php-sqlite3
```

## Legal

KoKaGrabber is distributed under the GPL, see LICENSE.