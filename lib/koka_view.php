<?php

class koka_view extends SQLite3
{
	function __construct()
	{
		$this -> open ( DIR . '/db/kokacache.sqlite' );
	}

	private function getLastVisit()
	{
		static $last_visit = null;

		if ( $last_visit === null )
		{
			$res = $this -> query ( 'SELECT sval FROM koka_settings WHERE skey = "last_visit"' );

			if ( $found = $res -> fetchArray ( SQLITE3_ASSOC ) )
			{
				$last_visit = $found [ 'sval' ];
			}
			else
			{
				$this -> exec ( 'INSERT INTO koka_settings(skey, sval) VALUES ("last_visit", 0)' );

				$last_visit = 0;
			}

			$this -> exec ( 'UPDATE koka_settings SET sval = ' . time() . ' WHERE skey = "last_visit"' );
		}

		return $last_visit;
	}

	private function eventSnipplet ( $event )
	{
		static $html = null;

		if ( $html === null )
			$html = file_get_contents ( DIR . '/templates/listentry.html' );

		$replacements = array (
			'%%%LINK%%%'    => $event [ 'link' ],
			'%%%ARTIST%%%'  => $event [ 'artist' ],
			'%%%CLASSES%%%' => $event [ 'classes' ]
		);

		return strtr ( $html, $replacements );
	}

	private function getAllEvents()
	{
		$events     = array();
		$last_visit = $this -> getLastVisit();

		$res = $this -> query ( 'SELECT * FROM koka_events ORDER BY artist ASC' );

		while ( $event = $res -> fetchArray ( SQLITE3_ASSOC ) )
		{
			$classes = array();

			// set classes depending on timestamps
			// everything newer than 24hours ago OR the last visit date is marked as "new"
			$classes[] = ( min ( $last_visit, time() - 3*3600 ) <= $event [ 'createdate' ] )
					   ? 'new'
					   : '';

			// everything with last seen date older than 3 hours ago is marked as "old"
			$classes[] = ( time() - 3*3600 > $event [ 'lastseendate' ] )
					   ? ' old'
					   : '';

			$event [ 'classes' ] = implode ( ' ', $classes );

			$events[] = $this -> eventSnipplet ( $event );
		}

		return implode ( "\n", $events );
	}

	public function show()
	{
		$html = file_get_contents ( DIR . '/templates/main.html' );

		$html = str_replace ( '%%%EVENTS%%%', $this -> getAllEvents(), $html );

		echo $html;
	}
}