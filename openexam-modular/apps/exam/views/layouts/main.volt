<div align="center">

		

	<div id="top-menu">
		<div id="logo"></div>

		<div id="menu-divider"></div>

		<div id="menu-links">
			<ul id="menu-header-navigation" class="menu">
				<li class="menu-item">
					{{ link_to("index", "Home") }}
				</li>
				<li class="menu-item">
					{{ link_to("help", "Help") }}
				</li>
				<li class="menu-item">
					{{ link_to("about", "About") }}
				</li>
			</ul>
		</div>

		<div id="header-search">
			{{ form("search") }}
				<div>
					<input id="s" type="text" name="s" value="">
					<input id="searchsubmit" type="submit" value="Search">
				</div>
			</form>
		</div>

	</div>

	{{ content() }}

	<div id="footer">
		 {{ link_to("http://uu.se", "Uppsala Universitet") }}
	</div>

</div>