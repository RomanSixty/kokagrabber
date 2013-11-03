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
		$all_events = file_get_contents ( 'http://www.koka36.de/showevents.php' );
		#$all_events = file_get_contents ( DIR . '/showevents.php' );

		$events = array();

		phpQuery::newDocument ( $all_events );

		foreach ( pq ( 'a.tipps' ) as $event )
		{
			$link   = pq ( $event ) -> attr ( 'href' );
			$artist = trim ( pq ( $event ) -> html() );

			// sometimes there's no artist, we skip those entries
			if ( empty ( $artist ) )
				continue;

			$events [ $link ] = $artist;
		}

		return $events;
	}

	private function insert ( $new_events )
	{
		$inserts = array();

		foreach ( $new_events as $link => $artist )
			$inserts[] = '("' . $artist . '",
				           "' . $link . '",
							' . time() . ',
							' . time() . ')';

		if ( count ( $inserts ) )
		{
			$insert_chunks = array_chunk ( $inserts, 20 );

			foreach ( $insert_chunks as $insert_chunk )
			{
				$query = 'INSERT INTO koka_events(artist, link, createdate, lastseendate)
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

		$query = 'SELECT id, link
	              FROM koka_events
		          WHERE link IN ("' . implode ( '","', array_keys ( $current_events ) ) . '")';

		if ( ! ( $res = $this -> query ( $query ) ) )
			die ( 'error in database query' );

		$update_times = array();

		while ( $found = $res -> fetchArray ( SQLITE3_ASSOC ) )
		{
			$update_times[] = $found [ 'id' ];
			unset ( $current_events [ $found [ 'link' ]] );
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