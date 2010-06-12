{embed="partials/header"}
					<p class="introduction">The DC Creative Space Finder is a listing of all the spaces available for DC creatives.</p>
					<div id="find-space">
						<h2>Find a Space</h2>
					</div>				
					<div id="recent-spaces">
						<h2>Recent Spaces</h2>
						<ul>
						{exp:weblog:entries weblog="spaces" limit="10"}
							<li>
								<a href="{url_title_path="space/detail"}"><strong>{title}</strong></a>
							</li>					
						{/exp:weblog:entries}
						</ul>
					
					</div>



{embed="partials/footer"}
