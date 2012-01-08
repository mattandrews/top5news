<!doctype html>
<html >
<head>
    <title>Top 5 News | beta v1.0</title>
    
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta charset="utf-8" />
    <link href="<?php echo base_url(); ?>assets/css/top5news.css" rel="stylesheet" type="text/css" />
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
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
</head>

<body>
    <div id="container">
        <h1>Top 5 News</h1>
        <p>The five most popular stories on the UK's most popular news websites. Feeds are refreshed every 15 minutes.<br />
        An experiment by <a href="http://www.benjilanyado.com/">Benji Lanyado</a> and <a href="http://mattandrews.info">Matt Andrews</a>.</p>
        <?php foreach($news as $source=>$stories) {
            echo '<div class="newsbox';
            if($stories[0]['source_name'] == 'meta') {
                echo ' metabox';
            }
            echo '">';
            echo '<h2 class="' . $stories[0]['source_name'] . '">' . $source;
            echo '<span title="' . date('l jS F Y (g:ia)', strtotime($stories[0]['created'])) . '">~' . $this->prettydate->getStringResolved($stories[0]['created']) . '</span>';
            echo '</h2>';
            echo "<ol>";
            foreach($stories as $s) {
                if($is_mobile) {
                    $headline = $s['headline'];
                } else {
                    $headline = word_limiter($s['headline'], 8);
                }
                echo '<li><a data-id="'.$s['id'].'" target="_new" href="' . $s['url'] . '" title="'.$s['headline'].'">' . $headline . '</a></li>';
            }
            echo "</ol>";
            echo "</div>";
        } ?>

        <p>Additional design by <a href="http://www.twitter.com/lawrencebrown">Lawrence Brown</a>. <a href="mailto:top5newsuk@gmail.com" id="contact-us">Contact us?</a> Or why not <a id="go-meta" href="javascript://">go meta</a>.</p>

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

           $('#go-meta').click(function(){
              $('.metabox').toggle().css('display', 'inline-block'); 
           });

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