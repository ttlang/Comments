<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8" />
	<title>Comments</title>
	<link rel="stylesheet" href="style.css" type="text/css" />
</head>
<body>
	<article>
<?php
	require('markdown.php');
	echo Markdown(file_get_contents('README.markdown'));
?>
	<h2>Love your comments</h2>
	<!-- Start of comment code -->
	<div id="comments">Loading comments...</div>
	<script type="text/javascript" src="https://silentorbit.com/comments/script/?sid=1" async="async"></script>
	<!-- End of comment code -->
	</article>
</body>
</html>
