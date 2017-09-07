<?php

?>
<html>
<head>
	<?php wp_head() ?>
</head>
<body style="color: #000; background-color: #fff">
<?php
// Start the loop.
while ( have_posts() ) : the_post();
	the_content();
	// End of the loop.
endwhile;
?>
</body>
</html>
