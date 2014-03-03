
<table class="albums-index">
	<?php $n = 1; ?>
	<tr>
	<?php foreach ($albums as $album) { ?>
		<td valign="top">
			<div class="album-name">
				<?php echo $this->tag->linkTo(array('album/' . $album->id . '/' . $album->uri, '<img src="' . $album->url . '" alt="' . $album->name . '"/>')); ?>
			</div>
			<div class="album-name">
				<?php echo $this->tag->linkTo(array('album/' . $album->id . '/' . $album->uri, $album->name)); ?>
			</div>
			<div class="artist-name">
				<?php echo $this->tag->linkTo(array('artist/' . $album->artist_id . '/' . $album->uri, $album->artist)); ?>
			</div>
		</td>
		<?php if (($n % 6) == 0) { ?>
			</tr><tr>
		<?php } ?>
		<?php $n = $n + 1; ?>
	<?php } ?>
</table>