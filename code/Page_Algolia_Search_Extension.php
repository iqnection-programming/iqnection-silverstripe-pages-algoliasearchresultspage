<?php

	class Page_Algolia_Search_Extension extends Extension
	{
		
	}
	
	class Page_Controller_Algolia_Search_Extension extends Extension
	{
		function AlgoliaSearchPage()
		{
			return DataObject::get_one('AlgoliaSearchResultsPage');
		}		
	}
	