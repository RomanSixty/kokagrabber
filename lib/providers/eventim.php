<?php

class eventim extends koka_provider
{
    function updateFrequency()
    {
        // 3 hours
        return 3*3600;
    }

    function getEvents()
    {
        $events = [];

        $category_filter = [ 'Clubkonzerte', 'Electronic & Dance', 'Hard & Heavy', 'HipHop & R’n‘B', 'Jazz & Blues', 'Rock & Pop', 'Weitere Konzerte' ];

        $url = 'https://public-api.eventim.com/websearch/search/api/exploration/v2/productGroups?webId=web__eventim-de&language=de&retail_partner=EVE&categories=Konzerte&city_ids=1&sort=DateAsc&in_stock=true&reco_variant=A&page=';

        $page = 1;

        while ( true )
        {
            try {
                $ch = curl_init();

                curl_setopt ( $ch, CURLOPT_URL, $url . $page );

                curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
                curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, true );

                curl_setopt ( $ch, CURLOPT_ENCODING, 'identity' );
                curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0' );

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
                echo 'no Eventim events found (page ' . $page . ')';
                return [];
            }

            $data = json_decode ( $all_events, true );

            if ( empty ( $data ) )
            {
                echo 'error decoding Eventim JSON (page ' . $page . ')';
                return [];
            }

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
                            if (    stristr ( $part, 'Tour' )
                                 || stristr ( $part, 'Ticket' )
                                 || stristr ( $part, 'konzert' )
                                 || stristr ( $part, 'Package' )
                                 || stristr ( $part, 'Open Air' )
                                 || strstr ( $part, 'Live' )
                                 || strstr ( $part, 'Europe' )
                                 || strstr ( $part, 'VIP' ) )
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

        return $events;
    }
}