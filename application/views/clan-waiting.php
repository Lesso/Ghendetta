<?php include_once('head.tpl'); ?>
<body>
<?php include_once('navigation.tpl'); ?>
<section class="clan" role="main">
	<h1><?= $clan['name'] ?></h1>
	<p>You and your fellow associates have fought <strong><?= $clan['score'] ?></strong> <abbr title="Foursquare Checkins">battles</abbr>.</p>
	<p>Your capo is not pleased. Fight more battles to reveal who is in your clan.</p>
	<p class="pr-cont"><span class="pr" style="width:<?= $progress ?>%"></span></p>
</section>

</body>
</html>
