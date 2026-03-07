<?php

class koka_update extends SQLite3
{
    var $cnt_koka = 0;
    var $cnt_eventim = 0;

    function __construct()
    {
        $this -> open ( DIR . '/db/kokacache.sqlite' );

        $this -> exec ( file_get_contents ( DIR . '/sql/koka_events.sql' ) );
    }

    private function updateNecessary ( $provider, $update_frequency )
    {
        if ( !empty ( $_GET [ 'force_refresh' ] ) )
            return true;

        $res = $this -> query ( 'SELECT sval FROM koka_settings WHERE skey = "last_update_' . $provider . '"' );

        if ( $found = $res -> fetchArray ( SQLITE3_ASSOC ) )
        {
            return ( $found [ 'sval' ] <= time() - $update_frequency );
        }
        else
        {
            $this -> exec ( 'INSERT INTO koka_settings(skey, sval) VALUES ("last_update_' . $provider . '", 0)' );

            return true;
        }
    }

    /**
     * insert new DB entries
     * @param array $new_events event data
     */
    private function insert ( $new_events, $provider )
    {
        $inserts = array();

        foreach ( $new_events as $id => $data )
            $inserts[] = '("'  . $id . '",
                           "' . htmlspecialchars ( $data [ 'artist'    ] ) . '",
                           "' . htmlspecialchars ( $data [ 'link'      ] ) . '",
                           "' . htmlspecialchars ( $data [ 'eventdate' ] ) . '",
                            ' . time() . ',
                            ' . time() . ',
                           "' . $provider . '")';

        if ( count ( $inserts ) )
        {
            $insert_chunks = array_chunk ( $inserts, 20 );

            foreach ( $insert_chunks as $insert_chunk )
            {
                $query = 'INSERT INTO koka_events(id, artist, link, eventdate, createdate, lastseendate, provider)
                          VALUES ' . implode ( ',', $insert_chunk );

                $this -> exec ( $query );
            }
        }
    }

    /**
     * remove events not found anymore
     * @param string $provider koka36|eventim
     */
    private function purgeOldEvents ( $provider )
    {
        $this -> exec ( 'DELETE FROM koka_events WHERE provider = "' . $provider . '" AND lastseendate < ' . ( time() - 24*3600*60 ) );
        $this -> exec ( 'UPDATE koka_events SET available = 0 WHERE provider = "' . $provider . '" AND lastseendate < ' . ( time() - 24*3600 ) );
    }

    /**
     * update process for all providers
     */
    public function update()
    {
        foreach ( $this -> getProviders() as $provider_name => $provider )
        {
            if ( !$this -> updateNecessary ( $provider_name, $provider -> updateFrequency() ) )
                continue;

            $events = $provider -> getEvents();

            $event_count = $this -> getEventCount ( $provider_name );

            if ( $event_count * .5 > count ( $events ) )
                echo 'too few events for ' . $provider_name . ': ' . count ( $events ) . ' pulled vs. ' . $event_count . ' existing<br/>';
            else
                $this -> dbUpdate ( $events, $provider_name );

            // mark last update time
            $query = 'UPDATE koka_settings
                 SET sval = ' . time() . '
                 WHERE skey = "last_update_' . $provider_name . '"';

            $this -> exec ( $query );
        }
    }

    /**
     * save new events, update timestamps of existing ones
     * @param array $current_events events found in current process
     * @param string $provider koka36|eventim
     */
    function dbUpdate ( $current_events, $provider )
    {
        $query = 'SELECT id, eventdate
                  FROM koka_events
                  WHERE id IN ("' . implode ( '","', array_keys ( $current_events ) ) . '")
                    AND provider = "' . $provider . '"';

        if ( ! ( $res = $this -> query ( $query ) ) )
            die ( 'error in database query' );

        $update_times  = [];
        $update_events = [];

        while ( $found = $res -> fetchArray ( SQLITE3_ASSOC ) )
        {
            $update_times[] = $found [ 'id' ];

            if ( $found [ 'eventdate' ] != $current_events [ $found [ 'id' ]][ 'eventdate' ] )
                $update_events [ $found [ 'id' ]] = $current_events [ $found [ 'id' ]][ 'eventdate' ];

            unset ( $current_events [ $found [ 'id' ]] );
        }

        // lastseen timestamps
        if ( count ( $update_times ) )
        {
            $query = 'UPDATE koka_events
                      SET lastseendate = ' . time() . '
                      WHERE id IN ("' . implode ( '","', $update_times ) . '")
                        AND provider = "' . $provider . '"';

            $this -> exec ( $query );
        }

        // updated event dates
        if ( count ( $update_events ) )
        {
            foreach ( $update_events as $id => $eventdate )
                $this -> exec ( 'UPDATE koka_events
                                 SET eventdate = "' . $eventdate . '"
                                 WHERE id = ' . $id . '
                                   AND provider = "' . $provider . '"' );
        }

        $this -> insert ( $current_events, $provider );
        $this -> purgeOldEvents ( $provider );
    }

    /**
     * get instances of all providers
     * @return array object instances
     */
    private function getProviders()
    {
        chdir ( __DIR__ . '/providers/' );

        $providers = [];

        foreach ( glob ( '*.php' ) as $file )
        {
            include ( $file );

            $classname = str_replace ( '.php', '', $file );

            $providers [ $classname ] = new $classname;
        }

        return $providers;
    }

    /**
     * how many events exist for a provider?
     * @param string $provider koka36|eventim
     * @return int event count
     */
    private function getEventCount ( $provider )
    {
        static $cache = [];

        if ( empty ( $cache ) )
        {
            $query = 'SELECT COUNT(id) AS cnt, provider FROM koka_events GROUP BY provider';

            if ( ! ( $res = $this -> query ( $query ) ) )
                die ( 'error in database query' );

            while ( $cur_count = $res -> fetchArray ( SQLITE3_ASSOC ) )
                $cache [ $cur_count [ 'provider' ]] = intval ( $cur_count [ 'cnt' ] );
        }

        return $cache [ $provider ];
    }
}
