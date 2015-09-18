<?php

/**
 * Related Posts
 * -----------------------------------------------------------------------------
 * @category   PHP Script
 * @package    Sheepie Related Posts
 * @author     Mark Grealish <mark@bhalash.com>
 * @copyright  Copyright (c) 2015 Mark Grealish
 * @license    https://www.gnu.org/copyleft/gpl.html The GNU General Public License v3.0
 * @version    3.0
 * @link       https://github.com/bhalash/sheepie-related-posts
 */

if (!defined('ABSPATH')) {
    die('-1');
}

/**
 * Get Related Posts from the Same Category
 * -----------------------------------------------------------------------------
 * Fetch posts related to given post, by category.
 * 
 * @param   int/object        $post       Post object.
 * @param   int               $count      Number of related posts to fetch.
 * @param   int               $timeout    Delay in hours for transient API. 
 * @param   array             $range      Date range to to back in time.
 * @return  array             $related    Array of related posts.
 */

function rp_get_related($args) { 
    $defaults = array(
        'post' => get_the_id(),
        'count' => 3,
        'range' => array(
            'after' => date('Y-m-j') . '-21 days',
            'before' => date('Y-m-j')
        )
    );

    $args = wp_parse_args($args, $defaults);

    if (!($post = get_post($args['post']))) {
        global $post;
    }

    $query_cat = array();

    if (!($categories = get_the_category($post->ID))) {
        $categories = get_option('default_category');
    }

    foreach ($categories as $cat) {
        $query_cat[] = $cat->cat_ID;
    }

    $related = get_posts(array(
        'category__in' => $query_cat,
        'date_query' => array(
            'inclusive' => true,
            'after' => $args['range']['after'],
            'before' => $args['range']['before']
        ),
        'numberposts' => $args['count'],
        'order' => 'DESC',
        'orderby' => 'rand',
        'perm' => 'readable',
        'post_status' => 'publish',
        'post__not_in' => array($post->ID)
    )); 

    if ($missing = $args['count'] - sizeof($related)) {
        // Filler isn't cached because that could cause problems.
        $related = rp_related_filler($post, $missing, $related);
    }

    return $related;
}

/**
 * Related Posts Filler
 * -----------------------------------------------------------------------------
 * @param   int/object        $post       Post object.
 * @param   int               $count      Number of related posts to fetch.
 * @param   array             $related    Array of related posts to exclude.
 * @return  array             Filler posts.
 */

function rp_related_filler($post, $count, $related) {
    $exlude = array(); 
    $exclude[] = $post->ID;

    foreach ($related as $r) {
        $exclude[] = $r->ID;
    }
    
    $filler = get_posts(array(
        'numberposts' => $count,
        'order' => 'DESC',
        'orederby' => 'rand',
        'post__not_in' => $exclude
    ));

    return array_merge($related, $filler);
}

?>
