<?php

include ( 'phpQuery.php' );

class koka_update extends SQLite3
{
	function __construct()
	{
		$this -> open ( DIR . '/db/kokacache.sqlite' );

		$this -> exec ( file_get_contents ( DIR . '/sql/koka_events.sql' ) );
	}

	private function updateNecessary()
	{
		$res = $this -> query ( 'SELECT sval FROM koka_settings WHERE skey = "last_update"' );

		if ( $found = $res -> fetchArray ( SQLITE3_ASSOC ) )
		{
			// we only update every 3 hours
			return ( $found [ 'sval' ] <= time() - 3*3600 );
		}
		else
		{
			$this -> exec ( 'INSERT INTO koka_settings(skey, sval) VALUES ("last_update", 0)' );

			return true;
		}
	}

	private function getKokaEvents()
	{
		$events = false;

		$url = 'http://www.koka36.de/events.php?kategorie=Rock%2FPop+%26+More';

		while ( true )
		{
			$all_events = file_get_contents ( $url );

			if ( empty ( $all_events ) )
				return $events;

			phpQuery::newDocument ( $all_events );

			foreach ( pq ( '.event_box' ) as $event )
			{
				$link   = '/' . pq ( '.button_view a', pq ( $event ) ) -> attr ( 'href' );
				$artist = trim ( pq ( '.textfield > div > p', pq ( $event )) -> html() );

				// sometimes there's no artist, we skip those entries
				if ( empty ( $artist ) )
					continue;

				preg_match ( '~_([0-9]+)\.html~i', $link, $matches );

				$events [ $matches [ 1 ]] = array (
					'link'   => $link,
					'artist' => $artist
				);
			}

			// jump to next page, if there is one
			$url = pq('#site > div:last > a:last') -> attr ( 'href' );

			if ( $url == '#' )
				break;
		}

		return $events;
	}

	private function insert ( $new_events )
	{
		$inserts = array();

		foreach ( $new_events as $id => $data )
			$inserts[] = '('  . $id . ',
				           "' . $data [ 'artist' ] . '",
				           "' . $data [ 'link'   ] . '",
							' . time() . ',
							' . time() . ')';

		if ( count ( $inserts ) )
		{
			$insert_chunks = array_chunk ( $inserts, 20 );

			foreach ( $insert_chunks as $insert_chunk )
			{
				$query = 'INSERT INTO koka_events(id, artist, link, createdate, lastseendate)
					      VALUES ' . implode ( ',', $insert_chunk );

				$this -> exec ( $query );
			}
		}
	}

	private function purgeOldEvents()
	{
		$query = 'DELETE FROM koka_events WHERE lastseendate < ' . ( time() - 24*3600 );

		$this -> exec ( $query );
	}

	public function update()
	{
		if ( !$this -> updateNecessary() )
			return;

		$current_events = $this -> getKokaEvents();

		if ( false === $current_events )
			return false;

		$query = 'SELECT id, link
	              FROM koka_events
		          WHERE id IN ("' . implode ( '","', array_keys ( $current_events ) ) . '")';

		if ( ! ( $res = $this -> query ( $query ) ) )
			die ( 'error in database query' );

		$update_times = array();

		while ( $found = $res -> fetchArray ( SQLITE3_ASSOC ) )
		{
			$update_times[] = $found [ 'id' ];
			unset ( $current_events [ $found [ 'id' ]] );
		}

		// lastseen timestamps
		if ( count ( $update_times ) )
		{
			$query = 'UPDATE koka_events
				      SET lastseendate = ' . time() . '
				      WHERE id IN (' . implode ( ',', $update_times ) . ')';

			$this -> exec ( $query );
		}

		$this -> insert ( $current_events );
		$this -> purgeOldEvents();

		// mark last update time
		$query = 'UPDATE koka_settings
			      SET sval = ' . time() . '
				  WHERE skey = "last_update"';

		$this -> exec ( $query );
	}
}
