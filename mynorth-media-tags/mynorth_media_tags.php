<?php
/**
 * Plugin Name: MyNorth Media Tags
 * Description: Media taxonomy plugin meant as a drop-in replacement for <a href="http://wordpress.org/extend/plugins/media-tags/">Media-Tags plugin</a> in WordPress 3.5+
 * Author:      MyNorthMedia
 * Author URI:  http://mynorthmedia.com
 * Text Domain: mnm_attachment_taxonomies
 * Version:     1.0.0
 * License:     GPLv3
 */


/**
 * Copyright 2013 MyNorthMedia (hello@mynorthmedia.com)
 *
 * This file is part of MyNorth Media Tags.
 *
 * MyNorth Media Tags is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * MyNorth Media Tags is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MyNorth Media Tags. If not, see <http://www.gnu.org/licenses/>.
 *
 */


/**
 * Based almost entirely on:
 * http://bueltge.de/taxonomien-im-wordpress-media-manager-nutzen/1474/
 *
 * MyNorthMedia changes:
 *  - localization and taxonomy name adjustments
 *  - addition of legacy template functions from Media-Tags plugin
 *  - addition of helper functions
 *  - addition of new template functions
 *
 * The license above is GPLv3, and we're stickinfg to it. We did not create
 * the base class used in this plugin, just modified existing work, so you
 * are welcome to do the same.
 *
 * For general FSF/GPLv3 info:
 * http://www.gnu.org/licenses/quick-guide-gplv3.html
 *
 * A copy of the GPLv3 should exist alongside as license.txt, and if you
 * keep attribution to MyNorthMedia and Frank Bültge we'll send you a...
 * FREE KITTEN!
 *
 * Note that the GPLv3 does not cover the kitten and we would strongly
 * discourage derivative works. Because...genetics are hard/dangerous.
 *
 */


if (function_exists( 'add_filter' )) {
    add_action( 'plugins_loaded', array( 'MnM_Attachment_Taxonomies', 'get_object' ) );
}


/**
 * Add Tags taxonomies to attachments with WP 3.5+
 */
class MnM_Attachment_Taxonomies {

    static private $classobj;

    /**
     * Constructor, init the functions inside WP
     *
     * @since   1.0.0
     * @return  void
     */
    public function __construct() {

        // load translation files
        add_action( 'admin_init', array( $this, 'localize_plugin' ) );
        // add taxonmies
        add_action( 'init', array( $this, 'setup_taxonomies' ) );
    }

    /**
     * Handler for the action 'init'. Instantiates this class.
     *
     * @since   1.0.0
     * @access  public
     * @return  $classobj
     */
    public function get_object() {

        if ( NULL === self::$classobj ) {
            self::$classobj = new self;
        }

        return self::$classobj;
    }

    /**
     * Localize plugin function.
     *
     * @uses    load_plugin_textdomain, plugin_basename
     * @since   2.0.0
     * @return  void
     */
    public function localize_plugin() {

        load_plugin_textdomain(
            'mnm_attachment_taxonomies',
            FALSE,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );
    }

    /**
     * Setup Taxonomies
     * Creates 'attachment_tag' and 'attachment_category' taxonomies.
     * Enhance via filter `mnm_attachment_taxonomies`
     *
     * @uses    register_taxonomy, apply_filters
     * @since   1.0.0
     * @return  void
     */
    public function setup_taxonomies() {

        $attachment_taxonomies = array();

        // Tags
        $labels = array(
            'name'              => _x( 'Media Tags', 'taxonomy general name', 'mnm_attachment_taxonomies' ),
            'singular_name'     => _x( 'Media Tag', 'taxonomy singular name', 'mnm_attachment_taxonomies' ),
            'search_items'      => __( 'Search Media Tags', 'mnm_attachment_taxonomies' ),
            'all_items'         => __( 'All Media Tags', 'mnm_attachment_taxonomies' ),
            'parent_item'       => __( 'Parent Media Tag', 'mnm_attachment_taxonomies' ),
            'parent_item_colon' => __( 'Parent Media Tag:', 'mnm_attachment_taxonomies' ),
            'edit_item'         => __( 'Edit Media Tag', 'mnm_attachment_taxonomies' ),
            'update_item'       => __( 'Update Media Tag', 'mnm_attachment_taxonomies' ),
            'add_new_item'      => __( 'Add New Media Tag', 'mnm_attachment_taxonomies' ),
            'new_item_name'     => __( 'New Media Tag Name', 'mnm_attachment_taxonomies' ),
            'menu_name'         => __( 'Media Tags', 'mnm_attachment_taxonomies' ),
        );

        $args = array(
            'hierarchical' => FALSE,
            'labels'       => $labels,
            'show_ui'      => TRUE,
            'show_admin_column' => TRUE,
            'query_var'    => TRUE,
            'rewrite'      => TRUE,
        );

        $attachment_taxonomies[] = array(
            'taxonomy'  => 'media-tags',
            'post_type' => 'attachment',
            'args'      => $args
        );

        $attachment_taxonomies = apply_filters( 'mnm_attachment_taxonomies', $attachment_taxonomies );

        foreach ( $attachment_taxonomies as $attachment_taxonomy ) {
            register_taxonomy(
                $attachment_taxonomy['taxonomy'],
                $attachment_taxonomy['post_type'],
                $attachment_taxonomy['args']
            );
        }

    }

} // end class


/**
 * Utility / Helper Functions
 *
 * Used for this plugin only.
 */

function split_and_clean_array($str='') {
  $arr = split(',', $str);

  // If we've got anything, perform an in-place title
  // adjustment using WP's sanitize function
  if ($arr) {
    foreach($arr as $i => $val) {
      $arr[$i] = sanitize_title_with_dashes($val);
    }
  }

  return $arr;
}



/**
 * Template Functions
 *
 * Legacy template functions based heavily off of the template functions used
 * by Media Tags. (read: seasoned copypasta)
 *
 */

function get_attachments_by_tags($args='', $legacy_mode=false) {

  $defaults = array(
    'exclude'        => null,
    'media_tags'     => '',
    'media_types'    => null,
    'numberposts'    => '-1',
    'offset'         => '0',
    'order'          => 'ASC',
    'orderby'        => 'menu_order',
    'post_mime_type' => 'image',
    'post_parent'    => null,
    'post_type'      => 'attachment',
  );

  // "$s" for "settings" from here on out
  $s = wp_parse_args($args, $defaults);

  // Make sure our array merge worked
  if (!$s['media_tags'] || strlen($s['media_tags']) == 0)
    return;

  $s['exclude']     = split_and_clean_array($s['exclude']);
  $s['media_tags']  = split_and_clean_array($s['media_tags']);
  $s['media_types'] = split_and_clean_array($s['media_types']);

  // Legacy Media-Tags didn't handle exclusions properly
  // and some templates are relying on this
  if ($legacy_mode)
    $s['exclude'] = array();

  $attachments = get_posts(array(
    'numberposts'    => $s['numberposts'],
    'offset'         => $s['offset'],
    'order'          => $s['order'],
    'orderby'        => $s['orderby'],
    'post__not_in'   => $s['exclude'],
    'post_mime_type' => $s['media_types'],
    'post_parent'    => $s['post_parent'],
    'post_type'      => $s['post_type'],
    'tax_query'      => array(
      array(
        'taxonomy'   => 'media-tags',
        'field'      => 'slug',
        'terms'      => $s['media_tags'],
      )
    )
  ));

  return $attachments;

} // end get_attachments_by_tags()


function get_attachments_by_media_tags($args='') {
  // "true" attr throws get_attachments_by_tags into legacy mode
  // so that templates expecting Media-Tags functions don't whine

  return get_attachments_by_tags($args, true);
}


// EOF -- how's it going?
