<?php

set_include_path ( get_include_path() . PATH_SEPARATOR . '/usr/lib/phpquery' );

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
        if ( !empty ( $_GET [ 'force_refresh' ] ) )
            return true;

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

        while ( !empty ( $url ) )
        {
            try {
                $all_events = file_get_contents ( $url );
            }
            catch ( Exception $e ) {
                return false;
            }

            if ( empty ( $all_events ) )
                return $events;

            phpQuery::newDocument ( $all_events );

            foreach ( pq ( '.event_box' ) as $event )
            {
                $link   = 'https://www.koka36.de/' . pq ( '.button_view a', pq ( $event ) ) -> attr ( 'href' );
                $artist = trim ( pq ( '.nailthumb-container > img', pq ( $event )) -> attr ( 'alt' ) );

                // sometimes there's no artist, we skip those entries
                if ( empty ( $artist ) )
                    continue;

                preg_match ( '~_([0-9]+)\.html~i', $link, $matches );

                $datum = pq ( '[style="imagefield"] > div:last', pq ( $event ) ) -> text();

                $events [ $matches [ 1 ]] = [
                    'link'      => $link,
                    'artist'    => $artist,
                    'eventdate' => trim ( $datum )
                ];
            }

            // jump to next page, if there is one
            $url = pq ('#site > div:last > a:last') -> attr ( 'href' );

            if ( $url == '#' )
                break;
        }
        return $events;
    }

    public function getEventimEvents ( &$events )
    {
        $category_filter = [ 'Clubkonzerte', 'Electronic & Dance', 'Hard & Heavy', 'HipHop & R’n‘B', 'Jazz & Blues', 'Rock & Pop', 'Weitere Konzerte' ];

        $url = 'https://public-api.eventim.com/websearch/search/api/exploration/v2/productGroups?webId=web__eventim-de&language=de&retail_partner=EVE&categories=Konzerte&city_ids=1&sort=DateAsc&in_stock=true&reco_variant=A&page=';

        $page = 1;

        while ( !empty ( $url ) )
        {
            try {
                $ch = curl_init();

                curl_setopt ( $ch, CURLOPT_URL, $url . $page );

                curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
                curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, true );
                curl_setopt ( $ch, CURLOPT_ENCODING, 'identity' );
                curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:137.0) Gecko/20100101 Firefox/137.0' );

                curl_setopt ( $ch, CURLOPT_HTTPHEADER, [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Encoding: gzip, deflate, br, zstd'
                ]);

                $all_events = curl_exec ( $ch );

                curl_close ( $ch );
            }
            catch ( Exception $e ) {
                return false;
            }

            if ( empty ( $all_events ) )
                return false;

            $data = json_decode ( $all_events, true );

            if ( empty ( $data ) )
                return false;

            foreach ( $data [ 'productGroups' ] as $event )
            {
                if ( $event [ 'status' ] != 'Available' )
                    continue;

                // filter by category

                $category_found = false;

                foreach ( $event [ 'categories' ] as $category )
                    if ( in_array ( $category [ 'name' ], $category_filter ) )
                    {
                        $category_found = true;
                        break;
                    }

                if ( !$category_found )
                    continue;

                // check "products", i.e. the actual events

                foreach ( $event [ 'products' ] as $concert )
                {
                    if ( $concert [ 'status' ] != 'Available' )
                        continue;

                    $date = substr ( $concert [ 'typeAttributes' ][ 'liveEntertainment' ][ 'startDate' ], 0, 10 );

                    list ( $y, $m, $d ) = explode ( '-', $date );

                    $artist = explode ( ' - ', $concert [ 'name' ] );

                    if ( count ( $artist ) > 1 )
                        foreach ( $artist as $key => $part )
                            if ( stristr ( $part, 'Tour' ) || stristr ( $part, 'Tickets' ) || stristr ( $part, 'konzert' ) || strstr ( $part, 'Live' ) || strstr ( $part, 'Europe' ) )
                            {
                                unset ( $artist [ $key ] );

                                if ( count ( $artist ) == 1 )
                                    break;
                            }

                    $events [ $concert [ 'productId' ]] = [
                        'link'      => $concert [ 'link' ],
                        'artist'    => utf8_encode ( implode ( ' - ', $artist ) ),
                        'eventdate' => $d . '.' . $m . '.' . $y
                    ];
                }
            }

            $page++;

            if ( $page > $data [ 'totalPages' ] )
                break;
        }
    }

    private function insert ( $new_events )
    {
        $inserts = array();

        foreach ( $new_events as $id => $data )
            $inserts[] = '('  . $id . ',
                           "' . htmlspecialchars ( $data [ 'artist'    ] ) . '",
                           "' . htmlspecialchars ( $data [ 'link'      ] ) . '",
                           "' . htmlspecialchars ( $data [ 'eventdate' ] ) . '",
                            ' . time() . ',
                            ' . time() . ')';

        if ( count ( $inserts ) )
        {
            $insert_chunks = array_chunk ( $inserts, 20 );

            foreach ( $insert_chunks as $insert_chunk )
            {
                $query = 'INSERT INTO koka_events(id, artist, link, eventdate, createdate, lastseendate)
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

        $update_events = [];

        if ( empty ( $current_events ) )
            return false;

        $this -> getEventimEvents ( $current_events );

        $query = 'SELECT COUNT(id) AS cnt FROM koka_events';

        if ( ! ( $res = $this -> query ( $query ) ) )
            die ( 'error in database query' );

        // if we have less than 90 percent of currently known events
        // something probably went wrong... don't update

        while ( $cur_count = $res -> fetchArray ( SQLITE3_ASSOC ) )
        {
            if ( count ( $current_events ) < intval ( $cur_count [ 'cnt' ] ) * .9 )
                return false;
        }

        $query = 'SELECT id, eventdate
                  FROM koka_events
                  WHERE id IN ("' . implode ( '","', array_keys ( $current_events ) ) . '")';

        if ( ! ( $res = $this -> query ( $query ) ) )
            die ( 'error in database query' );

        $update_times = [];

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
                      WHERE id IN (' . implode ( ',', $update_times ) . ')';

            $this -> exec ( $query );
        }

        // updated event dates
        if ( count ( $update_events ) )
        {
            foreach ( $update_events as $id => $eventdate )
                $this -> exec ( 'UPDATE koka_events SET eventdate = "' . $eventdate . '" WHERE id = ' . $id );
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
