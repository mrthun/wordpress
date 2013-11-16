<div id="ctsearch-content">
	<?php if($data): ?>
	<div id="ctsearch-status">Page <?php print $data->getCurrentPage(); ?> of <?php print $data->getTotalPages(); ?></div>
<?php while($job = $data->has_next()): ?>
	<div class="ctsearch-job">
		<h3 class="job-title">
			<?php if($job->getURL()): ?>
				<a href="<?php print $job->getURL(); ?>" target="_blank"><?php print $job->getTitle(); ?></a>
			<?php else: ?>
				<?php print $job->getTitle(); ?>
			<?php endif; ?>
		</h3>
		<div class="job-location"><span class="label">Location:</span> <?php print $job->getLocation(TRUE); ?></div>
		<div class="job-post-date"><span class="label">Date Posted:</span> <?php print date(get_option('date_format'), $job->getDatePosted()); ?></div>
		<div class="job-description"><span class="label">Description:</span> <?php print $job->getExcerpt(); ?></div>
		<div class="more clearfix"><a href="<?php print $job->getURL(); ?>" target="_blank">Full description</a></div>
	</div>
<?php endwhile; ?>

<div id="jobamatic-attribution"><a href="" target="_blank" title="Powered by SimplyHired."><img src="<?php echo $this->plugin_url;?>/img/Logo_SHpartner.png" height="20" width="150" alt="Powered by SimplyHired" /></a></div>

	<?php else: ?>
		<p></p>
	<?php endif; ?>
</div>
<?php if($pager): ?>
	<div id="ctsearch-pager"><?php print $pager; ?></div>
<?php endif; ?>