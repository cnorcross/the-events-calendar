<?php
/*
* Plugin Name: The Events Calendar Multisite Shortcode
* Plugin URI: https://northwoodsdigital.com
* Description: A simple REST API plugin with AJAX pagination to enable the display of events on a subsite of a WordPress Multisite Netork or any other WordPress Website using The Events Calendar by Modern Tribe.
* Version: 1.0
* Author: Mathew Moore
* Author URI: https://northwoodsdigital.com
* License: GPLv2 or later

**************************************************************************

Copyright (C) 2017 Mathew Moore

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

**************************************************************************/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'wp_ajax_nopriv_events_list_ajax_next', 'events_list_ajax_next' );
add_action( 'wp_ajax_events_list_ajax_next', 'events_list_ajax_next' );

function events_list_ajax_next() {

  $page_number = $_POST['page_number'];
  $limit = $_POST['limit'];
  $url = $_POST['url'];
  $excerpt = $_POST['excerpt'];
  $thumbnail = $_POST['thumbnail'];

  if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
    echo do_shortcode('[events-list url="'.$url.'" limit="'.$limit.'" excerpt="'.$excerpt.'" thumbnail="'.$thumbnail.'" page_number="'.$page_number.'"]');
  }
  die();
}

function events_shortcode_by_hike4($atts){
  $a = shortcode_atts(array(
    'limit' => '6',
    'url' => site_url(),
    'excerpt' =>  'true',
    'thumbnail' =>  'true',
    'categories' => '',
    'page_number' => '1',
    'nav' => 'true',
  ), $atts);

  $categories = !empty($a['categories']) ? '&categories='.$a['categories'] : '';

  // Enqueue Ajax Scripts START
  wp_enqueue_script( 'events_list_ajax', plugins_url( '/ajax-list.js', __FILE__ ), array('jquery'), '1.0', true );

  wp_localize_script( 'events_list_ajax', 'events_list', array(
    'ajax_url' => admin_url( 'admin-ajax.php' ),
    'limit' => $a['limit'],
    'url' =>  $a['url'],
    'excerpt' =>  $a['excerpt'],
    'thumbnail' =>  $a['thumbnail'],
    'plugins_url' => plugins_url( '/', __FILE__ ),
  ));
  // Enqueue Ajax Scripts END

  $url = $a['url'].'/wp-json/tribe/events/v1/events?per_page='.$a['limit'].$categories.'&page='.$a['page_number'];
  // if(isset($_REQUEST['prev_page'])) $url = $a['url'].'/wp-json/tribe/events/v1/events?per_page='.$a['limit'].'&page='.$_REQUEST['prev_page'];
  // if(isset($_REQUEST['next_page'])) $url = $a['url'].'/wp-json/tribe/events/v1/events?per_page='.$a['limit'].'&page='.$_REQUEST['next_page'];

  // print_r($url).'</br>';

  // Documentation: https://theeventscalendar.com/knowledgebase/introduction-events-calendar-rest-api/
  $request = wp_remote_get( $url );

	if( is_wp_error( $request ) ) {
		return;
	}

	$event_body = wp_remote_retrieve_body( $request );
  $event_data = json_decode( $event_body );

    if( empty( $event_data ) ) {
      return;
    }
    ob_start();

  	if( !empty( $event_data ) ) {
      // Events List Display START
  		echo '<div class="events-feed" id="events_feed">';
  		foreach( $event_data->events as $event ) {
        $description =  substr(wp_strip_all_tags($event->description),0,180);  // Excerpt Stripped down and cleaned
        $metatags = 'title="'. esc_html($event->title) . PHP_EOL . PHP_EOL . $description . '" alt="'. esc_html($event->title) .'" description="'. esc_html($event->title) .'"';
  			$eventlist = '<div class="event"><h2><a href="' . esc_url($event->url) . '" '. $metatags . '>';
        $eventlist .= esc_html($event->title);
        $eventlist .= '</a></h2>';
        $jsondate = new DateTime( $event->start_date );
        $eventlist .= '<span style="font-size:.8em;display:block;"><b>When:</b> ' . esc_html($jsondate->format("F d, Y"));
        $eventlist .= ' @ ' . esc_html($jsondate->format("g:i A")) . '</span>';
          if( !empty( $event->venue->venue ) ) {
            // Check for empty venue
            $eventlist .= '<span style="font-size:.8em;display:block;"><b>Where:</b> ' . esc_html($event->venue->venue) . '</span></br>';
          }
          if ($a['thumbnail'] == 'true'){
            // Check if thumbnail is being displayed
            if( !empty( $event->image->url ) ) {
              $eventlist .= '<a href="' . esc_url($event->url) . '" ' . $metatags . '">';
              $eventlist .= '<img src="' . esc_url($event->image->url) . '" ' . $metatags . '"/></a>';
            }
          }
          if ($a['excerpt'] == 'true'){
            // Check if excerpt is being displayed
            $eventlist .= '<span style="display:block;">'.esc_html($description).'... <a href="' . esc_url($event->url) . '" '. $metatags . '>continue reading</a></span>';
          }
        $eventlist .= '</div><hr>';
        echo $eventlist;
        // Events List Display END
  		}

      // Paginiation Buttons Function START
      if (!empty($event_data->next_rest_url)) {
        // Function to get the next page number from the json response
        $next_page_func = $event_data->next_rest_url;
        preg_match('/&page=\s*(\d+)/', $next_page_func, $next_page_matches);
        $next_page = $next_page_matches[1];
        // echo 'Next='.$next_page;
        // Function to get the previous page number from the json response
        $previous_page = ($next_page -2) ;
        // echo 'Previous='.$previous_page;
      }
        // Display the Next/Previous Form and Buttons
          $nav_buttons = '<form action="" method="post">';
          if (!empty($previous_page) > 0){
            $nav_buttons .= '<button class="event-paginatate" type="submit" title="Previous Page" name="prev_page" value="'.esc_html($previous_page).'">Previous Page</button>';
          }
          if (  (!empty($next_page) == 0) &&  $event_data->total_pages > 1  ){
            $nav_buttons .= '<button class="event-paginatate" type="submit" title="Previous Page" name="prev_page" value="'.esc_html($_POST['page_number']-1).'">Go Back</button>';
          }
          if (!empty($next_page) > 0){
            $nav_buttons .= '<button class="event-paginatate" style="float:right;" type="submit" title="Next Page" name="next_page" value="'.esc_html($next_page).'">Next Page</button>';
          }
          $nav_buttons .= '</form>';

          if ($a['nav'] == 'true'){
            echo $nav_buttons;
            // Paginiation Buttons Function END
          }

        echo '</div>';
        // Shortcode Function END ALL

      $output = ob_get_contents();
  	}

ob_get_clean();
return $output;

} add_shortcode('events-list','events_shortcode_by_hike4');
