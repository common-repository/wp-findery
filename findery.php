<?php
/*
 * Plugin Name: Findery
 * Plugin URI: http://findery.com
 * Description: A shortcode plugin for embedding content from Findery in Wordpress blogs
 * Version: 0.2
 * Author: Findery
 * Author URI: http://findery.com
 *
 * License: GPLv2 or later
 *
 * Copyright (C) 2012 Findery
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Don't call this file directly.
 */
if ( ! class_exists( 'WP' ) ) {
  die();
}

final class Findery_Embed {

  private static $embed_base = '<iframe src="%s" width="%s" height="%s" scrolling="no" style="1px solid #ccc;" frameborder="no"></iframe>';
  private static $default_width = '500';
  private static $default_height = '400';

  public static function init() {
    // Initialize shortcode handler
    add_shortcode( 'findery', array( __CLASS__, 'findery_shortcode_handler' ) );
    
    // Add filter to transform <iframe> embeds into shortcode embeds.
    add_filter( 'content_save_pre', array( __CLASS__, 'findery_replace_embed' ), 0 );
    
    // Register an embed handler so that standard Findery URLs are embedded automatically
    wp_embed_register_handler('findery', '#https:\/\/findery.com\/(notes|sets|[_a-z0-9]+$|[-_a-z0-9]+\/notes)(?:\/)?([-_a-z0-9]+)?#i', array( __CLASS__, 'findery_embed_handler') );
  }

  /**
   * Replaces <iframe> embeds with shortcode embeds in post content. If
   * we don't replace the <iframe> ourselves, Wordpress will remove it
   * entirely. This is more friendly.
   *  
   * @param  string $content The initial post content, before transformation.
   * @return string          The new post content, after we replace Findery <iframe>s.
   */
  public static function findery_replace_embed( $content ) {
    if ( preg_match_all('#(?:<|&lt;)iframe.*?\/\/findery.com\/embed.*?(?:>|&gt;).*?(?:<|&lt;)\/iframe(?:>|&gt;)#i', $content, $matches) ) {
      
      foreach( $matches[0] as $match ) {
        
        $unquoted = str_replace('"', "", $match);
        preg_match('/src=([^\s]+)/', $unquoted, $src);
        preg_match('/width=([^\s]+)/', $unquoted, $width);
        preg_match('/height=([^\s]+)/', $unquoted, $height);

        if ( $src[1] ) {

          if ( $width[1] ) { $width = " width=\"{$width[1]}\""; } 
          else { $width = ''; }
          
          if ( $height[1] ) { $height = " height=\"{$height[1]}\""; } 
          else { $height = ''; }

          $src = str_replace("embed/", "", $src[1]);
          $content = str_replace($match, "[findery {$src} {$width}{$height}]", $content);

        }    
      }
    }
    return $content;
  }

  /**
   * Handler for automatic embedding of Findery links.
   */
  public static function findery_embed_handler( $matches, $attr, $url, $rawattr ) {

    if ( ! empty( $rawattr['width'] ) && ! empty( $rawattr['height'] ) ) {
      $width  = $rawattr['width'];
      $height = $rawattr['height'];
    } else {
      list( $width, $height ) = wp_expand_dimensions( self::$default_width, self::$default_height, $attr['width'], $attr['height'] );
    }

    return sprintf(self::$embed_base,
                   preg_replace('/([^\/:])\//', '${1}/embed/', $url, 1),
                   $width,
                   $height);
  }

  /**
   * Replaces Findery shortcodes with the proper <iframe> embed code.
   * @param  array $atts Attributes parsed from the shortcode
   * @return string      The code with which the shortcode is replaced.
   */
  public static function findery_shortcode_handler( $atts ) {
    extract( shortcode_atts( array(
      'w' => self::$default_width,
      'h' => self::$default_height
    ), $atts ) );

    $src = preg_replace('/([^\/:])\//', '${1}/embed/', $atts[0], 1);
    $src = esc_url( preg_replace('/([^\/:])\//', '${1}/embed/', $atts[0], 1) );
    if ( 'findery.com' != parse_url( $src, PHP_URL_HOST ) )
      return;
    
    $w = (int)$w;
    $h = (int)$h;

    return "<iframe width=\"{$w}\" height=\"{$h}\" src=\"{$src}\" frameborder=\"no\" scrolling=\"no\" style=\"border:1px solid #ccc;\"></iframe>";

  }

}

/**
 * Initialize plugin
 */

add_action( 'init', array( 'Findery_Embed', 'init' ) );