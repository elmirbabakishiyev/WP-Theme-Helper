<?php

class ThemeHelper {

	protected $theme_url;
	protected $theme_root;

	function add_theme_support($supports) {
		foreach($supports as $support) {
			add_theme_support($support);
		}
	}

	function load_front_assets(array $styles, array $scripts, array $locals = array()) {
		add_action('wp_enqueue_scripts', function() use ($styles, $scripts, $locals) {

			if (!empty($styles)) {
				foreach ($styles as $style) {
					wp_enqueue_style($style['name'], $style['url']);
				}
			}
			if (!empty($scripts)) {
				foreach ($scripts as $script) {
					wp_enqueue_script($script['name'], $script['url'], $script['deps'], '', true);
				}
			}
			if (!empty($locals)) {
				wp_localize_script( $locals['name'], $locals['object'], $locals['vars'] );
			}
		});
	}

	function load_admin_assets(array $styles, array $scripts, $media_hooks = array(), array $locals = array()) {
		add_action('admin_enqueue_scripts', function($hook) use ($styles, $scripts, $locals, $media_hooks) {
			if (!empty($styles)) {
				foreach ($styles as $style) {
					if ( isset($style['hooks']) and in_array($hook, $style['hooks']) ) {
						wp_enqueue_style( $style['name'], $style['url'] );
					}
				}
			}
			if (!empty($scripts)) {
				foreach ($scripts as $script) {
					if ( isset($script['hooks']) and in_array($hook, $script['hooks']) ) {
						wp_enqueue_script( $script['name'], $script['url'], $script['deps'], '', true );
					}
				}
			}
			if (!empty($locals)) {
				wp_localize_script( $locals['name'], $locals['object'], $locals['vars'] );
			}
			if (!empty($media_hooks) and in_array($hook, $media_hooks)) {
				wp_enqueue_media();
			}
		});
	}

	function display_post_states($post_name, $post_status) {
		add_filter( 'display_post_states',
			function ( $post_states, $post ) use ($post_name, $post_status) {
				if( $post->post_title == $post_name ) {
					$post_states[] = $post_status;
				}
				return $post_states;
			}, 10, 2
		);
	} // end of display_post_states

	function add_custom_field_taxonomy($tax, array $field_params) {

		add_action( 'created_' . $tax, function ( $term_id, $tt_id ) use ($field_params ) {
			if( isset( $_POST[$field_params['id']] ) && '' !== $_POST[$field_params['id']] ){
				$image = $_POST[$field_params['id']];
				add_term_meta( $term_id, $field_params['id'], $image, true );
			}
		}, 10, 2 );

		add_action( 'edited_' . $tax, function  ( $term_id ) use ( $field_params ) {
			if( isset( $_POST[$field_params['id']] ) && '' !== $_POST[$field_params['id']] ){
				$image = $_POST[$field_params['id']];
				update_term_meta ( $term_id, $field_params['id'], $image );
			} else {
				update_term_meta ( $term_id, $field_params['id'], '' );
			}
		}, 10, 2 );

		add_action( $tax . '_add_form_fields', function () use ($field_params ) {

			switch ($field_params['type']) {
				case 'text':
					echo "
						<div class=\"form-field\">
							<label for=\"{$field_params['id']}\">{$field_params['name']}</label>
							<input type=\"text\" name=\"{$field_params['id']}\" id=\"{$field_params['id']}\" value=\"{$field_params['default_value']}\">
						</div>";
					break;
				case 'select':
					$options = '';
					if (!empty($field_params['values'])) {
						foreach ($field_params['values'] as $key => $value) {
							if ($key == $field_params['default_value']) {
								$selected = 'selected';
							} else {
								$selected = '';
							}
							$options .= "<option value=\"{$key}\" {$selected}>{$value}</option>";
						}
					}
					echo "
						<div class=\"form-field\">
							<label for=\"{$field_params['id']}\">{$field_params['name']}</label>
							<select name=\"{$field_params['id']}\">
								" .$options. "
							</select>
						</div>
					";
			} // end of anonymous function

		}, 10, 2 );

		add_action( $tax . '_edit_form_fields', function ( $term, $taxonomy ) use ( $field_params ) {

			switch ($field_params['type']) {
				case 'text':
					$stored_value = get_term_meta ( $term -> term_id, $field_params['id'], true );
					echo "
						<tr class=\"form-field term-group-wrap\">
							<th scope=\"row\">
								<label for=\"{$field_params['id']}\">{$field_params['name']}</label>
							</th>
							<td>
								<input type=\"text\" name=\"{$field_params['id']}\" id=\"{$field_params['id']}\" value=\"{$stored_value}\">
							</td>
						</tr>";
					break;
				case 'select':
					$stored_value = get_term_meta( $term -> term_id, $field_params['id'], true );
					$options = '';
					if (!empty($field_params['values'])) {
						foreach ($field_params['values'] as $key => $value) {
							if ($key == $stored_value) {
								$selected = 'selected';
							} else {
								$selected = '';
							}
							$options .= "<option value=\"{$key}\" {$selected}>{$value}</option>";
						}
					}
					echo "
						<tr class=\"form-field term-group-wrap\">
							<th scope=\"row\">
								<label for=\"{$field_params['id']}\">{$field_params['name']}</label>
							</th>
							<td>
								<select name=\"{$field_params['id']}\">
									" .$options. "
								</select>
							</td>
						</tr>";
					break;
			}

		}, 10, 2 );

	} // end of add_custom_field_category

	function add_custom_tax($tax_name, $tax_slug, $post_type) {

		add_action( 'init', function () use ($tax_name, $tax_slug, $post_type) {
			$labels = array(
				'name'              => $tax_name,
				'singular_name'     => $tax_name,
				'search_items'      => 'Search ' . $tax_name,
				'all_items'         => 'All ' . $tax_name,
				'parent_item'       => 'Parent ' . $tax_name,
				'edit_item'         => 'Edit '. $tax_name,
				'update_item'       => 'Update ' . $tax_name,
				'add_new_item'      => 'Add New ' .  $tax_name,
				'new_item_name'     => 'New '.$tax_name.' Name',
				'menu_name'         => $tax_name,
			);

			register_taxonomy(
				$tax_slug,
				$post_type,
				array(
					'labels' => $labels,
					'rewrite' => array( 'slug' => $tax_slug ),
					'hierarchical' => true,
				)
			);
		} );
	}

	function getAsset($name) {
		return $this->theme_url . '/assets/' . $name;
	}

	function getTemplatePart($name, $params = array(), $once = true) {
		$path = $this->theme_root . '/template-parts/' . $name . '.php';
		if ($once) {
			require_once($path);
		} else {
			require($path);
		}
	}

	function add_menu_page($menu_title, $slug, $capability, $callback_page, $icon = '') {
		add_action( 'admin_menu', function() use ($menu_title, $slug, $capability, $callback_page, $icon) {
			add_menu_page(
				$menu_title,
				$menu_title,
				$capability,
				$slug,
				$callback_page,
				$icon
			);
		} );
	}

	function register_nav_menu($name, $slug) {
		add_action( 'after_setup_theme', function () use ( $name, $slug ) {
			register_nav_menus( array(
				$slug => $name,
			) );
		} );
	}

	function add_metabox($id, $title, $screens, $priority, $callback_page, $context = 'normal') {
		add_action( 'add_meta_boxes', function() use ($id, $title, $screens, $priority, $callback_page, $context){
				add_meta_box( $id, $title, $callback_page, $screens, $context, $priority );
			}
		);
	}

	function save_metabox_data($options_prefix) {
		add_action( 'save_post', function( $post_id ) use ($options_prefix) {
			$options = array();

			foreach ( $_POST as $post_key => $post_val ) {
				if ( substr( $post_key, 0, strlen($options_prefix) + 1 ) === $options_prefix . "_" ) {
					$options[ $post_key ] = $post_val;
				}
			}

			update_post_meta(
				$post_id,
				'_' .$options_prefix. '_options',
				$options
			);
		} );
	}

	function getMenuItemsBySlug( $menu_slug ) {
		$menu_items = array();
		if ( ( $locations = get_nav_menu_locations() ) && isset( $locations[ $menu_slug ] ) ) {
			$menu = get_term( $locations[ $menu_slug ] );
			$menu_items = wp_get_nav_menu_items( $menu->term_id );
		}
		return $menu_items;
	}

	function getMenuWithSubitemsBySlug( $menu_slug ) {
		$menus       = $this->getMenuItemsBySlug( $menu_slug );
		$pretty_menu = [];

		foreach ( $menus as $menu ) {
			if ( $menu->menu_item_parent != 0 ) {
				$pretty_menu[ $menu->menu_item_parent ]['submenus'][ $menu->ID ]['title'] = $menu->title;
				$pretty_menu[ $menu->menu_item_parent ]['submenus'][ $menu->ID ]['url']   = $menu->url;
			} else {
				$pretty_menu[ $menu->ID ]['title'] = $menu->title;
				$pretty_menu[ $menu->ID ]['url']   = $menu->url;
			}
		}
		return $pretty_menu;
	}

	function get_page_url($name) {
		return get_permalink(get_page_by_title($name));
	}

	function register_widget($name, $id, $classes = '') {
		add_action( 'widgets_init', function () use ($name, $id, $classes) {

			register_sidebar( array(
				'name'          => $name,
				'id'            => $id,
				'before_widget' => "<div class='{$classes}'>",
				'after_widget'  => '</div>',
				'before_title'  => '<h3>',
				'after_title'   => '</h3>',
			) );

		} );
	}

} // end of class
