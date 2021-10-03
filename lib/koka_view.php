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

    private function getLastUpdate()
    {
        $res = $this -> query ( 'SELECT sval FROM koka_settings WHERE skey = "last_update"' );

        if ( $found = $res -> fetchArray ( SQLITE3_ASSOC ) )
            return $found [ 'sval' ];
        else
            return 0;
    }

	private function eventSnipplet ( $event )
	{
		static $html = null;

		if ( $html === null )
			$html = file_get_contents ( DIR . '/templates/listentry.html' );

		$replacements = array (
			'%%%ID%%%'      => $event [ 'id'        ],
			'%%%LINK%%%'    => $event [ 'link'      ],
			'%%%ARTIST%%%'  => $event [ 'artist'    ],
			'%%%DATUM%%%'   => $event [ 'eventdate' ],
			'%%%CLASSES%%%' => $event [ 'classes'   ]
		);

		return strtr ( $html, $replacements );
	}

	private function getAllEvents()
	{
		$events     = array();
		$last_visit = $this -> getLastVisit();

		$res = $this -> query ( 'SELECT * FROM koka_events ORDER BY LOWER(artist) ASC' );

		// Favoriten
		$kh = new koka_hilight();
		$hilights = $kh -> getHilights();

		while ( $event = $res -> fetchArray ( SQLITE3_ASSOC ) )
		{
			$classes = array();

			// set classes depending on timestamps
			// everything newer than 24hours ago OR the last visit date is marked as "new"
			if ( min ( $last_visit, time() - 3*3600 ) <= $event [ 'createdate' ] )
				$classes[] = 'new';

			// everything with last seen date older than 3 hours ago is marked as "old"
			if ( time() - 3*3600 > $event [ 'lastseendate' ] )
				$classes[] = 'old';

			if ( in_array ( $event [ 'id' ], $hilights ) )
				$classes[] = 'hilight';

			$event [ 'classes' ] = implode ( ' ', $classes );

			$events[] = $this -> eventSnipplet ( $event );
		}

		return implode ( "\n", $events );
	}

	public function show()
	{
		$html = file_get_contents ( DIR . '/templates/main.html' );

		$html = str_replace ( '%%%EVENTS%%%', $this -> getAllEvents(), $html );
		$html = str_replace ( '%%%LASTUPDATE%%%', date ( 'Y-m-d H:i:s', $this -> getLastUpdate() ), $html );

		echo $html;
	}
}