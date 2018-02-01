<% if $SiteSearchPage %>
<form id="SearchForm_SearchForm" action="{$SiteSearchPage.Link}results" method="get" enctype="application/x-www-form-urlencoded">
	<div id="SearchField">
		<label for="SearchForm_SearchForm_Search">Search</label>
		<input type="text" name="Search" class="text nolabel" id="SearchForm_SearchForm_Search" />
	</div>
	<input type="submit" name="action_results" value="Go" class="action" id="SearchForm_SearchForm_action_results" />
</form>
<% end_if %>