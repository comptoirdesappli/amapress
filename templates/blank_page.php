<?php
/**
 * Created by hpStorm.
 * User: Guillaume
 * Date: 26/05/2016
 * Time: 00:11
 */
?>
<html>
<head>
	<?php wp_head() ?>
</head>
<body>
oto
<?php
// Start the loop.
while ( have_posts() ) : the_post();
	the_content();
	// End of the loop.
endwhile;
?>
</body>
</html>
