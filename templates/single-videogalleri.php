<?php

if (!defined('ABSPATH')) exit;

get_header();

while (have_posts()) :
	the_post();
	VG_Frontend::render_single_page(get_the_ID());
endwhile;

get_footer();
