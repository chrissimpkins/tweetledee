<div style='float:left;margin: 0 6px 6px 0;'>
	<a href='https://twitter.com/<?php echo $tweeter ?>/statuses/<?php echo $currentitem['id_str']; ?>' border=0 target='blank'>
		<img src='<?php echo $avatar; ?>' border=0 />
	</a>
</div>
<strong><?php echo $fullname; ?></strong> <a href='https://twitter.com/<?php echo $tweeter; ?>' target='blank'>@<?php echo $tweeter;?></a><?php echo $rt ?><br />
<?php echo $parsedTweet; ?>
<?php if(isset($currentitem['extended_entities']['media'])): ?>
<?php foreach ($currentitem['extended_entities']['media'] as $entity):?>
<img src='<?php echo $entity['media_url_https'] ?>' border=0 />
<?php endforeach;?>
<?php endif; ?>
<?php if(isset($entities['urls']) && count($entities['urls'])>0): ?>
<?php foreach ($entities['urls'] as $included_tweet_url):?>

<div class="quoted_url"  style="padding: 10px; margin: 10px; border:2px solid lightgrey;-moz-border-radius: 15px; border-radius: 15px;">
<?php echo $renderer->render_quoted_content($included_tweet_url, $recursion_level)?>
</div>

<?php endforeach;?>
<?php endif; ?>
