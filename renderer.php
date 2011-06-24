<?php
/**
 * @see format_flexpage_renderer
 */
require_once($CFG->dirroot.'/course/format/flexpage/renderer.php');

/**
 * Plugin Renderer
 *
 * @author Mark Nielsen
 * @package block_flexpagenav
 */
class block_flexpagenav_renderer extends format_flexpage_renderer {
    /**
     * The javascript module used by the presentation layer
     *
     * @return array
     */
    public function get_js_module() {
        return array(
            'name'      => 'block_flexpagenav',
            'fullpath'  => '/blocks/flexpagenav/javascript.js',
            'requires'  => array(
                'base',
                'node',
                'event-custom',
                'json-parse',
                'yui2-yahoo',
                'yui2-dom',
                'yui2-event',
                'yui2-element',
                'yui2-button',
                'yui2-container',
                'yui2-menu',
            ),
            'strings' => array(
                array('savechanges'),
                array('cancel'),
                array('addlinkdotdotdot', 'block_flexpagenav'),
                array('labelurlrequired', 'block_flexpagenav'),
                array('labelrequired', 'block_flexpagenav'),
                array('movelink', 'block_flexpagenav'),
                array('deletelink', 'block_flexpagenav'),
                array('deletemenu', 'block_flexpagenav'),
            )
        );
    }

    /**
     * Render the menu model
     *
     * @param block_flexpagenav_model_menu $menu
     * @return string
     */
    public function render_block_flexpagenav_model_menu(block_flexpagenav_model_menu $menu) {
        /** @var $render block_flexpagenav_lib_render_abstract */
        $render = mr_helper::get('blocks/flexpagenav')->load(
            'lib/render/'.$menu->get_render(),
            array($menu)
        );
        return $render->output();
    }

    /**
     * @param moodle_url $submiturl
     * @param string|course_format_flexpage_lib_box $content
     * @return string
     */
    public function form_wrapper(moodle_url $submiturl, $content) {
        if ($content instanceof course_format_flexpage_lib_box) {
            $content = $this->render($content);
        }
        return html_writer::start_tag('form', array('method' => 'post', 'action' => $submiturl->out_omit_querystring())).
               html_writer::input_hidden_params($submiturl).
               $content.
               html_writer::end_tag('form');
    }

    /**
     * Create hierarchy of child pages
     *
     * @param course_format_flexpage_model_page $parent
     * @param course_format_flexpage_model_cache $cache
     * @param array $exclude
     * @return closure|string
     */
    public function child_pages_list(course_format_flexpage_model_page $parent, course_format_flexpage_model_cache $cache, array $exclude) {
        /**
         * Little helper function to close li and ul tags
         */
        $closeliul = function($amount) {
            $output = '';
            for ($i = 0; $i < $amount; $i++) {
                $output .= html_writer::end_tag('li');
                $output .= html_writer::end_tag('ul');
            }
            return $output;
        };

        $lastpage = $parent;
        $output   = '';
        $opened   = 0;
        foreach ($cache->get_pages() as $page) {
            if ($cache->is_child_page($parent, $page)) {
                // If not in exclude list, make sure parent isn't either
                if (!in_array($page->get_id(), $exclude)) {
                    if (in_array($page->get_parentid(), $exclude)) {
                        $exclude[] = $page->get_id();
                    }
                }
                $checkbox  = html_writer::checkbox('exclude[]', $page->get_id(), (!in_array($page->get_id(), $exclude)), format_string($page->get_display_name()));
                $depth     = $cache->get_page_depth($page);
                $lastdepth = $cache->get_page_depth($lastpage);

                if ($depth > $lastdepth) {
                    $opened++;
                    $output .= html_writer::start_tag('ul');
                    $output .= html_writer::start_tag('li');
                    $output .= $checkbox;
                } else if ($depth == $lastdepth) {
                    $output .= html_writer::end_tag('li');
                    $output .= html_writer::start_tag('li');
                    $output .= $checkbox;

                } else {
                    if ($depth < $lastdepth) {
                        $opened  = $opened - ($lastdepth - $depth);
                        $output .= $closeliul(($lastdepth - $depth));
                    }
                    $output .= html_writer::start_tag('li');
                    $output .= $checkbox;
                }
                $lastpage = $page;
            }
        }
        if (empty($output)) {
            $output = get_string('nochildpages', 'block_flexpagenav');
        } else {
            $output .= $closeliul($opened);
        }
        return $output;
    }

    /**
     * Add existing menu modal content
     *
     * @param moodle_url $sumiturl
     * @param block_flexpagenav_model_menu[] $menus
     * @return string
     */
    public function add_existing_menu(moodle_url $sumiturl, array $menus) {
        $form = html_writer::start_tag('form', array('method' => 'post', 'action' => $sumiturl->out_omit_querystring())).
                html_writer::input_hidden_params($sumiturl).
                html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'region', 'value' => '')).
                html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'menuid', 'value' => '')).
                html_writer::tag('div', get_string('addto', 'format_flexpage'), array('class' => 'format_flexpage_addactivity_heading')).
                html_writer::tag('div', '', array('id' => 'format_flexpage_region_radios')).
                html_writer::end_tag('form');

        $title = html_writer::tag('div', get_string('menus', 'block_flexpagenav').':', array('class' => 'format_flexpage_addactivity_heading'));

        $box = new course_format_flexpage_lib_box();
        $box->add_new_row()->add_new_cell($form);
        $box->add_new_row()->add_new_cell($title);
        $row = $box->add_new_row(array('id' => 'block_flexpagenav_addmenu_links'));

        foreach ($menus as $menu) {
            $link = clone($sumiturl);
            $link->param('menuid', $menu->get_id());

            $items[] = html_writer::link($link, format_string($menu->get_name()), array('name' => $menu->get_id()));
        }
        $row->add_new_cell(html_writer::alist($items));

        return $this->render($box);
    }

    /**
     * Manage menus modal content
     *
     * @param moodle_url $url
     * @param block_flexpagenav_model_menu[] $menus
     * @return string
     */
    public function manage_menus(moodle_url $url, array $menus) {
        $actions = array('editmenu', 'managelinks', 'deletemenu');

        $output = html_writer::empty_tag('input', array('id' => 'addmenu', 'type' => 'button', 'value' => get_string('addmenudotdotdot', 'block_flexpagenav')));

        if (!empty($menus)) {
            $box = new course_format_flexpage_lib_box(array('class' => 'format_flexpage_box_managepages'));
            $row = $box->add_new_row(array('class' => 'format_flexpage_box_headers'));
            $row->add_new_cell(get_string('name', 'block_flexpagenav'))
                ->add_new_cell(get_string('manage', 'block_flexpagenav'))
                ->add_new_cell(get_string('usedastabs', 'block_flexpagenav'));

            foreach ($menus as $menu) {
                $options = array();
                foreach ($actions as $action) {
                    $actionurl = clone($url);
                    $actionurl->param('action', $action);
                    $actionurl->param('menuid', $menu->get_id());

                    $option = json_encode((object) array(
                        'action' => $action,
                        'url' => $actionurl->out(false),
                    ));
                    $options[$option] = get_string($action.'action', 'block_flexpagenav');
                }
                $actionselect = html_writer::select($options, 'actions', '', false, array(
                    'id'    => html_writer::random_id(),
                    'class' => 'block_flexpagenav_actions_select'
                ));

                $box->add_new_row()->add_new_cell(format_text($menu->get_name()))
                                   ->add_new_cell($actionselect, array('id' => html_writer::random_id()))
                                   ->add_new_cell(($menu->get_useastab() ? get_string('yes') : get_string('no')));
            }
            $output .= $this->render($box);
        }
        return $output;
    }

    /**
     * Edit menu modal content
     *
     * @param moodle_url $submiturl
     * @param block_flexpagenav_model_menu $menu
     * @return string
     */
    public function edit_menu(moodle_url $submiturl, block_flexpagenav_model_menu $menu) {
        $box = new course_format_flexpage_lib_box(array('class' => 'format_flexpage_form'));

        $box->add_new_row()->add_new_cell(html_writer::label(get_string('name', 'block_flexpagenav'), 'id_name'))
                           ->add_new_cell(html_writer::empty_tag('input', array('id' => 'id_name', 'name' => 'name', 'type' => 'text', 'size' => 50, 'value' => $menu->get_name())));

        $box->add_new_row()->add_new_cell(html_writer::label(get_string('render', 'block_flexpagenav'), 'id_render'))
                           ->add_new_cell(html_writer::select(block_flexpagenav_model_menu::get_render_options(), 'render', $menu->get_render(), false, array('id' => 'id_render')));

        $box->add_new_row()->add_new_cell(html_writer::label(get_string('displayname', 'block_flexpagenav'), 'id_displayname'))
                           ->add_new_cell(html_writer::checkbox('displayname', 1, ($menu->get_displayname() == 1), '', array('id' => 'id_displayname')));

        $box->add_new_row()->add_new_cell(html_writer::label(get_string('useastab', 'block_flexpagenav'), 'id_useastab'))
                           ->add_new_cell(html_writer::checkbox('useastab', 1, ($menu->get_useastab() == 1), '', array('id' => 'id_useastab')));

        return $this->form_wrapper($submiturl, $box);
    }

    /**
     * Delete link modal
     *
     * @param moodle_url $submiturl
     * @param block_flexpagenav_model_menu $menu
     * @return string
     */
    public function delete_menu(moodle_url $submiturl, block_flexpagenav_model_menu $menu) {
        $areyousure = get_string('areyousuredeletemenu', 'block_flexpagenav', format_string($menu->get_name()));
        return $this->form_wrapper($submiturl, html_writer::tag('div', $areyousure, array('class' => 'block_flexpagenav_deletemenu')));
    }

    /**
     * Mange links modal content
     *
     * @param moodle_url $url
     * @param block_flexpagenav_model_menu $menu
     * @return string
     */
    public function manage_links(moodle_url $url, block_flexpagenav_model_menu $menu) {
        global $PAGE;

        /** @var $types block_flexpagenav_lib_link_abstract[] */
        $types   = mr_helper::get('blocks/flexpagenav')->load('lib/link/**');
        $options = array();
        foreach ($types as $type) {
            $actionurl = clone($url);
            $actionurl->params(array(
                'action' => 'editlink',
                'type' => $type->get_type(),
                'menuid' => $menu->get_id(),
            ));

            $option = json_encode((object) array(
                'action' => 'editlink',
                'url' => $actionurl->out(false),
            ));
            $options[$option] = $type->get_name();
        }
        $output = html_writer::select($options, 'actions', '', false, array(
            'id'    => html_writer::random_id(),
            'class' => 'block_flexpagenav_addlink_select'
        ));
        $output = html_writer::tag('div', $output, array('id' => html_writer::random_id()));
        $links  = $menu->get_links();
        if (!empty($links)) {
            $actions = array('editlink', 'movelink', 'deletelink');

            $box = new course_format_flexpage_lib_box(array('class' => 'format_flexpage_box_managepages'));
            $row = $box->add_new_row(array('class' => 'format_flexpage_box_headers'));
            $row->add_new_cell(get_string('type', 'block_flexpagenav'))
                ->add_new_cell(get_string('manage', 'block_flexpagenav'))
                ->add_new_cell(get_string('preview', 'block_flexpagenav'));

            foreach ($links as $link) {
                $options = array();
                foreach ($actions as $action) {
                    $actionurl = clone($url);
                    $actionurl->params(array(
                        'action' => $action,
                        'linkid' => $link->get_id(),
                        'type' => $link->get_type(),
                        'menuid' => $menu->get_id(),
                    ));

                    $option = json_encode((object) array(
                        'action' => $action,
                        'url' => $actionurl->out(false),
                    ));
                    $options[$option] = get_string($action.'action', 'block_flexpagenav');
                }
                $actionselect = html_writer::select($options, 'actions', '', false, array(
                    'id'    => html_writer::random_id(),
                    'class' => 'block_flexpagenav_actions_select'
                ));
                $box->add_new_row()->add_new_cell(format_text($link->load_type()->get_name()))
                                   ->add_new_cell($actionselect, array('id' => html_writer::random_id()))
                                   ->add_new_cell($link->load_type()->get_info());
            }
            $output .= $this->render($box);
        }
        return $PAGE->get_renderer('local_mr')->render(new mr_html_notify('format_flexpage')).
               $output;
    }

    /**
     * Move link modal
     *
     * @param moodle_url $submiturl
     * @param block_flexpagenav_model_link $link
     * @param block_flexpagenav_model_menu $menu
     * @return string
     */
    public function move_link(moodle_url $submiturl, block_flexpagenav_model_link $link, block_flexpagenav_model_menu $menu) {
        $box = new course_format_flexpage_lib_box(array('class' => 'block_flexpagenav_movelink'));

        $options = array();
        foreach ($menu->get_links() as $reflink) {
            if ($reflink->get_id() == $link->get_id()) {
                continue;
            }
            $options[$reflink->get_id()] = trim(strip_tags($reflink->load_type()->get_info()));
        }
        $box->add_new_row()->add_new_cell(get_string('movelinkx', 'block_flexpagenav', $link->load_type()->get_info()))
                           ->add_new_cell(html_writer::select(block_flexpagenav_model_link::get_move_options(), 'move', block_flexpagenav_model_link::MOVE_AFTER, false))
                           ->add_new_cell(html_writer::select($options, 'reflinkid', '', false));

        return $this->form_wrapper($submiturl, $box);
    }

    /**
     * Delete link modal
     *
     * @param moodle_url $submiturl
     * @param block_flexpagenav_model_link $link
     * @return string
     */
    public function delete_link(moodle_url $submiturl, block_flexpagenav_model_link $link) {
        $areyousure = get_string('areyousuredeletelink', 'block_flexpagenav', $link->load_type()->get_info());
        return $this->form_wrapper($submiturl, html_writer::tag('div', $areyousure, array('class' => 'block_flexpagenav_deletelink')));
    }
}