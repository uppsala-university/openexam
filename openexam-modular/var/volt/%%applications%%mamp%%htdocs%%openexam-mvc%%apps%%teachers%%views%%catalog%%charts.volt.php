
<div class="section-header">
	<h2>Popular Albums by Genre</h2>
</div>

<table><tr>
	<?php $n = 1; ?>
	<?php foreach ($charts as $genre => $chart) { ?>
	<td valign="top">
		<table class="chart">
			<tr>
				<td colspan="2" class="title" align="left">
					<?php echo $this->tag->linkTo(array('tag/' . $genre, $genre)); ?>
				</td>
			</tr>
			<?php foreach ($chart as $album) { ?>
				<tr>
					<td class="image" valign="top" align="left">
						<img src="<?php echo $album->url; ?>" alt="<?php echo $album->name; ?>">
					</td>
					<td class="album" valign="top" align="left">
						<?php echo $this->tag->linkTo(array('album/' . $album->id . '/' . $album->uri, $album->name)); ?><br/>
						<div class="artist-name"><?php echo $this->tag->linkTo(array('artist/' . $album->artist_id . '/' . $album->uri, $album->artist)); ?></div>
					</td>
				</tr>
			<?php } ?>
		</table>
	</td>

	<?php if (($n % 3) == 0) { ?>
		</tr><tr>
	<?php } ?>

	<?php $n = $n + 1; ?>

	<?php } ?>
</table>