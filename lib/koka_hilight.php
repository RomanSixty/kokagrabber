<?php

class koka_hilight extends SQLite3
{
	function __construct()
	{
		$this -> open ( DIR . '/db/kokacache.sqlite' );
	}

	public function action ( $id, $unhilight = false )
	{
		$id = intval ( $id );

		if ( empty ( $id ) )
			return;

		$hilights = $this -> getHilights();

		// eintragen oder austragen?

		if ( $unhilight === false )
		{
			$hilights[] = $id;
		}
		else
		{
			$key = array_search ( $id, $hilights );

			if ( $key !== false )
				unset ( $hilights [ $key ] );
		}

		// neue Einträge speichern
		$this -> query ( 'REPLACE INTO koka_settings (skey, sval) VALUES ("hilights", "' . implode ( ',', $hilights ) . '")' );
	}

	public function getHilights()
	{
		$res = $this -> query ( 'SELECT sval FROM koka_settings WHERE skey = "hilights"' );

		if ( $found = $res -> fetchArray ( SQLITE3_ASSOC ) )
			$hilights = explode ( ',', $found [ 'sval' ] );
		else
			$hilights = array();

		return $hilights;
	}
}