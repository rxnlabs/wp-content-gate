<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title><?php wp_title('');?></title>
	<?php wp_head();?>
</head>
<body <?php body_class();?>>
	<?php if( have_posts() ): while( have_posts() ):the_post();?>
		<div class="wcg-<?php the_ID();?>">
			<?php the_content();?>
		</div>	
	<?php endwhile;else:_e('Post not found');endif;?>
	<?php wp_footer();?>
</body>
</html>