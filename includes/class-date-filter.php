<?php

namespace PosterCast;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Date-based filtering for poster visibility.
 *
 * Builds a WP_Query meta_query that respects _pcast_show_from and _pcast_show_until
 * meta fields, allowing posters to be scheduled for display within a date range.
 */
class Date_Filter {

    /**
     * Get a meta_query array that filters posters by their show_from / show_until dates.
     *
     * A poster is visible when:
     *   - show_from is empty OR show_from <= today
     *   - AND show_until is empty OR show_until >= today
     *
     * @return array WP_Query-compatible meta_query array.
     */
    public static function get_meta_query(): array {
        $today = current_time( 'Y-m-d' );

        return [
            'relation' => 'AND',
            [
                'relation' => 'OR',
                [
                    'key'     => '_pcast_show_from',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => '_pcast_show_from',
                    'value'   => '',
                    'compare' => '=',
                ],
                [
                    'key'     => '_pcast_show_from',
                    'value'   => $today,
                    'compare' => '<=',
                    'type'    => 'DATE',
                ],
            ],
            [
                'relation' => 'OR',
                [
                    'key'     => '_pcast_show_until',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => '_pcast_show_until',
                    'value'   => '',
                    'compare' => '=',
                ],
                [
                    'key'     => '_pcast_show_until',
                    'value'   => $today,
                    'compare' => '>=',
                    'type'    => 'DATE',
                ],
            ],
        ];
    }
}
