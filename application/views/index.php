<!doctype html>
<html >
<head>
    <title>Top 5 News | beta v1.0</title>
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <meta charset="utf-8" />

    <link href="<?php echo base_url(); ?>assets/css/top5news.css?v=3" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/css/mobile.css?v=2" />

    <script type="text/javascript">
      var _gaq = _gaq || [];
      _gaq.push(['_setAccount', 'UA-27742277-1']);
      _gaq.push(['_trackPageview']);

      (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
      })();
    </script>

    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/jquery-1.7.1.min.js"></script>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/jquery-ui.js"></script>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/jquery.cookie.js"></script>

    <script>
      $(document).ready(function(){
        var links = [];
        <?php if($locale == 1) { ?>
        var last_datestamp = "<?php echo $news['guardian'][0]['created']; ?>";
        <?php } else if ($locale == 2) { ?>
        var last_datestamp = "<?php echo $news['guardianusa'][0]['created']; ?>";
        <?php } ?>

        $('div.newsbox a').each(function(i){
          links[i] = $(this).attr('href');
        });

        $.ajax({
          url: '<?php echo site_url("renderer/rankchanges"); ?>', 
          dataType: 'json',
          type: 'post',
          data: 'date=' + last_datestamp + '&links=' + links,
          success: function(json){
            for (var i in json) {
              var result = json[i];

              var item = $('div.newsbox a[href="'+result.url+'"]');
              
              if(item.length) {
                var current_rank = item.data('rank');
                var old_rank = result.rank;
                if (current_rank > old_rank) {
                  $('<span class="up"></span>').appendTo(item);
                } else if (current_rank < old_rank) {
                  $('<span class="down"></span>').appendTo(item);
                }
              }
            }
          }
        });

        $('#container').sortable({
          items: 'div.newsbox',
          start: function(event, ui) {
            $('ul.sortable li').css('float', 'left');
          },
          stop: function(event, ui) {
            $('ul.sortable li').css('float', 'none');
            var serialize = $('#container').sortable('toArray');
            $.cookie('top5news_custom_order_<?php echo $locale; ?>', serialize, { expires: 365 });
          }
        }).disableSelection();

      });
    </script>
</head>

<body>

    <div id="container">

        <h1>Top 5 News</h1>
        <p>The five most popular stories on <?php if($locale == 1) { echo "the UK"; } else if ($locale == 2) { echo "America"; } ?>'s most popular news websites. Feeds are refreshed every 15 minutes.<br />
        An experiment by <a href="http://www.benjilanyado.com/">Benji Lanyado</a> and <a href="http://mattandrews.info">Matt Andrews</a>. Why not <a href="https://twitter.com/Top5NewsUK">follow Top5News on Twitter</a>?</p>

        <p>Want custom ordering? Drag and drop a box to save your custom preference and we'll show it to you like that from now on.</p>
        <?php foreach($news as $source=>$stories) {
            $source_name = $stories[0]['source_name'];
            $source_title = $stories[0]['full_name'];
            echo '<div id="item-'.$source_name.'" class="newsbox';
            if($source_name == 'meta') {
                echo ' metabox';
            }
            echo '">';
            echo '<h2 class="' . $source_name . '">';
            if($source == 'meta') {
              echo "Top 5 Top 5";
            } else {
              echo $source_title;
            }
            echo '<span title="' . date('l jS F Y (g:ia)', strtotime($stories[0]['created'])) . '">~' . $this->prettydate->getStringResolved($stories[0]['created']) . '</span>';
            echo '</h2>';
            echo "<ol>";
            foreach($stories as $s) {
                $headline = strip_tags($s['headline']);
                if($source_name == 'meta') {
                  $headline = ' <strong>' . $s['full_name'] . '</strong>: ' . $headline;
                }
                if(!$is_mobile) {
                    $headline = character_limiter($headline, 65);
                }

                echo '<li id="link-id-'.$s['id'].'"><a data-id="'.$s['id'].'" data-rank="'.$s['rank'].'" target="_blank" href="' . $s['url'] . '" title="'.$s['headline'].'">' . $headline . '</a></li>';
            }
            echo "</ol>";
            echo "</div>";
        } ?>

        <p>Additional design by <a href="http://www.twitter.com/lawrencebrown">Lawrence Brown</a>. Want to <a href="mailto:top5newsuk@gmail.com" id="contact-us">contact us?</a><br /> Follow us at <a href="https://twitter.com/Top5NewsUK">@Top5NewsUK</a>.</p>

        <form action="<?php echo site_url('renderer/email'); ?>" id="email-form" method="post">
            <label>Your name</label>
            <input size="40" type="text" name="name" />
            <label>Your email</label>
            <input size="40" type="text" name="email" />
            <label>Your comments</label>
            <textarea name="comments" rows="6" cols="60"></textarea>
            <input type="submit" id="send-email" value="Send message" />
        </form>

    </div>

    <script>
        $(document).ready(function(){
           $('#email-form').hide();

           $('#contact-us').click(function(){
              $('#email-form').slideToggle(); 
           });

           $('#email-form').submit(function(){
              $('.form-feedback').remove();
              var form = $(this);
              var name = form.children('input[name=name]');
              var email = form.children('input[name=email]');
              var comments = form.children('textarea[name=comments]');
              if (name.val() == '' || email.val() == '' || comments.val() == '') {
                  update_form_feedback('Please fill out all of the fields!');
              } else {
                  $.ajax({
                     type: 'post',
                     url: form.attr('action'),
                     data: form.serialize(),
                     success: function() {
                         update_form_feedback('Your message was sent -- thanks!');
                     },
                     error: function() {
                         update_form_feedback('There was a problem sending your message, sorry :(');
                     }
                  });
                  return false;
              }
              return false;
           });

           $('.newsbox a').click(function(){
               var link = $(this);
               var id = link.data('id');
               $.post('<?php echo site_url('renderer/track'); ?>', 'id=' + id);
           })

        });

        function update_form_feedback(msg) {
            $('<p class="form-feedback">' + msg + '</p>').insertAfter('#send-email');
        }
    </script>
</body>
</html>