<?php

class ticketmaster
{
    function updateFrequency()
    {
        // 3 hours
        return 3*3600;
    }

    function getEvents()
    {
        $events = [];

        $url = 'https://www.ticketmaster.de/api/search/events/city?cities=Berlin&stateCodes=&categoryId=KZFzniwnSyZfZ7v7nJ&page=';

        $page = 0;

        while ( true )
        {
            try {
                $ch = curl_init();

                curl_setopt ( $ch, CURLOPT_URL, $url . $page );

                curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
                curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, true );

                curl_setopt ( $ch, CURLOPT_ENCODING, 'identity' );
                curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0' );

                curl_setopt ( $ch, CURLOPT_HTTPHEADER, [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Encoding: gzip, deflate, br, zstd',
                    'Accept-Language: en-US,de-DE;q=0.8,en;q=0.5,de;q=0.3',
                    'DNT: 1'
                ]);

                $all_events = curl_exec ( $ch );

                curl_close ( $ch );
            }
            catch ( Exception $e ) {
                return [];
            }

            if ( empty ( $all_events ) )
            {
                echo 'no Ticketmaster events found (page ' . $page . ')';
                return [];
            }

            $data = json_decode ( $all_events, true );

            if ( empty ( $data ) )
            {
                echo 'error decoding Ticketmaster JSON (page ' . $page . ')';
                return [];
            }

            if ( count ( $data [ 'events' ] ) == 0 )
                break;

            foreach ( $data [ 'events' ] as $event )
            {
                if ( $event [ 'cancelled' ] || $event [ 'virtual' ] )
                    continue;

                $date = substr ( $event [ 'dates' ][ 'startDate' ], 0, 10 );

                list ( $y, $m, $d ) = explode ( '-', $date );

                $artist = preg_split ( '~(\||:| – | - )~', $event [ 'title' ] );

                if ( count ( $artist ) > 1 )
                {
                    $save = '';

                    foreach ( $artist as $key => $part )
                        if (    preg_match ( '~\bTours?\b~i', $part )
                             || stristr ( $part, 'Ticket' )
                             || stristr ( $part, 'Clubtour' )
                             || stristr ( $part, 'konzert' )
                             || stristr ( $part, 'Package' )
                             || stristr ( $part, 'Open Air' )
                             || stristr ( $part, 'Europe' )
                             || strstr ( $part, 'Live' )
                             || strstr ( $part, 'Logen' )
                             || strstr ( $part, 'Premium' )
                             || strstr ( $part, 'UK/EU' )
                             || strstr ( $part, 'VIP' ) )
                        {
                            if ( $key == 0 )
                                $save = $part;

                            unset ( $artist [ $key ] );
                        }

                    if ( empty ( $artist ) )
                        $artist[] = $save;
                }

                $events [ $event [ 'id' ]] = [
                    'link'      => $event [ 'url' ],
                    'artist'    => utf8_encode ( implode ( ' - ', $artist ) ),
                    'eventdate' => $d . '.' . $m . '.' . $y
                ];
            }

            $page++;
        }

        return $events;
    }
}