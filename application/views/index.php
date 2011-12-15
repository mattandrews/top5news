<!doctype html>
<html >
<head>
    <title>Top 5 News | beta v1.0</title>
    
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta charset="utf-8" />
    <link href="<?php echo base_url(); ?>assets/css/top5news.css" rel="stylesheet" type="text/css" />
    
</head>

<body>
    <div id="container">
        <h1>Top 5 News</h1>
        <p>The five most popular stories on the UK's most popular news websites. Feeds are refreshed every 15 minutes.<br />
        An experiment by <a href="http://www.benjilanyado.com/">Benji Layado</a> and <a href="http://mattandrews.info">Matt Andrews</a>.</p>
        <?php foreach($news as $source=>$stories) {
            echo '<div class="newsbox">';
            echo '<h2 class="' . $stories[0]['source_name'] . '">' . $source;
            echo '<span title="' . date('l jS F Y (g:ia)', strtotime($stories[0]['created'])) . '">~' . $this->prettydate->getStringResolved($stories[0]['created']) . '</span>';
            echo '</h2>';
            echo "<ol>";
            foreach($stories as $s) {
                echo '<li><a target="_new" href="' . $s['url'] . '">' . $s['headline'] . '</a></li>';
            }
            echo "</ol>";
            echo "</div>";
        } ?>

    </div>
</body>