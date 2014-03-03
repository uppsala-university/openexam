<div align="center">

		

	<div id="top-menu">
		<div id="logo"></div>

		<div id="menu-divider"></div>

		<div id="menu-links">
			<ul id="menu-header-navigation" class="menu">
				<li class="menu-item">
					<?php echo $this->tag->linkTo(array('index', 'Home')); ?>
				</li>
				<li class="menu-item">
					<?php echo $this->tag->linkTo(array('help', 'Help')); ?>
				</li>
				<li class="menu-item">
					<?php echo $this->tag->linkTo(array('about', 'About')); ?>
				</li>
			</ul>
		</div>

		<div id="header-search">
			<?php echo $this->tag->form(array('search')); ?>
				<div>
					<input id="s" type="text" name="s" value="">
					<input id="searchsubmit" type="submit" value="Search">
				</div>
			</form>
		</div>

	</div>

	<?php echo $this->getContent(); ?>

	<div id="footer">
		 <?php echo $this->tag->linkTo(array('http://uu.se', 'Uppsala Universitet')); ?>
	</div>

</div>