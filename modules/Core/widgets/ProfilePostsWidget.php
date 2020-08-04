<?php
/*
 *	Made by Aberdeener
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr8
 *
 *  License: MIT
 *
 *  Profile Posts Widget
 */
class ProfilePostsWidget extends WidgetBase {
    private $_cache, $_smarty, $_language, $_user, $_timeago;

    public function __construct($pages = array(), $smarty, $language, $cache, $user, $timeago) {
        $this->_language = $language;
        $this->_smarty = $smarty;
        $this->_cache = $cache;
        $this->_user = $user;
        $this->_timeago = $timeago;

        parent::__construct($pages);

        // Get order
        $order = DB::getInstance()->query('SELECT `order` FROM nl2_widgets WHERE `name` = ?', array('Latest Profile Posts'))->first();

        // Set widget variables
        $this->_module = 'Core';
        $this->_name = 'Latest Profile Posts';
        $this->_location = 'right';
        $this->_description = 'Display the latest profile posts on your site.';
        $this->_order = $order->order;
    }

    public function initialise() {
        // Generate HTML code for widget
        if ($this->_user->isLoggedIn()) {
            $user_group = $this->_user->data()->group_id;
        } else {
            $user_group = null;
        }

        if ($user_group) {
            $cache_name = 'profile_posts_' . $user_group;
            $user_id = $this->_user->data()->id;
        } else {
            $cache_name = 'profile_posts_guest';
            $user_id = 0;
        }
        $this->_cache->setCache($cache_name);

        $posts_array = array();

        if ($this->_cache->isCached('profile_posts_' . $user_id)) {
             $posts_array = $this->_cache->retrieve('profile_posts_' . $user_id);
         } else {
            $posts = DB::getInstance()->query('SELECT * FROM nl2_user_profile_wall_posts ORDER BY time DESC LIMIT 5')->results();
            foreach ($posts as $post) {

                if ($user_group) {
                    if ($this->_user->isBlocked($post->author_id, $this->_user->data()->id)) continue;
                    if ($this->_user->isPrivateProfile($post->author_id) && !$this->_user->hasPermission('profile.private.bypass')) continue;
                } else if ($this->_user->isPrivateProfile($post->author_id)) continue;

                $posts_array[] = array(
                    'avatar' => $this->_user->getAvatar($post->author_id, "../", 64),
                    'username' => $this->_user->idToNickname($post->author_id),
                    'username_style' => $this->_user->getGroupClass($post->author_id),
                    'content' => Util::truncate($post->content, 20),
                    'link' => URL::build('/profile/' . $this->_user->idToName($post->author_id) . '/#post-' . $post->id),
                    'date_ago' => date('d M Y, H:i', $post->time),
                    'user_id' => $post->author_id,
                    'user_profile_link' => URL::build('/profile/' . $this->_user->idToName($post->author_id)),
                    'ago' => $this->_timeago->inWords(date('d M Y, H:i', $post->time), $this->_language->getTimeLanguage())
                );
            }
            $this->_cache->store('profile_posts_' . $user_id, $posts_array, 120);
        }
        if (count($posts_array) >= 1) {
            $this->_smarty->assign(array(
                'POSTS' => $posts_array
            ));
        }
        $this->_smarty->assign(array(
            'LATEST_PROFILE_POSTS' => $this->_language->get('user', 'latest_profile_posts'),
            'NO_PROFILE_POSTS' => $this->_language->get('user', 'no_profile_posts')
        ));
        $this->_content = $this->_smarty->fetch('widgets/profile_posts.tpl');;
    }
}